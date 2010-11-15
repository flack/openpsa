<?php
set_time_limit(50000);
ini_set('memory_limit', "800M");
while(@ob_end_flush());
echo "<pre>\n";

$qb = org_openpsa_invoices_invoice_dba::new_query_builder();
$qb->add_constraint('date', '=', 0);
$invoices = $qb->execute();

foreach ($invoices as $invoice)
{
    $invoice->date = $invoice->metadata->created;
    $invoice->update();
    echo "Invoice #{$invoice->id} updated\n";
    flush();
}

//get all invoices with no invoice_items
$mc = new midgard_collector('org_openpsa_invoice_item' , 'metadata.deleted' , false);
$mc->set_key_property('id');
$mc->add_value_property('invoice');
$mc->execute();
$keys = $mc->list_keys();
foreach($keys as $key => $empty)
{
    $keys[$key] = $mc->get_subkey($key ,'invoice');
}
$qb_invoices = org_openpsa_invoices_invoice_dba::new_query_builder();
if(!empty($keys))
{
    $qb_invoices->add_constraint('id' , 'NOT IN' , $keys);
}

$invoices = $qb_invoices->execute();
foreach($invoices as $invoice)
{
    echo "Starting invoice_item-migration for invoice(" . $invoice->number .") - id: " . $invoice->id ." \n";
    $old_invoice_sum = round($invoice->sum , 2);
    echo "Invoice_sum_before : " . $old_invoice_sum ." \n";
    //first lets recalculate the hour_reports etc.
    //this will create the invoice items for corresponding hour_reports
    $invoice->_recalculate_invoice_items(array(), true);
    //this gets the invoice sum by the invoice-items
    $new_invoice_sum = $invoice->get_invoice_sum();
    echo "Calculated sum by invoice_items : " . $new_invoice_sum . "\n";

    //if there is a difference between the auto-calculated & the old one
    //it will create a new invoice_item to cover up the difference
    if($old_invoice_sum != $new_invoice_sum)
    {
        $price = $old_invoice_sum - $new_invoice_sum;
        //create an invoice_item to cover up any possible difference
        $migration_invoice_item = new org_openpsa_invoices_invoice_item_dba();
        $migration_invoice_item->invoice = $invoice->id;
        $migration_invoice_item->units = 1;
        $migration_invoice_item->pricePerUnit = $price;
        $migration_invoice_item->description = $_MIDCOM->i18n->get_string('Conversion to invoice items difference' , 'org.openpsa.invoices' ) ." - " . date("d.m.Y");
        $migration_invoice_item->create();

        echo "Created invoice_item to cover up the difference with pricePerUnit:" . $price . " and units: 1 \n";
        echo "New Calculated sum by invoice_items: " . $invoice->get_invoice_sum() . " \n";
        //this shouldn't happen but if there is some kind of false calculation
        // it will delete the false created invoice_items an break the loop
        if($invoice->get_invoice_sum() != $old_invoice_sum )
        {
            echo "\n" . $price + $new_invoice_sum ."\n";
            echo "\n\n False calculated invoice_sum ! - stopping convert script \n\n";
            $migration_invoice_item->delete();
            $qb_delete = org_openpsa_invoices_invoice_item_dba::new_query_builder();
            $qb_delete->add_constraint('invoice' , '=' , $invoice->id);
            $items_delete = $qb_delete->execute();
            foreach($items_delete as $item)
            {
                $item->skip_invoice_update = true;
                $item->delete();
            }
            break;
        }
    }
    echo "Invoice_sum_after :" . round($invoice->sum , 2) . "\n\n";
}

echo "Done.\n";
echo "</pre>";
flush();
ob_start();
?>