<?php
// Available request keys:
// entry, processing_msg_raw, processing_msg
//
// Available entry fields, see net_nehmer_buddylist_handler_pending::_pending documentation

//$data =& $_MIDCOM->get_custom_context_data('request_data');
$entry =& $data['entry'];
?>

<form action="&(entry['form_action']);" method='post'>
<input type="hidden" name="guid" value="&(entry['guid_hidden_value']);" />

<p>
<?php if ($entry['view_account_url']) { ?>
    <a href="&(entry['view_account_url']);">
        <?php printf($data['l10n']->get('buddy request from %s:'), $entry['account_user']->name); ?>
    </a>
    <?php
}
else
{
    printf($data['l10n']->get('buddy request from %s:'), $entry['account_user']->name);
}
?>
    <br />
    <input type="submit"
           name="&(entry['approve_submit_name']);"
           value="<?php $data['l10n']->show('approve');?>"
    />
<?php if ($entry['approve_and_add_submit_name']) { ?>
    <input type="submit"
           name="&(entry['approve_and_add_submit_name']);"
           value="<?php $data['l10n']->show('approve and add');?>"
    />
<?php } ?>
    <input type="submit"
           name="&(entry['reject_submit_name']);"
           value="<?php $data['l10n']->show('reject');?>"
    />
</p>

</form>