{def $group=fetch('content','object',hash('object_id',$item.content.group_id))}
<p>{$item.title} for group "<a href={$group.main_node.url_alias|ezurl}>{$group.name|wash}</a>"</p>
{undef $group}