<?php
$view = $data['controller']->datamanager->get_content_html();
?>

<h2><?php echo $data['l10n']->get('remove feed'); ?>: &(view['title']:h);</h2>

<form action="" method="post">
  <input type="submit" name="net_nemein_rss_deleteok" class="delete" accesskey="d" value="<?php echo $data['l10n_midcom']->get('delete'); ?> " />
  <input type="submit" name="net_nemein_rss_deletecancel" class="cancel" accesskey="c" value="<?php echo $data['l10n_midcom']->get('cancel'); ?>" />
</form>

<?php
$data['controller']->datamanager->display_view();
?>