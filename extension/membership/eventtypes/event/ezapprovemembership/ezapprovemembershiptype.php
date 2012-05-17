<?php
//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// SOFTWARE NAME: eZ publish membership extension
// SOFTWARE RELEASE: 0.x
// COPYRIGHT NOTICE: Copyright (C) 2006-2007 Kristof Coomans <http://blog.kristofcoomans.be>
// SOFTWARE LICENSE: GNU General Public License v2.0
// NOTICE: >
//   This program is free software; you can redistribute it and/or
//   modify it under the terms of version 2.0  of the GNU General
//   Public License as published by the Free Software Foundation.
//
//   This program is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU General Public License for more details.
//
//   You should have received a copy of version 2.0 of the GNU General
//   Public License along with this program; if not, write to the Free
//   Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
//   MA 02110-1301, USA.
//
//
// ## END COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
//

include_once( 'kernel/classes/ezworkflowtype.php' );

define( 'EZ_APPROVEMEMBERSHIP_COLLABORATION_NOT_CREATED', 0 );
define( 'EZ_APPROVEMEMBERSHIP_COLLABORATION_CREATED', 1 );

class eZApproveMembershipType extends eZWorkflowEventType
{
    function eZApproveMembershipType()
    {
        $this->eZWorkflowEventType( 'ezapprovemembership', ezpI18n::tr( 'extension/membership/eventtypes', 'Approve membership' ) );
        // limit workflows which use this event to be used only on the membership pre-register trigger
        $this->setTriggerTypes( array( 'membership' => array( 'register' => array( 'before' ) ) ) );
    }

    /*
     \reimp
    */
    function attributeDecoder( $event, $attr )
    {

    }

    /*
     \reimp
    */
    function typeFunctionalAttributes()
    {
        return array();
    }

    /*
     \reimp
    */
    function fetchHTTPInput( $http, $base, $event )
    {
    }

    /*
     \reimp
    */
    function customWorkflowEventHTTPAction( $http, $action, $workflowEvent )
    {

    }

    /*
     \reimp
    */
    function execute( $process, $event )
    {
        $parameters = $process->attribute( 'parameter_list' );

        //eZDebug::writeDebug( $process );
        //eZDebug::writeDebug( $event );
        eZDebug::writeDebug( $parameters );

        $groupID = $parameters['group_id'];
        $userID = $parameters['user_id'];

        // group owner does not have to approve membership for himself
        include_once( 'kernel/classes/ezcontentobject.php' );
        $group = eZContentObject::fetch( $groupID );
        $ownerID = $group->attribute( 'owner_id' );
        if ( $ownerID == $userID )
        {
            //return eZWorkflowType::STATUS_ACCEPTED;
        }

        $collaborationID = false;
        $db = eZDb::instance();
        $processCollabRows = $db->arrayQuery( 'select workflow_process_id, collaboration_id from ezapprove_items where workflow_process_id = ' . $process->attribute( 'id' )  );
        if ( count( $processCollabRows ) > 0 )
        {
            $collaborationID = $processCollabRows[0]['collaboration_id'];
        }

        eZDebug::writeDebug( $process->attribute( 'event_state'), 'approve $process->attribute( \'event_state\')' );

        if ( $collaborationID === false )
        {
            $this->createApproveMembershipCollaboration( $process, $event, $groupID, $userID );
            $this->setInformation( "We are going to create approval" );
            $process->setAttribute( 'event_state', EZ_APPROVEMEMBERSHIP_COLLABORATION_CREATED );
            $process->store();

            return eZworkflowType::STATUS_DEFERRED_TO_CRON_REPEAT;
        }
        else if ( $process->attribute( 'event_state') == EZ_APPROVEMEMBERSHIP_COLLABORATION_NOT_CREATED )
        {
            eZApproveMembershipCollaborationHandler::activateApproval( $collaborationID );
            $process->setAttribute( 'event_state', EZ_APPROVEMEMBERSHIP_COLLABORATION_CREATED );
            $process->store();

            return eZWorkflowType::STATUS_DEFERRED_TO_CRON_REPEAT;
        }
        else //EZ_APPROVEMEMBERSHIP_COLLABORATION_CREATED
        {
            return $this->checkApproveMembershipCollaboration( $process, $event, $collaborationID );
        }

        return eZWorkflowType::STATUS_ACCEPTED;
    }

    function createApproveMembershipCollaboration( $process, $event, $groupID, $userID )
    {
        include_once( 'extension/membership/collaboration/ezapprovemembership/ezapprovemembershipcollaborationhandler.php' );
        $collaborationItem = eZApproveMembershipCollaborationHandler::createApproval( $groupID, $userID );
        include_once( 'lib/ezdb/classes/ezdb.php' );
        $db = eZDB::instance();
        $db->query( 'INSERT INTO ezapprove_items( workflow_process_id, collaboration_id )
                       VALUES(' . $process->attribute( 'id' ) . ',' . $collaborationItem->attribute( 'id' ) . ' ) ' );

    }

    function checkApproveMembershipCollaboration( $process, $event, $collaborationID )
    {
        include_once( 'extension/membership/collaboration/ezapprovemembership/ezapprovemembershipcollaborationhandler.php' );
        $approvalStatus = eZApproveMembershipCollaborationHandler::checkApproval( $collaborationID );

        switch ( $approvalStatus )
        {
            case EZ_COLLABORATION_APPROVEMEMBERSHIP_STATUS_WAITING:
            {
                $status = eZWorkflowType::STATUS_DEFERRED_TO_CRON_REPEAT;
            } break;

            case EZ_COLLABORATION_APPROVEMEMBERSHIP_STATUS_ACCEPTED:
            {
                $status = eZWorkflowType::STATUS_ACCEPTED;
            } break;

            case EZ_COLLABORATION_APPROVEMEMBERSHIP_STATUS_DENIED:
            {
                $status = eZWorkflowType::STATUS_WORKFLOW_CANCELLED;
            } break;

            default:
            {
                $status = eZWorkflowType::STATUS_WORKFLOW_CANCELLED;
            }
        }

        if ( $approvalStatus != EZ_COLLABORATION_APPROVEMEMBERSHIP_STATUS_WAITING )
        {
            include_once( 'lib/ezdb/classes/ezdb.php' );
            $db = eZDB::instance();
            $db->query( 'DELETE FROM ezapprove_items WHERE workflow_process_id = ' . $process->attribute( 'id' )  );
        }

        return $status;
    }
}

eZWorkflowEventType::registerEventType( 'ezapprovemembership', 'eZApproveMembershipType' );

?>