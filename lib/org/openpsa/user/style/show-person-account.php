<?php
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
$username = $data['account']->get_username();
?>
<div class="area org_openpsa_helper_box">
    <h3><?php echo $data['l10n']->get("user account"); ?></h3>
    <?php
    if ($username)
    {
        echo "<p>{$username}</p>\n";
        $user = new midcom_core_user($data['person']);
        if ($user->is_online() == 'online')
        {
            echo '<p>' . $data['l10n']->get('user is online') . "</p>\n";
        }
        else if ($lastlogin = $user->get_last_login())
        {
            echo '<p>' . $data['l10n']->get('last login') . ': ' . strftime('%x %X', $lastlogin) . "</p>\n";
        }
        if (   $data['person']->id == midcom_connection::get_user()
            || midcom::get('auth')->can_user_do('org.openpsa.user:manage', null, 'org_openpsa_user_interface'))
        {
            echo '<ul class="area_toolbar">';
            echo '<li><a class="button" href="' . $prefix . 'account/edit/' . $data['person']->guid . '/" />' . $data['l10n_midcom']->get('edit') . "</a></li>\n";
            echo '<li><a class="button" href="' . $prefix . 'account/delete/' . $data['person']->guid . '/" />' . $data['l10n_midcom']->get('delete') . "</a></li>\n";
            echo "</ul>\n";
        }
    }
    else
    {
        echo '<p><span class="metadata">' . $data['l10n']->get("no account") . '</span></p>';
        if (   $data['person']->id == midcom_connection::get_user()
            || midcom::get('auth')->can_user_do('org.openpsa.user:manage', null, 'org_openpsa_user_interface'))
        {
            echo '<ul class="area_toolbar">';
            echo '<li><a class="button" href="' . $prefix . 'account/create/' . $data['person']->guid . '/" />' . $data['l10n']->get('create account') . "</a></li>\n";
            echo "</ul>\n";
        }
    }
    ?>
</div>