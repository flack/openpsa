<?php
set_time_limit(50000);
ini_set('memory_limit', "800M");
while(@ob_end_flush());
echo "<pre>\n";

$qb = org_openpsa_sales_salesproject_dba::new_query_builder();
$results = $qb->execute();

foreach($results as $salesproject)
{
    $salesproject->mark_delivered();
    $salesproject->mark_invoiced();
}
echo "Done.\n";
echo "</pre>";
ob_start();
?>