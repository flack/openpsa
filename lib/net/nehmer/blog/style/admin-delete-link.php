<?php
// Available request keys: article, datamanager, edit_url, delete_url, create_urls
?>

<h2><?php echo $data['l10n']->get('delete article link'); ?>: <?php echo $data['article']->title; ?></h2>
<p><?php echo sprintf($data['l10n']->get("this blog entry has been linked from <a href=\"%s\">%s</a> and confirming will delete only the link"), $data['topic_url'], $data['topic_name']); ?></p>
<p><?php echo sprintf($data['l10n']->get("if you want to delete the original article, <a href=\"%s\">click here</a>"), $data['delete_url']); ?></p>
<form action="&(_MIDGARD['uri']:h);" method="post">
  <input type="submit" name="f_delete" value="<?php echo $data['l10n_midcom']->get('delete'); ?> " />
  <input type="submit" name="f_cancel" value="<?php echo $data['l10n_midcom']->get('cancel'); ?>" />
</form>
