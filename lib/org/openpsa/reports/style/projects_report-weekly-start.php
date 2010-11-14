<?php
//If we need to do any specific mangling to the report data etc, we do it here.
$query =& $data['query_data'];
$report =& $data['report'];
if (   !array_key_exists('title', $report)
    || empty($report['title']))
{
    $report['title'] = sprintf($data['l10n']->get('weekly report for %s - %s'), strftime('%x', $query['start']), strftime('%x', $query['end']));
}

// Array to move weekly reports "private" data around
$report['raw_results']['weekly_report_data'] = array();
$weekly_data =& $report['raw_results']['weekly_report_data'];
$weekly_data['invoiceable_total'] = 0;
$weekly_data['uninvoiceable_total'] = 0;
$weekly_data['invoiceable_customers'] = array(); // Keyed by group id
$weekly_data['uninvoiceable_customers'] = array(); // Keyed by group id
$weekly_data['invoiceable_total_by_customer'] = array(); // Keyed by group id
$weekly_data['uninvoiceable_total_by_customer'] = array(); // Keyed by group id


//TODO: Check style context somehow (are we inside DL or not, and change output accordingly

if (   !isset($query['skip_html_headings'])
    || empty($query['skip_html_headings']))
{
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN""http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="" lang="">
    <head>
        <title>OpenPSA - &(report['title']);</title>
        <link rel="stylesheet" type="text/css" href="<?php echo MIDCOM_STATIC_URL; ?>/org.openpsa.reports/common.css" />
        <link rel="stylesheet" type="text/css" href="<?php echo MIDCOM_STATIC_URL; ?>/org.openpsa.reports/projects.css" />
        <script type="text/javascript" src="<?php echo MIDCOM_STATIC_URL; ?>/org.openpsa.core/table2csv.js"></script>
    </head>
    <body>
<?php
}
else
{
?>
<style type="text/css">
    @import url(<?php echo MIDCOM_STATIC_URL; ?>/org.openpsa.reports/common.css);
    @import url(<?php echo MIDCOM_STATIC_URL; ?>/org.openpsa.reports/projects.css);
</style>
<?php
}
?>