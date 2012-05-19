{let approval_content=$collaboration_item.content
     $group=fetch('content','object', hash('object_id', $approval_content.group_id))
     $author=fetch('content','object', hash('object_id', $approval_content.user_id))}
{set-block scope=root variable=subject}{'[%sitename] Membership registration for group "%groupname" waits for approval'
                                        |i18n( "extension/membership/collaboration",,
                                               hash( '%sitename', ezini( "SiteSettings", "SiteURL" ),
                                                     '%groupname', $group.name|wash ) )}{/set-block}
{'%authorname has requested to join the group "%groupname" at %sitename.
You need to approve or deny this request by using the URL below.'
 |i18n( 'extension/membership/collaboration',,
        hash( '%sitename', ezini( "SiteSettings", "SiteURL" ),
              '%groupname', $group.name|wash,
              '%authorname', $author.name|wash ) )}
http://{ezini( "SiteSettings", "SiteURL" )}/collaboration/item/full/{$collaboration_item.id}

{"If you do not wish to continue receiving these notifications,
change your settings at:"|i18n( 'design/standard/notification' )}
http://{ezini( "SiteSettings", "SiteURL" )}/notification/settings/

--
{"%sitename notification system"
 |i18n( 'extension/membership/collaboration',,
        hash( '%sitename', ezini( "SiteSettings", "SiteURL" ) ) )}
{/let}