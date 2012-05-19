<form method="post" action={"/collaboration/action/"|ezurl}>

{let $message_limit=2
     $message_offset=0
     $approval_content=$collab_item.content
     $current_participant=fetch("collaboration","participant",hash("item_id",$collab_item.id))
     $participant_list=fetch("collaboration","participant_map",hash("item_id",$collab_item.id))
     $message_list=fetch("collaboration","message_list",hash("item_id",$collab_item.id,"limit",$message_limit,"offset",$message_offset))
     $group=fetch('content','object', hash('object_id', $approval_content.group_id))
     $author=fetch('content','object', hash('object_id', $approval_content.user_id))}

<div class="context-block">

{* DESIGN: Header START *}<div class="box-header"><div class="box-tc"><div class="box-ml"><div class="box-mr"><div class="box-tl"><div class="box-tr">

<h1 class="context-title">{"Membership approval"|i18n('extension/membership/collaboration')}</h1>

{* DESIGN: Mainline *}<div class="header-mainline"></div>

{* DESIGN: Header END *}</div></div></div></div></div></div>

{* DESIGN: Content START *}<div class="box-ml"><div class="box-mr"><div class="box-content">

<div class="context-attributes">

{switch match=$collab_item.content.approval_status}
{case match=0}

{if $collab_item.is_creator}
{'Your membership request is awaiting approval. If you wish you can add comments for the approver.'|i18n('extension/membership/collaboration')}
{else}
{'The membership request of %authorname for the "%groupname" group needs your approval.'|i18n('extensionnsion/membership/collaboration', '', hash( '%authorname', $author.name|wash, '%groupname', $group.name|wash ))}
{/if}

{/case}
{case match=1}
{if $collab_item.is_creator}
{'Your membership request has been approved.'|i18n('extension/membership/collaboration')}
{else}
{'You have approved the membership request of %authorname for the "%groupname" group.'|i18n('extensionnsion/membership/collaboration', '', hash( '%authorname', $author.name|wash, '%groupname', $group.name|wash ))}
{/if}
{/case}
{case}
{if $collab_item.is_creator}
{'Your membership request has been denied.'|i18n('extension/membership/collaboration')}
{else}
{'You have denied the membership request of %authorname for the "%groupname" group.'|i18n('extensionnsion/membership/collaboration', '', hash( '%authorname', $author.name|wash, '%groupname', $group.name|wash ))}
{/if}
{/case}
{/switch}

{if $approval_content.approval_status|eq(0)}
    <label>{"Comment"|i18n('extension/membership/collaboration')}:</label>
    <textarea class="box" name="Collaboration_ApproveComment" cols="40" rows="5"></textarea>
{/if}
</div>


{* DESIGN: Content END *}</div></div></div>

<div class="controlbar">

{* DESIGN: Control bar START *}<div class="box-bc"><div class="box-ml"><div class="box-mr"><div class="box-tc"><div class="box-bl"><div class="box-br">

<input type="hidden" name="CollaborationActionCustom" value="custom" />
<input type="hidden" name="CollaborationTypeIdentifier" value="ezapprovemembership" />

<input type="hidden" name="CollaborationItemID" value="{$collab_item.id}" />

<div class="block">
{if $approval_content.approval_status|eq(0)}

    <input class="button" type="submit" name="CollaborationAction_Comment" value="{'Add Comment'|i18n('extension/membership/collaboration')}" />

    {if $collab_item.is_creator|not}
    <input class="button" type="submit" name="CollaborationAction_Approve" value="{'Approve'|i18n('extension/membership/collaboration')}" />
    <input class="button" type="submit" name="CollaborationAction_Deny" value="{'Deny'|i18n('extension/membership/collaboration')}" />
    {else}
    <input class="button-disabled" type="submit" name="CollaborationAction_Approve" value="{'Approve'|i18n('extension/membership/collaboration')}" disabled="disabled" />
    <input class="button-disabled" type="submit" name="CollaborationAction_Deny" value="{'Deny'|i18n('extension/membership/collaboration')}" disabled="disabled" />
    {/if}
{else}
    <input class="button-disabled" type="submit" name="CollaborationAction_Comment" value="{'Add Comment'|i18n('extension/membership/collaboration')}" disabled="disabled" />

    <input class="button-disabled" type="submit" name="CollaborationAction_Approve" value="{'Approve'|i18n('extension/membership/collaboration')}" disabled="disabled" />
    <input class="button-disabled" type="submit" name="CollaborationAction_Deny" value="{'Deny'|i18n('extension/membership/collaboration')}" disabled="disabled" />
{/if}
</div>

