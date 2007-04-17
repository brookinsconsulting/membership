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

$Module =& $Params['Module'];
$groupID = $Params['GroupID'];

if ( !is_numeric( $groupID ) )
{
    return $Module->handleError( EZ_ERROR_KERNEL_NOT_AVAILABLE, 'kernel' );
}

include_once( 'kernel/classes/ezcontentobject.php' );
$group =& eZContentObject::fetch( $groupID );

if ( !is_object( $group ) )
{
    return $Module->handleError( EZ_ERROR_KERNEL_NOT_AVAILABLE, 'kernel' );
}

$groupNode =& $group->attribute( 'main_node' );

if ( !is_object( $groupNode ) )
{
    return $Module->handleError( EZ_ERROR_KERNEL_NOT_AVAILABLE, 'kernel' );
}

include_once( 'extension/membership/classes/ezmembership.php' );
$accessAllowed = eZMembership::checkAccess( 'register', $group );
if ( !$accessAllowed )
{
    return $Module->handleError( EZ_ERROR_KERNEL_ACCESS_DENIED, 'kernel' );
}

include_once( 'kernel/classes/datatypes/ezuser/ezuser.php' );
$user =& eZUser::currentUser();
$userID = $user->attribute( 'contentobject_id' );
$userObject =& $user->attribute( 'contentobject' );

include_once( 'extension/membership/classes/ezmembership.php' );
$parentNodeIDArray = eZMembership::parentNodeIDArray( $userObject );

include_once( 'lib/ezutils/classes/ezdebugsetting.php' );
eZDebugSetting::writeDebug( 'membership', 'parent node id array: ' . implode( ', ', $parentNodeIDArray ), 'membership/register view' );

if ( in_array( $groupNode->attribute( 'node_id' ), $parentNodeIDArray ) )
{
    include_once( 'kernel/common/template.php' );
    $tpl =& templateInit();

    $tpl->setVariable( 'group', $group );

    $Result = array();
    $Result['content'] = $tpl->fetch( 'design:membership/register_alreadymember.tpl' );
    $Result['path'] = array(
        array( 'text' => ezi18n( 'extension/membership', 'Membership' ), 'url' => false ),
        array( 'text' => ezi18n( 'extension/membership', 'Register' ), 'url' => false ) );
    return;
}

$currentAction = $Module->currentAction();

if ( $currentAction == 'Register' )
{
    include_once( 'lib/ezutils/classes/ezoperationhandler.php' );
    $operationResult = eZOperationHandler::execute( 'membership', 'register', array( 'group_id' => $groupID, 'user_id' => $userID ) );

    /*
        \todo Check operation result and act accordingly.
    */
    eZDebug::writeDebug( $operationResult );
    if ( is_array( $operationResult ) && array_key_exists( 'status', $operationResult ) )
    {
        $Result = array();
        $Result['path'] = array(
            array( 'text' => ezi18n( 'extension/membership', 'Membership' ), 'url' => false ),
            array( 'text' => ezi18n( 'extension/membership', 'Register' ), 'url' => false ) );

        switch ( $operationResult['status' ] )
        {
            case EZ_MODULE_OPERATION_CONTINUE:
            {
                include_once( 'kernel/common/template.php' );
                $tpl =& templateInit();

                $tpl->setVariable( 'group', $group );

                $Result['content'] = $tpl->fetch( 'design:membership/register_done.tpl' );
                return;
            } break;

            case EZ_MODULE_OPERATION_CANCELED:
            {
                if ( isset( $operationResult['redirect_url'] ) )
                {
                    return $Module->redirectTo( $operationResult['redirect_url'] );
                }
                else if ( isset( $operationResult['result'] ) )
                {
                    $result =& $operationResult['result'];
                    $resultContent = false;
                    if ( is_array( $result ) )
                    {
                        if ( isset( $result['content'] ) )
                            $resultContent = $result['content'];
                        if ( isset( $result['path'] ) )
                            $Result['path'] = $result['path'];
                    }
                    else
                    {
                        $resultContent = $result;
                    }

                    // Temporary fix to make approval workflow work with edit.
                    if ( strpos( $resultContent, 'Deffered to cron' ) === 0 )
                    {
                        $Result = null;
                    }
                    else
                    {
                        $Result['content'] = $resultContent;
                    }
                }

                return;
            } break;

            case EZ_MODULE_OPERATION_HALTED:
            {
                if ( isset( $operationResult['result'] ) )
                {
                    $result =& $operationResult['result'];
                    $resultContent = false;
                    if ( is_array( $result ) )
                    {
                        if ( isset( $result['content'] ) )
                            $resultContent = $result['content'];
                        if ( isset( $result['path'] ) )
                            $Result['path'] = $result['path'];
                    }
                    else
                        $resultContent =& $result;
                    // Detect defer to cron and show message
                    if ( strpos( $resultContent, 'Deffered to cron' ) === 0 )
                    {
                        include_once( 'kernel/common/template.php' );
                        $tpl =& templateInit();

                        $tpl->setVariable( 'group', $group );

                        $Result['content'] = $tpl->fetch( 'design:membership/register_deferred.tpl' );
                    }
                    else
                    {
                        $Result['content'] =& $resultContent;
                    }
                }

                return;
            } break;
        }
    }
}

include_once( 'kernel/common/template.php' );
$tpl =& templateInit();

$tpl->setVariable( 'group', $group );

$Result = array();
$Result['content'] = $tpl->fetch( 'design:membership/register.tpl' );
$Result['path'] = array(
    array( 'text' => ezi18n( 'extension/membership', 'Membership' ), 'url' => false ),
    array( 'text' => ezi18n( 'extension/membership', 'Register' ), 'url' => false ) );

?>