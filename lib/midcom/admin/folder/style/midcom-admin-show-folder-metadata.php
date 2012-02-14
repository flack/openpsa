<h1><?php echo $data['title']; ?></h1>
<?php
if (isset($data['symlink']))
{
    ?><p><?php
    echo sprintf(midcom::get('i18n')->get_string('this folder is a symlink to: <a href="%s">%s</a>', 'midcom.admin.folder'), $data['symlink'], $data['symlink']);
    if (!$GLOBALS['midcom_config']['symlinks'])
    {
        ?><p><?php
        echo sprintf(midcom::get('i18n')->get_string('symlinks are currently disabled', 'midcom.admin.folder'));
        ?></p><?php
    }
}

$data['controller']->display_form();
?>