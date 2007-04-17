<h1>{"Registration for %name"|i18n("extension/membership", '', hash("%name", $group.name))}</h1>
<div>

{foreach $group.main_node.path|append($group.main_node) as $step}
{delimiter} / {/delimiter}
<a href={$step.url_alias|ezurl}>{$step.name}</a>
{/foreach}

<p>{"Congratulations! You are now member of this group."|i18n("extension/membership")}</p>

</div>