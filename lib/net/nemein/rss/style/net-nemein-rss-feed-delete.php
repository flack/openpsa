<?php
// Available request keys: article, datamanager, edit_url, delete_url, create_urls

$view = $data['controller']->datamanager->get_content_html();
?>

<h2><?php echo $_MIDCOM->i18n->get_string('delete feed', 'net.nemein.rss'); ?>: &(view['title']:h);</h2>

<form action="" method="post">
  <input type="submit" name="net_nemein_rss_deleteok" class="delete" accesskey="d" value="<?php echo $data['l10n_midcom']->get('delete'); ?> " />
  <input type="submit" name="net_nemein_rss_deletecancel" class="cancel" accesskey="c" value="<?php echo $data['l10n_midcom']->get('cancel'); ?>" />
</form>

<?php
$data['controller']->datamanager->display_view();
?>