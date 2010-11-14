<?php
$hour =& $data['current_row']['hour'];
$task =& $data['current_row']['task'];
$report =& $data['report'];
$person =& $data['current_row']['person'];
$query_data =& $data['query_data'];
$group =& $data['current_group'];
$weekly_data =& $report['raw_results']['weekly_report_data'];
$weekly_data_group =& $group['weekly_report_data'];

$data['current_row']['customer'] = false;
if ($task->customer)
{
    $data['current_row']['customer'] =& org_openpsa_reports_handler_projects_report::_get_cache('groups', $task->customer, $data);
}

if ($hour->invoiceable)
{
    $total =& $weekly_data['invoiceable_total'];
    $customers =& $weekly_data['invoiceable_customers'];
    $total_by_customer =& $weekly_data['invoiceable_total_by_customer'];
    $group_total =& $weekly_data_group['invoiceable_total'];
    $group_customers =& $weekly_data_group['invoiceable_customers'];
    $group_total_by_customer =& $weekly_data_group['invoiceable_total_by_customer'];
}
else
{
    $total =& $weekly_data['uninvoiceable_total'];
    $customers =& $weekly_data['uninvoiceable_customers'];
    $total_by_customer =& $weekly_data['uninvoiceable_total_by_customer'];
    $group_total =& $weekly_data_group['uninvoiceable_total'];
    $group_customers =& $weekly_data_group['uninvoiceable_customers'];
    $group_total_by_customer =& $weekly_data_group['uninvoiceable_total_by_customer'];
}

$total += $hour->hours;
$group_total += $hour->hours;

if ($data['current_row']['customer'])
{
    $customers[$task->customer] =& $data['current_row']['customer'];
    $group_customers[$task->customer] =& $data['current_row']['customer'];

    if (!array_key_exists($task->customer, $total_by_customer))
    {
        $total_by_customer[$task->customer] = 0;
    }
    $total_by_customer[$task->customer] += $hour->hours;

    if (!array_key_exists($task->customer, $group_total_by_customer))
    {
        $group_total_by_customer[$task->customer] = 0;
    }
    $group_total_by_customer[$task->customer] += $hour->hours;
}

?>