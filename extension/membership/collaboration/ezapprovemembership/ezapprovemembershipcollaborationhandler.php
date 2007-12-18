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

include_once( 'kernel/classes/ezcollaborationitemhandler.php' );
include_once( 'kernel/classes/ezcollaborationitemparticipantlink.php' );
include_once( 'kernel/classes/ezcollaborationitemgrouplink.php' );
include_once( 'kernel/classes/ezcollaborationprofile.php' );

/// Default status, no approval decision has been made
define( 'EZ_COLLABORATION_APPROVEMEMBERSHIP_STATUS_WAITING', 0 );
/// The membership registration was approved
define( 'EZ_COLLABORATION_APPROVEMEMBERSHIP_STATUS_ACCEPTED', 1 );
/// The membership registration was denied
define( 'EZ_COLLABORATION_APPROVEMEMBERSHIP_STATUS_DENIED', 2 );


define( 'EZ_COLLABORATION_MESSAGE_TYPE_APPROVEMEMBERSHIP', 1 );

class eZApproveMembershipCollaborationHandler extends eZCollaborationItemHandler
{
    function eZApproveMembershipCollaborationHandler()
    {
        $this->eZCollaborationItemHandler( 'ezapprovemembership',
                                   ezi18n( 'extension/membership/collaboration', 'Membership approval' ),
                                   array( 'use-messages' => true,
                                          'notification-types' => true,
                                          'notification-collection-handling' => eZCollaborationItemHandler::NOTIFICATION_COLLECTION_PER_PARTICIPATION_ROLE ) );

    }

    function notificationParticipantTemplate( $participantRole )
    {
        if ( $participantRole == eZCollaborationItemParticipantLink::ROLE_APPROVER )
        {
            return 'approver.tpl';
        }
        else if ( $participantRole == eZCollaborationItemParticipantLink::ROLE_AUTHOR )
        {
            return 'author.tpl';
        }
        else
        {
            return false;
        }
    }

    static function createApproval( $groupID, $userID )
    {
        // create a collaboration item
        include_once( 'kernel/classes/ezcollaborationitem.php' );
        $collaborationItem = eZCollaborationItem::create( 'ezapprovemembership', $userID );
        $collaborationItem->setAttribute( 'data_int1', $groupID );
        $collaborationItem->setAttribute( 'data_int2', $userID );
        $collaborationItem->store();

        $group = eZContentObject::fetch( $groupID );
        $approverID = $group->attribute( 'owner_id' );

        $collaborationID = $collaborationItem->attribute( 'id' );

        // link the participants to the collaboration item
        $participantList = array( array( 'id' => array( $userID ),
                                         'role' => eZCollaborationItemParticipantLink::ROLE_AUTHOR ),
                                  array( 'id' => array( $approverID ),
                                         'role' => eZCollaborationItemParticipantLink::ROLE_APPROVER ) );

        foreach ( $participantList as $participantItem )
        {
            foreach( $participantItem['id'] as $participantID )
            {
                $participantRole = $participantItem['role'];
                $link = eZCollaborationItemParticipantLink::create( $collaborationID, $participantID,
                                                                    $participantRole, eZCollaborationItemParticipantLink::TYPE_USER );
                $link->store();

                $profile = eZCollaborationProfile::instance( $participantID );
                $collabGroupID = $profile->attribute( 'main_group' );
                eZCollaborationItemGroupLink::addItem( $collabGroupID, $collaborationID, $participantID );
            }
        }

        // create a collaboration notification event
        $collaborationItem->createNotificationEvent();
        return $collaborationItem;
    }

    /*
      \reimp
    */
    function content( $collaborationItem )
    {
        $content = array( 'group_id' => $collaborationItem->attribute( 'data_int1' ),
                          'user_id' => $collaborationItem->attribute( 'data_int2' ),
                          'approval_status' => $collaborationItem->attribute( 'data_int3' ) );
        return $content;
    }

    /*
      \reimp
      Updates the last_read time of the participant link
    */
    function readItem( $collaborationItem, $viewMode = false )
    {
        $collaborationItem->setLastRead();
    }

