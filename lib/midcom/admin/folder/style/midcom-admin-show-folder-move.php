<form method="post" class="datamanager2">
    <div class="midcom_admin_content_folderlist">
        <?php $data['handler']->show_tree(); ?>
    </div>
    <div class="form_toolbar">
        <input type="submit" class="save" accesskey="s" value="<?php echo $data['l10n']->get('move'); ?>" />
    </div>
</form>