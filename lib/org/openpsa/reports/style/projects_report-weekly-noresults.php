<?php
//If we need to do any specific mangling etc, we do it here.
$query =& $data['query_data'];
$report =& $data['report'];
if (   !array_key_exists('title', $report)
    || empty($report['title']))
{
    $report['title'] = sprintf($data['l10n']->get('weekly report for %s - %s'), strftime('%x', $query['start']), strftime('%x', $query['end']));
}

if (empty($query['skip_html_headings']))
{
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN""http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="" lang="">
    <head>
        <title>OpenPSA - &(report['title']);</title>
        <link rel="stylesheet" type="text/css" href="<?php echo MIDCOM_STATIC_URL; ?>/org.openpsa.reports/common.css" />
        <link rel="stylesheet" type="text/css" href="<?php echo MIDCOM_STATIC_URL; ?>/org.openpsa.reports/projects.css" />
    </head>
    <body>
<?php
}
if (empty($query['resource_expanded']))
{
?>
        <div class="error">
            <h1><?php echo $data['l10n']->get('no results'); ?></h1>
            <p><?php echo $data['l10n']->get('no results found matching the report criteria'); ?></p>
        </div>
        <!--
        <div class="debug">
          <h1>Query data</h1>
          <pre><?php print_r($query); ?></pre>
        </div>
        -->
<?php
}
else
{
    /* TODO: Iterate trough $query['resource_expanded'] and draw empty tables for each person */
}
if (empty($query['skip_html_headings']))
{
?>
    </body>
</html>
<?php
}
?>