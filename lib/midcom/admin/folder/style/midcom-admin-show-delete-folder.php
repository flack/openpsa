<h1><?php echo sprintf($_MIDCOM->i18n->get_string('delete folder %s', 'midcom.admin.folder'), $data['title']); ?></h1>
<form method="post" action="" enctype="multipart/form-data" class="datamanager midcom_admin_folder delete_folder">
    <div class="form_description">
        <?php echo $_MIDCOM->i18n->get_string('url name', 'midcom'); ?>
    </div>
    <div class="form_shorttext"><?php echo $data['topic']->name; ?></div>
    <div class="form_description">
        <?php echo $_MIDCOM->i18n->get_string('title', 'midcom'); ?>
    </div>
    <div class="form_shorttext"><?php echo $this->_topic->extra; ?></div>
    <p style="font-weight: bold; color: red;">
    <?php 
    echo $_MIDCOM->i18n->get_string('all descendants will be deleted', 'midcom.admin.folder');
    ?>
    </p>
    <?php
    midcom_admin_folder_handler_delete::list_children($data['topic']->id);
    if (isset($data['symlink']))
    {
        ?><p><?php
        echo sprintf($_MIDCOM->i18n->get_string('this folder is a symlink to <a href="%s">%s</a> and confirming will delete only this symlink', 'midcom.admin.folder'), $data['symlink'], $data['symlink']);
        ?></p><?php
        if (!$GLOBALS['midcom_config']['symlinks'])
        {
            ?><p><?php
            echo sprintf($_MIDCOM->i18n->get_string('symlinks are currently disabled', 'midcom.admin.folder'));
            ?></p><?php
        } 
    }
    ?>
    <p style="font-weight: bold; color: red;">
        <?php echo $_MIDCOM->i18n->get_string('are you sure you want to delete this folder', 'midcom.admin.folder'); ?>
    </p>
    <div class="form_toolbar">
        <input class="save" type="submit" name="f_submit" value=" <?php echo $_MIDCOM->i18n->get_string('delete', 'midcom'); ?>" />
        <input class="cancel" type="submit" accesskey="c" name="f_cancel" value="<?php echo $_MIDCOM->i18n->get_string('cancel', 'midcom'); ?>" />
    </div>
</form>