{* DESIGN: Control bar END *}</div></div></div></div></div></div>
</div>

</div>

<div class="context-block">

{* DESIGN: Header START *}<div class="box-header"><div class="box-tc"><div class="box-ml"><div class="box-mr"><div class="box-tl"><div class="box-tr">

<h2 class="context-title">{"User info"|i18n('extension/membership/collaboration')}</h2>

{* DESIGN: Mainline *}<div class="header-subline"></div>

{* DESIGN: Header END *}</div></div></div></div></div></div>

<div class="box-ml"><div class="box-mr">

<div class="context-information">
    <p class="modified">{'Registered at'|i18n( 'design/admin/node/view/full' )}: {$author.published|l10n(shortdatetime)}</p>
    <div class="break"></div>
</div>

{* DESIGN: Content START *}<div class="box-bc"><div class="box-ml"><div class="box-mr"><div class="box-bl"><div class="box-br"><div class="box-content">

<div class="mainobject-window" title="{$author.name|wash}">
    {content_view_gui view=text_linked content_object=$author}
</div>

</div></div>

{* DESIGN: Content END *}</div></div></div></div></div></div>

</div>

<div class="context-block">

{* DESIGN: Header START *}<div class="box-header"><div class="box-tc"><div class="box-ml"><div class="box-mr"><div class="box-tl"><div class="box-tr">

<h2 class="context-title">{"Group info"|i18n('extension/membership/collaboration')}</h2>

{* DESIGN: Mainline *}<div class="header-subline"></div>

{* DESIGN: Header END *}</div></div></div></div></div></div>

<div class="box-ml"><div class="box-mr">

<div class="context-information">
    <p class="modified">{'Created at'|i18n( 'design/admin/node/view/full' )}: {$group.published|l10n(shortdatetime)}</p>
    <div class="break"></div>
</div>

{* DESIGN: Content START *}<div class="box-bc"><div class="box-ml"><div class="box-mr"><div class="box-bl"><div class="box-br"><div class="box-content">

<div class="mainobject-window" title="{$author.name|wash}">
{foreach $group.main_node.path|append($group.main_node) as $step}
{delimiter} / {/delimiter}
<a href={$step.url_alias|ezurl}>{$step.name}</a>
{/foreach}
</div>

</div></div>

{* DESIGN: Content END *}</div></div></div></div></div></div>

</div>

<div class="context-block">

{* DESIGN: Header START *}<div class="box-header"><div class="box-tc"><div class="box-ml"><div class="box-mr"><div class="box-tl"><div class="box-tr">

<h2 class="context-title">{"Participants"|i18n('extension/membership/collaboration')}</h2>

{* DESIGN: Mainline *}<div class="header-subline"></div>

{* DESIGN: Header END *}</div></div></div></div></div></div>

{* DESIGN: Content START *}<div class="box-bc"><div class="box-ml"><div class="box-mr"><div class="box-bl"><div class="box-br"><div class="box-content">

<div class="block">
{foreach $participant_list as $role}
<label>{$role.name|wash}:</label>
{foreach $role.items as $participant}
<p>{collaboration_participation_view view=text_linked collaboration_participant=$participant}</p>
{/foreach}
{/foreach}
</div>

{* DESIGN: Content END *}</div></div></div></div></div></div>

</div>

{if $message_list}

<div class="context-block">

{* DESIGN: Header START *}<div class="box-header"><div class="box-tc"><div class="box-ml"><div class="box-mr"><div class="box-tl"><div class="box-tr">

<h2 id="messages" class="context-title">{"Messages"|i18n('extension/membership/collaboration')}</h2>

{* DESIGN: Mainline *}<div class="header-subline"></div>

{* DESIGN: Header END *}</div></div></div></div></div></div>

{* DESIGN: Content START *}<div class="box-bc"><div class="box-ml"><div class="box-mr"><div class="box-bl"><div class="box-br"><div class="box-content">

  <table class="special" cellspacing="0">
  {foreach $message_list as $message_link sequence array('bglight','bgdark') as $sequence}

      {collaboration_simple_message_view view=element sequence=$sequence is_read=$current_participant.last_read|gt($message_link.modified) item_link=$message_link collaboration_message=$message_link.simple_message}

  {/foreach}
  </table>

{* DESIGN: Content END *}</div></div></div></div></div></div>

</div>

{/if}

{/let}

</form>
