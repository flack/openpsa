<?php
set_time_limit(50000);
ini_set('memory_limit', "800M");
while(@ob_end_flush());
echo "<pre>\n";

$qb = org_openpsa_sales_salesproject_dba::new_query_builder();
$results = $qb->execute();

foreach($results as $salesproject)
{
    $close_est = $salesproject->get_parameter('org.openpsa.sales', 'close_est');
    $profit = $salesproject->get_parameter('org.openpsa.sales', 'profit');
    $value = $salesproject->get_parameter('org.openpsa.sales', 'value');
    $probability = $salesproject->get_parameter('org.openpsa.sales', 'probability');

    $needs_update = false;

    if ($value)
    {
        $salesproject->value = (float) $value;
        $needs_update = true;
        $salesproject->set_parameter('org.openpsa.sales', 'value', '');
    }
    if ($profit)
    {
        $salesproject->profit = (float) $profit;
        $needs_update = true;
        $salesproject->set_parameter('org.openpsa.sales', 'profit', '');
    }
    if ($close_est)
    {
        $salesproject->closeEst = (int) $close_est;
        $needs_update = true;
        $salesproject->set_parameter('org.openpsa.sales', 'close_est', '');
    }
    if ($probability)
    {
        $salesproject->probability = (int) $probability;
        $salesproject->set_parameter('org.openpsa.sales', 'probability', '');
        $needs_update = true;
    }

    if ($needs_update)
    {
        if ($salesproject->update())
        {
            echo "Sales Project #{$salesproject->id} updated\n";
            flush();
        }
        else
        {
            echo "Sales Project #{$salesproject->id} couldn't be updated, last midgard error: " . midcom_connection::get_error_string() . "\n";
            _midcom_stop_request();
        }
    }
}
echo "Done.\n";
echo "</pre>";
ob_start();
?>