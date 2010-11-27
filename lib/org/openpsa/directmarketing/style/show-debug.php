<?php
echo "<p>\n";
echo 'time:' . time() . "<br>\n";

$badname = 'Minä olen huono tiedosto.foo.jpg';
$badname2 = 'Minä olen huono tiedosto ilman päätettä';
$fixed = midcom_helper_datamanager2_type_blobs::safe_filename($badname, true);
echo "force extension: {$badname} -> {$fixed}<br/>\n";
$fixed = midcom_helper_datamanager2_type_blobs::safe_filename($badname, false);
echo "don't force extension: {$badname} -> {$fixed}<br/>\n";
$fixed = midcom_helper_datamanager2_type_blobs::safe_filename($badname2, true);
echo "force extension2: {$badname2} -> {$fixed}<br/>\n";
$fixed = midcom_helper_datamanager2_type_blobs::safe_filename($badname2, false);
echo "don't force extension2: {$badname2} -> {$fixed}<br/>\n";
?>