<?php
if ( !isset($_POST['org_openpsa_export_csv_data']) )
{
    debug_push_class(__CLASS__, __FUNCTION__);
    debug_add('Variable org_openpsa_reports_csv not set in _POST, aborting');
    debug_pop();
    die;
}

$filename = 'export.csv';
if (isset($_POST['org_openpsa_export_csv_filename']))
{
    $filename = $_POST['org_openpsa_export_csv_filename'];
    //some basic sanitation...
    $filename = str_replace("\n", '', $filename);
    $filename = str_replace("'", '', $filename);
    $filename = str_replace('"', '', $filename);
    $filename = str_replace(' ', '_', $filename);
}

header ('Content-type: application/csv; charset=utf-8');
header('Content-Disposition: attachment;Filename=' . $filename);
echo $_POST['org_openpsa_export_csv_data'];
?>