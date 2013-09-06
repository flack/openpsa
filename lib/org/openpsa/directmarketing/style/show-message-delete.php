<?php
$view = $data['view_message'];
?>

<h2><?php echo $data['l10n_midcom']->get('delete'); ?>: &(view['title']);</h2>

<form action="" method="post">
  <input type="submit" name="org_openpsa_directmarketing_deleteok" value="<?php echo $data['l10n_midcom']->get('delete'); ?> " />
  <input type="submit" name="org_openpsa_directmarketing_deletecancel" value="<?php echo $data['l10n_midcom']->get('cancel'); ?>" />
</form>

<?php
midcom_show_style('show-message');
?>