<?php
$view = $data['datamanager'];

echo "<h1>" . $data['l10n']->get('delete invoice') . ": " . $data['object']->get_label() . "</h1>\n";
?>
<form action="" method="post">
  <input type="submit" name="midcom_baseclasses_components_handler_crud_deleteok" value="<?php echo $data['l10n_midcom']->get('delete'); ?> " />
  <input type="submit" name="midcom_baseclasses_components_handler_crud_deletecancel" value="<?php echo $data['l10n_midcom']->get('cancel'); ?>" />
</form>

<div class="main">
    <?php $view->display_view(); ?>
</div>