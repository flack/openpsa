<?php
// The available request keys can be found in the components' API documentation
// of net_nehmer_account_handler_register
//
// Bind the view data, remember the reference assignment:
//$data =& $_MIDCOM->get_custom_context_data('request_data');
?>

<h1><?php $data['l10n']->show('confirm account details'); ?></h1>

<p><?php $data['l10n']->show('confirm account details explaination'); ?></p>

<?php $data['controller']->display_form(); ?>