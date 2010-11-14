<?php
//If we need to do any specific mangling to the report data etc, we do it here.
$query =& $data['query_data'];
$report =& $data['report'];
if (!is_array($report))
{
    $report = array();
}
if (   !array_key_exists('title', $report)
    || empty($report['title']))
{
    $report['title'] = sprintf($data['l10n']->get('invoice report %s - %s'), strftime('%x', $data['start']), strftime('%x', $data['end']));
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN""http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="" lang="">
    <head>
        <title>OpenPSA - &(report['title']);</title>
        <link rel="stylesheet" type="text/css" href="<?php echo MIDCOM_STATIC_URL; ?>/org.openpsa.reports/common.css" />
        <link rel="stylesheet" type="text/css" href="<?php echo MIDCOM_STATIC_URL; ?>/org.openpsa.invoices/invoices.css" />
        <?php $_MIDCOM->print_head_elements(); ?>
        <script type="text/javascript">
//copied from templates/OpenPsa2/ui.js, should be moved to a better place at some point...
var org_openpsa_jsqueue = {
    actions: [],
    add: function (action)
    {
        this.actions.push(action);
    },
    execute: function()
    {
        for (var i = 0; i < this.actions.length; i++)
        {
            this.actions[i]();
        }
        this.actions = [];
    }
};
        </script>
    </head>
    <body>
        <div class="org_openpsa_reports_report org_openpsa_invoices">
            <div class="header">
                <?php midcom_show_style('projects_report-basic-header-logo'); ?>
                <h1>&(report['title']);</h1>
            </div>

