<h1><?php echo $data['title']; ?></h1>
<?php
if (isset($data['symlink']))
{
    ?><p><?php
    echo sprintf($data['l10n']->get('this folder is a symlink to: <a href="%s">%s</a>'), $data['symlink'], $data['symlink']);
    if (!midcom::get('config')->get('symlinks'))
    {
        ?><p><?php
        echo $data['l10n']->get('symlinks are currently disabled');
        ?></p><?php
    }
}

$data['controller']->display_form();
?>