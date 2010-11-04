<?php
// Request Keys:
// datamanager, fields, schema, account, avatar, avatar_thumbnail, form_submit_name,
// processing_msg, profile_url, edit_url, avatar_url, avatar_thumbnail_url
$account =& $data['account'];
$visible_data =& $data['visible_data'];
$schema =& $data['datamanager']->schema;
?>
<?php
if ($data['person_toolbar'])
{
?>
<div class="person_toolbar">
<?php
echo $data['person_toolbar_html'];    
?>
</div>
<?php
}
?>
<div class="vcard">
    <?php
    if ($data['avatar']) 
    {
        echo "<img src=\"{$data['avatar_thumbnail_url']}\" class=\"photo\" style=\"float: left; margin-right: 6px;\" alt=\"{$data['user']->name}\" />\n";
    } 
    ?>
    <h2 class="fn"><?php echo $data['user']->name; ?></h2>

    <?php 
    $online_state = $data['user']->is_online();
    switch ($online_state)
    {
        case 'offline':
            $last_login = $data['user']->get_last_login();
            if (!$last_login)
            {
                echo "<p class=\"status\"><img src=\"" . MIDCOM_STATIC_URL . "/net.nehmer.account/offline.png\" alt=\"\" /> ". $data['l10n']->get('the user is offline') . "</p>\n";
            }
            else
            {
                echo "<p class=\"status\"><img src=\"" . MIDCOM_STATIC_URL . "/net.nehmer.account/offline.png\" alt=\"\" /> {$data['l10n']->get('last login')}: " . strftime('%x %X', $last_login) . "</p>\n";
            }
            break;
            
        case 'online':
            echo "<p class=\"status\"><img src=\"" . MIDCOM_STATIC_URL . "/net.nehmer.account/online.png\" alt=\"\" /> {$data['l10n']->get('the user is online')}</p>\n";
            break;
    }

    echo "<dl>\n";
    foreach ($data['visible_fields'] as $name)
    {
        if (   $name == 'firstname'
            || $name == 'lastname')
        {
            // We already showed these
            continue;
        }
        

        $content = $visible_data[$name];
        if (empty($content))
        {
            continue;
        }
        
        $title = $schema->translate_schema_string($schema->fields[$name]['title']);        
        echo "    <dt>{$title}</dt>\n";
        echo "    <dd class=\"{$name}\">{$content}</dd>\n";
    }
    echo "</dl>\n";
    ?>
</div>
