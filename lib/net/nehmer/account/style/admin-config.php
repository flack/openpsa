<?php
// Available request keys: article, controller, edit_url, delete_url, create_urls
$data = & $_MIDCOM->get_custom_context_data('request_data');
?>
<h1><?php echo $data['l10n_midcom']->get('component configuration'); ?>: <?php echo $data['node']->extra; ?></h1>
<?php
$data['controller']->display_form();
?>