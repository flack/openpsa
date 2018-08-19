<?php
if (!isset($_POST['midcom_grid_csv_data'])) {
    throw new midcom_error('Variable midcom_grid_csv_data not set in _POST, aborting');
}

$filename = 'export.csv';
if (isset($_POST['midcom_grid_csv_filename'])) {
    $filename = $_POST['midcom_grid_csv_filename'];
    //some basic sanitation...
    $filename = str_replace(["\n", '"', "'"], '', $filename);
    $filename = str_replace(' ', '_', $filename);
}

midcom::get()->header('Content-type: application/csv; charset=utf-8');
midcom::get()->header('Content-Disposition: attachment;Filename=' . $filename);
echo $_POST['midcom_grid_csv_data'];
