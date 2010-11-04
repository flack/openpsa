<?php
// Available request keys: product, datamanager, edit_url, delete_url, create_urls
$dn_data= $data['datamanager']->get_content_html();
$data['view_productlink'] =& $dn_data;
?>

<h2><?php echo $data['l10n_midcom']->get('delete productlink'); ?></h2>

<form action="" method="post">
  <input type="submit" name="midcom_baseclasses_components_handler_crud_deleteok" value="<?php echo $data['l10n_midcom']->get('delete'); ?> " />
  <input type="submit" name="midcom_baseclasses_components_handler_crud_deletecancel" value="<?php echo $data['l10n_midcom']->get('cancel'); ?>" />
</form>

<?php midcom_show_style('productlink_view'); ?>