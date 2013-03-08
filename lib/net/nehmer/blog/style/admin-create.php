<?php
// Available request keys: controller, schema, schemadb
?>

<h2><?php echo $data['l10n']->get('create article'); ?>: <?php echo $data['topic']->extra; ?></h2>

<?php $data['controller']->display_form (); ?>