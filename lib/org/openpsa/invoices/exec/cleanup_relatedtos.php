<?php
//This script is meant to clean up some of the fallout of #1893, use at your own risk
$_MIDCOM->auth->require_admin_user();

set_time_limit(50000);
ini_set('memory_limit', "800M");
while(@ob_end_flush());
echo "<pre>\n";

$qb = org_openpsa_invoices_invoice_dba::new_query_builder();
$invoices = $qb->execute();
$total = 0;

foreach ($invoices as $invoice)
{
    $qb2 = org_openpsa_relatedto_dba::new_query_builder();
    $qb2->add_constraint('fromGuid', '=', $invoice->guid);
    $qb2->add_constraint('toClass', '=', 'org_openpsa_sales_salesproject_deliverable_dba');
    $rels = $qb2->execute();
  
    $no = sizeof($rels) - 1;
    if ($no < 1)
    {
        continue;
    }
    echo "Processing invoice " . $invoice->number . "\n";
    flush();
    foreach ($rels as $i => $rel)
    {
        if ($i == $no)
        {
            break;
        }
        $rel->delete();
    }
    echo "Deleted " . ($no) . " links\n";
    flush();
    $total += $no;
}
echo "Deleted " . $total . " invoice->deliverable links.\n";


$qb = org_openpsa_projects_task_dba::new_query_builder();
$qb->add_constraint('agreement', '<>', 0);
$tasks = $qb->execute();
$total = 0;
foreach ($tasks as $task)
{
    $mc = new org_openpsa_relatedto_collector($task->guid, 'org_openpsa_invoices_invoice_dba');
    $mc->add_object_order('number', 'ASC');
    $invoices = $mc->get_related_objects();

    //Assume that the first invoice link is the correct one
    $correct = array_shift($invoices);

    $guids = array();
    foreach ($invoices as $invoice)
    {
        $guids[] = $invoice->guid;
    }

    if (empty($guids))
    {
        continue;
    }

    $rel_qb = org_openpsa_relatedto_dba::new_query_builder();
    $rel_qb->add_constraint('fromGuid', 'IN', $guids);
    $rel_qb->add_constraint('toGuid', '=', $task->guid);

    $rels = $rel_qb->execute();
  
    $no = sizeof($rels);
    if ($no < 1)
    {
        continue;
    }

    echo "Leave invoice " . $correct->number . " assigned to task " . $task->title . ", deleting " . $no . " links\n";
    flush();

    foreach ($rels as $i => $rel)
    {
        $rel->delete();
    }
    echo "Deleted " . ($no) . " links\n";
    flush();
    $total += $no;
}

echo "Deleted " . $total . " invoice->task links.\n";
echo "</pre>";
ob_start();
?>