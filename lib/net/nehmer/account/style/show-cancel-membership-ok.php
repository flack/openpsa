<?php
// The available request keys can be found in the components' API documentation
// of net_nehmer_account_handler_edit
//
// Bind the view data, remember the reference assignment:
//$data =& $_MIDCOM->get_custom_context_data('request_data');
$view = $data['datamanager']->get_content_html();
$return_url = $GLOBALS['midcom_config']['midcom_site_url'];
?>

<h2><?php $data['l10n']->show('membership cancelled'); ?></h2>

<p><a href="&(return_url);"><?php $data['l10n_midcom']->show('back'); ?></a></p>

<p>&(view['firstname']); &(view['lastname']);</p>