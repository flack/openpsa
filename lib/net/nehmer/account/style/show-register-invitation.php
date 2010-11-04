<?php
$inviter = $_MIDCOM->auth->get_user($data['invite']->metadata->creator);
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
<h1><?php echo $data['l10n']->get('register'); ?></h1>

<?php
echo "<p>" . sprintf($data['l10n']->get('%s has invited you to join this site'), "<a href=\"{$prefix}view/{$inviter->guid}/\">{$inviter->name}</a>") . "</p>\n";
?>
<form method="post">
<input type="submit" name="net_nehmer_account_register_invitation" value="<?php echo $data['l10n']->get('register'); ?>"/>
<input type="submit" name="net_nehmer_account_cancel_invitation" value="<?php echo $data['l10n']->get('cancel'); ?>"/>
</form>


