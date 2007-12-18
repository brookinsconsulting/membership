<?php

function membership_ContentActionHandler( $Module, $http, $objectID )
{
    if ( $http->hasPostVariable( 'MembershipRegisterButton' ) )
    {
        return $Module->redirectTo( '/membership/register/' . $objectID );
    }

    return false;
}

?>
