<?php
$desc = $data['controller']->datamanager->schema->description;
$title = sprintf($data['l10n_midcom']->get('edit %s'), $data['l10n']->get($desc));
?>

<h2>&(title:h);: <?php echo $data['controller']->datamanager->types['title']->value; ?></h2>

<?php $data['controller']->display_form (); ?>