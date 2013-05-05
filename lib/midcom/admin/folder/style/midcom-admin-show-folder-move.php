<h1><?php echo $data['title']; ?></h1>

<form method="post">
    <div class="midcom_admin_content_folderlist">
        <?php $data['handler']->show_tree(); ?>
    </div>
    <div class="form_toolbar">
        <input type="submit" class="save" accesskey="s" value="<?php echo $data['l10n']->get('move'); ?>" />
    </div>
</form>