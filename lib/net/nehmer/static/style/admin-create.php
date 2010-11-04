<?php
// Available request keys: controller, indexmode, schema, schemadb

$desc = $data['schemadb'][$data['schema']]->description;
$title = sprintf($data['l10n_midcom']->get('create %s'), $data['l10n']->get($desc));
?>

<h2>&(title:h);: <?php echo $data['topic']->extra; ?></h2>

<?php if ($data['indexmode']) { midcom_show_style('admin-create-indexnote'); } ?>

<?php $data['controller']->display_form (); ?>