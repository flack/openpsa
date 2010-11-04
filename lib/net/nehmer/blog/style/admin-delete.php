<?php
// Available request keys: article, datamanager, edit_url, delete_url, create_urls
$dn_data= $data['datamanager']->get_content_html();
$data['view_article'] =& $dn_data;
?>

<h2><?php echo $data['l10n']->get('delete article'); ?>: &(dn_data['title']);</h2>

<form action="" method="post">
  <input type="submit" name="net_nehmer_blog_deleteok" value="<?php echo $data['l10n_midcom']->get('delete'); ?> " />
  <input type="submit" name="net_nehmer_blog_deletecancel" value="<?php echo $data['l10n_midcom']->get('cancel'); ?>" />
</form>

<?php midcom_show_style('show-article'); ?>