    /*
     \reimp
     \return the number of messages for the approve item.
    */
    function messageCount( $collaborationItem )
    {
        include_once( 'kernel/classes/ezcollaborationitemmessagelink.php' );
        return eZCollaborationItemMessageLink::fetchItemCount( array( 'item_id' => $collaborationItem->attribute( 'id' ) ) );
    }

    /*!
     \static
     \return the status of the approval collaboration item \a $approvalID.
    */
    static function checkApproval( $approvalID )
    {
        include_once( 'kernel/classes/ezcollaborationitem.php' );
        $collaborationItem = eZCollaborationItem::fetch( $approvalID );
        if ( $collaborationItem !== null )
        {
            return $collaborationItem->attribute( 'data_int3' );
        }
        return false;
    }

    /*!
     \reimp
     \return the number of unread messages for the membership approve item.
    */
    function unreadMessageCount( $collaborationItem )
    {
        $lastRead = 0;
        $status = $collaborationItem->attribute( 'user_status' );
        if ( $status )
        {
            $lastRead = $status->attribute( 'last_read' );
        }
        include_once( 'kernel/classes/ezcollaborationitemmessagelink.php' );
        return eZCollaborationItemMessageLink::fetchItemCount( array( 'item_id' => $collaborationItem->attribute( 'id' ),
                                                                      'conditions' => array( 'modified' => array( '>', $lastRead ) ) ) );
    }

    /*!
     \reimp
     Adds a new comment, approves the membership or denies the membership.
    */
    function handleCustomAction( $module, $collaborationItem )
    {
        include_once( 'lib/ezutils/classes/ezdebugsetting.php' );
        $redirectView = 'item';
        $redirectParameters = array( 'full', $collaborationItem->attribute( 'id' ) );
        $addComment = false;

        if ( $this->isCustomAction( 'Approve' ) or $this->isCustomAction( 'Deny' ) )
        {
            eZDebugSetting::writeDebug( 'membership', 'custom action approve or deny', 'eZApproveMembershipCollaborationHandler::handleCustomAction' );
            // check current user's participation role
            $user = eZUser::currentUser();
            $userID = $user->attribute( 'contentobject_id' );
            $participantList = eZCollaborationItemParticipantLink::fetchParticipantList( array( 'item_id' =>
            $collaborationItem->attribute( 'id' ) ) );

            $canApprove = false;

            foreach( $participantList as $participant )
            {
                if ( $participant->ParticipantID == $userID &&
                    $participant->ParticipantRole == eZCollaborationItemParticipantLink::ROLE_APPROVER )
                {
                    $canApprove = true;
                }
            }

            if ( !$canApprove )
            {
                eZDebugSetting::writeDebug( 'membership', 'current user does not have the participant role of approver', 'eZApproveMembershipCollaborationHandler::handleCustomAction' );
                return $module->redirectToView( $redirectView, $redirectParameters );
            }

            $status = EZ_COLLABORATION_APPROVEMEMBERSHIP_STATUS_DENIED;
            if ( $this->isCustomAction( 'Approve' ) )
            {
                $status = EZ_COLLABORATION_APPROVEMEMBERSHIP_STATUS_ACCEPTED;
            }

            $collaborationItem->setAttribute( 'data_int3', $status );
            $collaborationItem->setAttribute( 'status', eZCollaborationItem::STATUS_INACTIVE );
            $timestamp = time();
            $collaborationItem->setAttribute( 'modified', $timestamp );
            $collaborationItem->setIsActive( false );
            $redirectView = 'view';
            $redirectParameters = array( 'summary' );
            $addComment = true;
        }

        if ( $addComment or $this->isCustomAction( 'Comment' ) )
        {
            $messageText = $this->customInput( 'ApproveComment' );
            if ( trim( $messageText ) != '' )
            {
                include_once( 'kernel/classes/ezcollaborationsimplemessage.php' );
                $message = eZCollaborationSimpleMessage::create( 'ezapprovemembership_comment', $messageText );
                $message->store();
                include_once( 'kernel/classes/ezcollaborationitemmessagelink.php' );
                eZCollaborationItemMessageLink::addMessage( $collaborationItem, $message, EZ_COLLABORATION_MESSAGE_TYPE_APPROVEMEMBERSHIP );
            }
        }

        $collaborationItem->sync();
        return $module->redirectToView( $redirectView, $redirectParameters );
    }
}

?>