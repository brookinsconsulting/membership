
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

class eZMembership
{
    /*
      \static
    */
    function parentNodeIDArray( &$userObject )
    {
        include_once( 'kernel/classes/eznodeassignment.php' );
        $nodeAssignmentList = eZNodeAssignment::fetchForObject( $userObject->attribute( 'id' ), $userObject->attribute( 'current_version' ), 0, false );
        $assignedNodes =& $userObject->assignedNodes();

        $parentNodeIDArray = array();

        foreach ( $assignedNodes as $assignedNode )
        {
            $append = false;
            foreach ( $nodeAssignmentList as $nodeAssignment )
            {
                if ( $nodeAssignment['parent_node'] == $assignedNode->attribute( 'parent_node_id' ) )
                {
                    $append = true;
                    break;
                }
            }
            if ( $append )
            {
                $parentNodeIDArray[] = $assignedNode->attribute( 'parent_node_id' );
            }
        }

        return $parentNodeIDArray;
    }

    /*
      \static
    */
    function checkAccess( $functionName, $object, $returnAccessList = false )
    {
        include_once( 'kernel/classes/datatypes/ezuser/ezuser.php' );
        $user =& eZUser::currentUser();
        $userID = $user->attribute( 'contentobject_id' );

        $accessResult = $user->hasAccessTo( 'membership' , $functionName );
        $accessWord = $accessResult['accessWord'];

        if ( $accessWord == 'yes' )
        {
            return 1;
        }
        else if ( $accessWord == 'no' )
        {
            if ( $returnAccessList === false )
            {
                return 0;
            }
            else
            {
                return $accessResult['accessList'];
            }
        }
        else
        {
            $policies  = $accessResult['policies'];

            foreach ( $policies as $limitationArray  )
            {
                $access = false;
                eZDebugSetting::writeDebug( 'membership', $limitationArray, 'membership ' . $functionName . ' limitation array' );
                foreach ( array_keys( $limitationArray ) as $key  )
                {
                    switch ( $key )
                    {
                        case 'Class':
                        {
                            if ( in_array( $object->attribute( 'contentclass_id' ), $limitationArray[$key] ) )
                            {
                                $access = true;
                            }
                            else
                            {
                                $access = false;
                            }
                        } break;

                        case 'Section':
                        case 'User_Section':
                        {
                            if ( in_array( $object->attribute( 'section_id' ), $limitationArray[$key]  ) )
                            {
                                $access = true;
                            }
                            else
                            {
                                $access = false;
                            }
                        } break;

                        case 'User_Subtree':
                        {
                            $assignedNodes = $object->attribute( 'assigned_nodes' );
                            if ( count( $assignedNodes ) > 0 )
                            {
                                foreach (  $assignedNodes as  $assignedNode )
                                {
                                    $path = $assignedNode->attribute( 'path_string' );
                                    $subtreeArray = $limitationArray[$key];
                                    foreach ( $subtreeArray as $subtreeString )
                                    {
                                        if ( strstr( $path, $subtreeString ) )
                                        {
                                            $access = true;
                                        }
                                    }
                                }
                            }
                            else
                            {
                                $parentNodes = $object->attribute( 'parent_nodes' );
                                if ( count( $parentNodes ) > 0 )
                                {
                                    foreach ( $parentNodes as $parentNode )
                                    {
                                        include_once( 'kernel/classes/ezcontentobjecttreenode.php' );
                                        $parentNode = eZContentObjectTreeNode::fetch( $parentNode );
                                        $path = $parentNode->attribute( 'path_string' );

                                        $subtreeArray = $limitationArray[$key];
                                        foreach ( $subtreeArray as $subtreeString )
                                        {
                                            if ( strstr( $path, $subtreeString ) )
                                            {
                                                $access = true;
                                                break;
                                            }
                                        }
                                    }
                                }
                            }
                        } break;

                        default:
                        {
                            eZDebug::writeWarning( 'Unknown limitation: ' . $key, 'eZMembership::checkAccess' );
                        }
                    }

                    if ( !$access )
                    {
                        eZDebugSetting::writeDebug( 'membership', $limitationArray[$key],'no access granted by the limitation ' . $key );
                        break;
                    }

                }

                if ( $access )
                {
                    break;
                }
            }

            eZDebugSetting::writeDebug( 'membership', $access, 'access' );

            if ( $access )
            {
                return 1;
            }
            else
            {
                if ( $returnAccessList === false )
                {
                    return 0;
                }
                else
                {
                    return $accessResult['accessList'];
                }
            }
        }
    }
}

?>