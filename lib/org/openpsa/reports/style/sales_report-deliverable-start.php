<?php
$query = $data['query_data'];
$title = sprintf($data['l10n']->get('sales report %s - %s'), strftime('%x', $data['start']), strftime('%x', $data['end']));
?>
<!DOCTYPE html>
<html lang="<?php echo midcom::get()->i18n->get_current_language(); ?>">
    <head>
        <meta charset="UTF-8">
        <title>OpenPSA - &(title);</title>
        <link rel="stylesheet" type="text/css" href="<?php echo MIDCOM_STATIC_URL; ?>/org.openpsa.reports/common.css" />
        <link rel="stylesheet" type="text/css" href="<?php echo MIDCOM_STATIC_URL; ?>/org.openpsa.sales/sales.css" />
        <script type="text/javascript" src="<?php echo MIDCOM_STATIC_URL; ?>/org.openpsa.core/table2csv.js"></script>
        <?php midcom::get()->head->print_head_elements(); ?>
    </head>
    <body>
        <div id="content-text" class="org_openpsa_reports_report org_openpsa_reports_deliverable">
            <div class="header">
                <?php midcom_show_style('projects_report-basic-header-logo'); ?>
                <h1>&(title);</h1>
            </div>