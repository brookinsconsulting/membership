{section show=fetch( 'user', 'has_access_to', hash( 'module', 'membership', 'function', 'register' ) )}
<script type="text/javascript">
menuArray['ContextMenu']['elements']['menu-membership'] = new Array();
menuArray['ContextMenu']['elements']['menu-membership']['url'] = {"/membership/register/%objectID%"|ezurl};
</script>
<hr/>
<a id="menu-membership" href="#" onmouseover="ezpopmenu_mouseOver( 'ContextMenu' );">{"Membership"|i18n("extension/membership")}</a>
{/section}