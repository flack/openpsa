<?php
// Available request keys:
// buddies, buddies_meta, delete_form_action
//
// Available metadata keys, see net_nehmer_buddylist_handler_welcome::_buddies_meta

$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
?>

<h2><?php echo $data['topic']->extra; ?></h2>

<?php if ($data['buddies'])
{
    ?>
    <form action="&(data['delete_form_action']);" method="post" />
    <table border="0">
    <tr>
        <th width="50%" align="left"><?php $data['l10n_midcom']->show('username'); ?></th>
        <th align="center"><?php $data['l10n_midcom']->show('online state'); ?></th>
        <th align="center">&nbsp;</th>
        <?php
        if (   $_MIDCOM->auth->user
            && $data['user']->guid == $_MIDCOM->auth->user->guid)
        {
            ?>
            <th align="center"><?php $data['l10n_midcom']->show('delete'); ?></th>
            <?php
        }
        ?>
    </tr>
    <?php
        foreach ($data['buddies'] as $username => $user)
        {
            $buddy_meta = $data['buddies_meta'][$username];
            $person = $user->get_storage();
            $buddy_meta['view_account_url'] = $person->homepage;
            ?>
            <tr>
            <?php
            if ($buddy_meta['view_account_url'])
            {
                ?>
                <td><a href="&(buddy_meta['view_account_url']);" rel=\"friend\">&(user.name);</a></td>
                <?php
            }
            else
            {
                ?>
                <td>&(user.name);</td>
                <?php
            }
            ?>
            <td align="center"><?php $data['l10n_midcom']->show($buddy_meta['is_online'] ? 'online' : 'offline'); ?></td>
            <?php if ($buddy_meta['new_mail_url'])
            {
                ?>
                <td align="center"><a href="&(buddy_meta['new_mail_url']);"><?php $data['l10n_midcom']->show('write mail'); ?></a></td>
                <?php
            }
            else
            {
                ?>
                <td align="center">&nbsp;</td>
                <?php
            }

            if (   $_MIDCOM->auth->user
                && $data['user']->guid == $_MIDCOM->auth->user->guid)
            {
                ?>
                <td align="center">
                    <input type="checkbox"
                           name="&(buddy_meta['delete_checkbox_name']);"
                    />
                </td>
                <?php
            }
            ?>
        </tr>
        <?php
        }
    ?>
        <tr>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <?php
            if (   $_MIDCOM->auth->user
                && $data['user']->guid == $_MIDCOM->auth->user->guid)
            {
                ?>
                <td align="center">
                    <input type="submit"
                           name="&(data['delete_submit_button_name']);"
                           value="<?php $data['l10n']->show('delete selected'); ?>"
                    />
                </td>
                <?php
            }
            ?>
        </tr>
    </table>
    </form>
    <?php
    $data['qb']->show_pages();
}
else
{
?>
    <p><?php $data['l10n']->show('no buddies found.'); ?></p>
<?php } ?>

<?php
if (   $_MIDCOM->auth->user
    && net_nehmer_buddylist_entry::get_unapproved_count() > 0)
{
    ?>
    <p><a href="&(prefix);pending/list.html"><?php $data['l10n']->show('new buddy requests pending.'); ?></a></p>
    <?php
}
?>