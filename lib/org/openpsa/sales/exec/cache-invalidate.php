<?php
midcom::get()->auth->require_admin_user();
midcom::get()->disable_limits();
ob_implicit_flush(true);
echo "<h1>Invalidating deliverable caches:</h1>\n";

$qb = org_openpsa_sales_salesproject_deliverable_dba::new_query_builder();
$qb->add_constraint('state', '>=', org_openpsa_sales_salesproject_deliverable_dba::STATE_ORDERED);

echo "<pre>\n";
foreach ($qb->execute() as $deliverable) {
    $start = microtime(true);
    echo "Update caches for deliverable #{$deliverable->id} " . $deliverable->title . "\n";
    echo "units: {$deliverable->units} uninvoiceable: {$deliverable->uninvoiceableUnits} price: {$deliverable->price} cost: {$deliverable->cost} invoiced: {$deliverable->invoiced}\n";
    $deliverable->calculate_price(false);
    org_openpsa_invoices_invoice_item_dba::update_deliverable($deliverable);
    $deliverable->update_units();
    $deliverable->update();

    $time_consumed = round(microtime(true) - $start, 2);
    echo "OK ({$time_consumed} secs), new units:\n";
    echo "units: {$deliverable->units} uninvoiceable: {$deliverable->uninvoiceableUnits} price: {$deliverable->price} cost: {$deliverable->cost} invoiced: {$deliverable->invoiced}\n";
}
?>
</pre>
<p>All done</p>
