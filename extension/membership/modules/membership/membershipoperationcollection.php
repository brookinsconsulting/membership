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

include_once( 'lib/ezutils/classes/ezdebugsetting.php' );

class MembershipOperationCollection
{
    /*
        Ads a new node assignment for a user
    */
    function addLocation( $groupID, $userID )
    {
        include_once( 'kernel/classes/ezcontentobject.php' );
        $user = eZContentObject::fetch( $userID );
        $group = eZContentObject::fetch( $groupID );

        $selectedNodeID = $group->attribute( 'main_node_id' );

        include_once( 'extension/membership/classes/ezmembership.php' );
        $parentNodeIDArray = eZMemberShip::parentNodeIDArray( $user );

        eZDebugSetting::writeDebug( 'membership', 'selected node id: ' . $selectedNodeID, 'MembershipOperationCollection::addLocation' );
        eZDebugSetting::writeDebug( 'membership', 'existing node id array: ' . var_export( $parentNodeIDArray, true ), 'MembershipOperationCollection::addLocation' );

        if ( !in_array( $selectedNodeID, $parentNodeIDArray ) )
        {
            eZDebugSetting::writeDebug( 'membership', 'no location yet, adding one', 'MembershipOperationCollection::addLocation' );

            include_once( 'lib/ezdb/classes/ezdb.php');
            $db = eZDB::instance();
            $db->begin();

            $insertedNode = $user->addLocation( $selectedNodeID, true );

            $insertedNode->setAttribute( 'contentobject_is_published', 1 );
            $insertedNode->setAttribute( 'main_node_id', $user->attribute( 'main_node_id' ) );
            $insertedNode->setAttribute( 'contentobject_version', $user->attribute( 'current_version' ) );
            $insertedNode->updateSubTreePath();
            $insertedNode->sync();

            $db->commit();

            include_once( 'kernel/classes/datatypes/ezuser/ezuser.php' );
            eZUser::cleanupCache();

            include_once( 'kernel/classes/ezcontentcachemanager.php' );
            eZContentCacheManager::clearContentCacheIfNeeded( $userID );
        }

        return array( 'status' => true );
    }
}
?>