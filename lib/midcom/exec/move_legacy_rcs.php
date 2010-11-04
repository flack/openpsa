<?php
$_MIDCOM->auth->require_admin_user();
$_MIDCOM->cache->content->content_type('text/plain');

@ini_set('max_execution_time', 0);
while(@ob_end_flush());
$rcs_service = $_MIDCOM->get_service('rcs');
$basepath = $rcs_service->config->get_rcs_root();

$dp = opendir($basepath);
while(($file = readdir($dp)) !== false)
{
    flush();
    if (   $file == '.'
        || $file == '..'
        || is_dir("{$basepath}/$file"))
    {
        continue;
    }
    $oldpath = "{$basepath}/$file";
    $newdir = "{$basepath}/{$file[0]}/{$file[1]}";
    if (!file_exists($newdir))
    {
        if (!file_exists("{$basepath}/{$file[0]}"))
        {
            mkdir("{$basepath}/{$file[0]}");
        }
        mkdir ($newdir);
    }
    $newpath = "{$newdir}/$file";
    $cmd = "mv -f $oldpath $newpath";
    echo $cmd;
    exec($cmd, $output, $status);
    if ($status == 0)
    {
        echo " - OK\n"; 
        continue;
    }
    echo " - FAILED\n";
    
}
ob_start();

?>