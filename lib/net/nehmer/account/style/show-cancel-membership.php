<?php
// This page must pass the HTTP Request Argument cancel_ok to the server to
// initiate deletion, this can be either HTTP GET or POST, whichever you like.
// The value of this member is not important.

//$data =& $_MIDCOM->get_custom_context_data('request_data');
$view = $data['datamanager']->get_content_html();
?>

<h2><?php $data['l10n']->show('cancel membership'); ?></h2>

<form action="" method="post">

<p><?php $data['l10n']->show('cancel membership message'); ?></p>

<div class="form_toolbar">
  <input type="hidden" name="confirmation_hash" value="&(data['confirmation_hash']);" />
  <input type="submit" name="net_nehmer_account_deleteok" value="<?php $data['l10n_midcom']->show('yes'); ?>" />
  <input type="submit" name="net_nehmer_account_deletecancel" value="<?php $data['l10n_midcom']->show('no'); ?>" />
</div>

</form>

<p>&(view['firstname']); &(view['lastname']);</p>

