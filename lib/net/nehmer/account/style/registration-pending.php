<?php
// The available request keys can be found in the components' API documentation
// of net_nehmer_account_handler_register
//
// Bind the view data, remember the reference assignment:
//$data =& $_MIDCOM->get_custom_context_data('request_data');
?>

<h1><?php $data['l10n']->show('registration pending for approval'); ?></h1>
<p><?php $data['l10n']->show('your registration is now pending for approval'); ?></p>
<p><?php $data['l10n']->show('you will be notified of the decision to the provided email address'); ?></p>
