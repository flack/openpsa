<?php
midcom::get()->auth->require_admin_or_ip('midcom.helper.filesync');
midcom::get()->header('Content-Type: text/plain');
$exporter = midcom_helper_filesync_exporter::create('structure');
$exporter->export();
echo "Export to {$exporter->root_dir} completed\n";
