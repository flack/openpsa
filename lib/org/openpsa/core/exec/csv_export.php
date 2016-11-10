<?php
if (!isset($_POST['org_openpsa_export_csv_data'])) {
    throw new midcom_error('Variable org_openpsa_reports_csv not set in _POST, aborting');
}

$filename = 'export.csv';
if (isset($_POST['org_openpsa_export_csv_filename'])) {
    $filename = $_POST['org_openpsa_export_csv_filename'];
    //some basic sanitation...
    $filename = str_replace(array("\n", '"', "'"), '', $filename);
    $filename = str_replace(' ', '_', $filename);
}

midcom::get()->header('Content-type: application/csv; charset=utf-8');
midcom::get()->header('Content-Disposition: attachment;Filename=' . $filename);
echo $_POST['org_openpsa_export_csv_data'];