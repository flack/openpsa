<h1><?php echo $data['l10n']->get('delete task'); ?>: <?php echo $data['object']->title; ?></h1>

<form action="" method="post">
  <input type="submit" name="midcom_baseclasses_components_handler_crud_deleteok" value="<?php echo $data['l10n_midcom']->get('delete'); ?> " />
  <input type="submit" name="midcom_baseclasses_components_handler_crud_deletecancel" value="<?php echo $data['l10n_midcom']->get('cancel'); ?>" />
</form>

<?php
$data['datamanager']->display_view();
?>