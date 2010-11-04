<?php
// Available request keys: resource, controller

//$data =& $_MIDCOM->get_custom_context_data('request_data');
?>

<h1><?php echo $data['l10n_midcom']->get('edit'); ?>: <?php echo $data['controller']->datamanager->types['title']->value; ?></h1>

<?php $data['controller']->display_form(); ?>