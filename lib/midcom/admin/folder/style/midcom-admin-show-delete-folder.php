<h1><?php echo sprintf($data['l10n']->get('delete folder %s'), $data['title']); ?></h1>
<form method="post" action="" enctype="multipart/form-data" class="datamanager midcom_admin_folder delete_folder">
    <div class="form_description">
        <?php echo $data['l10n_midcom']->get('url name'); ?>
    </div>
    <div class="form_shorttext"><?php echo $data['topic']->name; ?></div>
    <div class="form_description">
        <?php echo $data['l10n_midcom']->get('title'); ?>
    </div>
    <div class="form_shorttext"><?php echo $this->_topic->extra; ?></div>
    <p style="font-weight: bold; color: red;">
    <?php
    echo $data['l10n']->get('all descendants will be deleted');
    ?>
    </p>
    <?php
    midcom_admin_folder_handler_delete::list_children($data['topic']);
    if (isset($data['symlink']))
    {
        ?><p><?php
        echo sprintf($data['l10n']->get('this folder is a symlink to <a href="%s">%s</a> and confirming will delete only this symlink'), $data['symlink'], $data['symlink']);
        ?></p><?php
        if (!midcom::get('config')->get('symlinks'))
        {
            ?><p><?php
            echo $data['l10n']->get('symlinks are currently disabled');
            ?></p><?php
        }
    }
    ?>
    <p style="font-weight: bold; color: red;">
        <?php echo $data['l10n']->get('are you sure you want to delete this folder'); ?>
    </p>
    <div class="form_toolbar">
        <input class="save" type="submit" name="f_submit" value=" <?php echo $data['l10n_midcom']->get('delete'); ?>" />
        <input class="cancel" type="submit" accesskey="c" name="f_cancel" value="<?php echo $data['l10n_midcom']->get('cancel'); ?>" />
    </div>
</form>
