<?php
midcom::get()->auth->require_admin_user();
midcom::get()->disable_limits();
ob_implicit_flush();
echo "<h1>Cleanup deliverable AT entries:</h1>\n";

$qb = org_openpsa_sales_salesproject_deliverable_dba::new_query_builder();
$qb->add_constraint('orgOpenpsaObtype', '=', org_openpsa_products_product_dba::DELIVERY_SUBSCRIPTION);
$qb->add_constraint('state', '>=', org_openpsa_sales_salesproject_deliverable_dba::STATE_ORDERED);

echo "<pre>\n";
foreach ($qb->execute() as $deliverable) {
    $mc = new org_openpsa_relatedto_collector($deliverable->guid, midcom_services_at_entry_dba::class);
    $mc->add_object_order('start', 'DESC');
    $at_entries = $mc->get_related_objects();

    if (count($at_entries) < 2) {
        continue;
    }

    echo "Removing duplicate AT entries for deliverable #{$deliverable->id} " . $deliverable->title . "\n";

    $first = true;
    foreach ($at_entries as $entry) {
        if ($first) {
            $first = false;
            echo "Keeping entry for " . strftime('%x %X', $entry->start) . "\n";
            continue;
        }

        echo "Deleting entry for " . strftime('%x %X', $entry->start) . "\n";
        $entry->delete();
    }
}
?>
</pre>
<p>All done</p>
