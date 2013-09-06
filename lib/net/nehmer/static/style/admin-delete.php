<?php
$dn_data= $data['datamanager']->get_content_html();

$desc = $data['datamanager']->schema->description;
$title = sprintf($data['l10n_midcom']->get('delete %s'), $data['l10n']->get($desc));
?>

<h2>&(title:h);: &(dn_data['title']:h);</h2>

<form action="" method="post">
  <input type="submit" name="net_nehmer_static_deleteok" value="<?php echo $data['l10n_midcom']->get('delete'); ?> " />
  <input type="submit" name="net_nehmer_static_deletecancel" value="<?php echo $data['l10n_midcom']->get('cancel'); ?>" />
</form>

<?php midcom_show_style('show-article'); ?>
