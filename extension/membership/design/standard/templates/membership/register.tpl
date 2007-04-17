{def $postURL=concat("/membership/register/", $group.id)}

<form action={$postURL|ezurl} method="post">
<h1>{"Registration for %name"|i18n("extension/membership", '', hash("%name", $group.name))}</h1>
<div>

{foreach $group.main_node.path|append($group.main_node) as $step}
{delimiter} / {/delimiter}
<a href={$step.url_alias|ezurl}>{$step.name}</a>
{/foreach}

<p>{"Do you want to join this group?"|i18n("extension/membership")}</p>
<input type="submit" name="RegisterButton" value="{'Yes, join the club!'|i18n('extension/membership')}" />
</div>
</form>

{undef $postURL}