<?php
// The available request keys can be found in the components' API documentation
// of net_nehmer_account_handler_publish
//
// Bind the view data, remember the reference assignment:
//$data =& $_MIDCOM->get_custom_context_data('request_data');

// Add a few keys to the request context for images in use:
$data['view_imgurl_always'] = MIDCOM_STATIC_URL . '/stock-icons/16x16/ok.png';
$data['view_imgurl_never'] = MIDCOM_STATIC_URL . '/stock-icons/16x16/cancel.png';
?>

<h2><?php $data['l10n']->show('publish account details'); ?></h2>

<form name="net_nehmer_account_publish" action='' method='post' id="net_nehmer_account_publish" enctype='multipart/form-data'>

<table cellspacing='0' cellpadding='0' border='0' style='border-collapse: collapse;'>
