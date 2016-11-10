<?php
midcom::get()->auth->require_admin_or_ip('midcom.helper.filesync');
midcom::get()->header('Content-Type: text/plain');
$exporter = midcom_helper_filesync_exporter::create('page');
// TODO: be smarter
if (!isset($_GET['root'])) {
    $exporter->export();
} else {
    $exporter->read_root($_GET['root']);
}
echo "Export to {$exporter->root_dir} completed\n";