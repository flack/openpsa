<?php
set_time_limit(50000);
ini_set('memory_limit', "800M");
while(@ob_end_flush());
echo "<pre>\n";

$qb = org_openpsa_invoices_invoice_hour_dba::new_query_builder();
$qb->add_constraint('invoice', '<>', 0);
$results = $qb->execute();


foreach($results as $i_report)
{
    $report = new org_openpsa_projects_hour_report_dba($i_report->hourReport);
    if ($report->invoice)
    {
        continue;
    }
    $report->invoice = $i_report->invoice;
    $report->_use_rcs = false;
    $report->update();

    echo "Hour report #{$report->id} updated\n";
    flush();
    $i_report->delete();
}

$qb = org_openpsa_invoices_invoice_dba::new_query_builder();
$invoices = $qb->execute();
foreach ($invoices as $invoice)
{
    $invoice->number = (int) substr($invoice->invoiceNumber, 1);
    $invoice->update();
    echo "Invoice #{$invoice->id} updated\n";
    flush();
}

echo "Done.\n";
echo "</pre>";
ob_start();
?>