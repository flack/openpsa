<?php
// Available request keys: article, datamanager, edit_url, delete_url, create_urls

//$data =& $_MIDCOM->get_custom_context_data('request_data');
$dn_data= $data['datamanager']->get_content_html();
?>

<h1><?php echo $data['l10n']->get('delete person'); ?>: <?php echo $data['person']->name; ?></h1>

<form action="" method="post">
  <input type="submit" name="org_openpsa_contacts_deleteok" value="<?php echo $data['l10n_midcom']->get('delete'); ?> " />
  <input type="submit" name="org_openpsa_contacts_deletecancel" value="<?php echo $data['l10n_midcom']->get('cancel'); ?>" />
</form>

<?php midcom_show_style('show-person'); ?>