<?php
$account =& $data['account'];
$visible_data =& $data['visible_data'];
$schema =& $data['datamanager']->schema;
?>
<div class="vcard">
    <?php
    if ($data['avatar'])
    {
        echo "<a href=\"{$data['profile_url']}\"><img src=\"{$data['avatar_thumbnail_url']}\" class=\"photo\" style=\"float: left; margin-right: 6px;\" alt=\"{$data['user']->name}\" /></a>\n";
    }
    ?>
    <h2 class="fn"><?php echo "<a href=\"{$data['profile_url']}\">{$data['user']->name}</a>"; ?></h2>

    <?php
    $online_state = $data['user']->is_online();
    switch ($online_state)
    {
        case 'offline':
            $last_login = $data['user']->get_last_login();
            if (!$last_login)
            {
                echo "<p class=\"status\"><img src=\"" . MIDCOM_STATIC_URL . "/net.nehmer.account/offline.png\" alt=\"\" /> " . $data['l10n']->get('the user is offline') . "</p>\n";
            }
            else
            {
                echo "<p class=\"status\"><img src=\"" . MIDCOM_STATIC_URL . "/net.nehmer.account/offline.png\" alt=\"\" /> {$data['l10n']->get('last login')}: {strftime('%x %X', $last_login)}</p>\n";
            }
            break;

        case 'online':
            echo "<p class=\"status\"><img src=\"" . MIDCOM_STATIC_URL . "/net.nehmer.account/online.png\" alt=\"\" /> {$data['l10n']->get('the user is online')}</p>\n";
            break;
    }
    ?>
</div>