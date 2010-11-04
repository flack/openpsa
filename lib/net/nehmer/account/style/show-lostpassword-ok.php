<?php
// The available request keys can be found in the components' API documentation
// of net_nehmer_account_handler_edit
//
// Bind the view data, remember the reference assignment:
//$data =& $_MIDCOM->get_custom_context_data('request_data');
?>

<h2><?php $data['l10n']->show('lost password'); ?></h2>

<p>&(data['processing_msg']);</p>

<p><a href="&(data['profile_url']);"><?php $data['l10n_midcom']->show('back'); ?></a></p>