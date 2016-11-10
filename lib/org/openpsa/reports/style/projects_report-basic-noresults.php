<?php
//If we need to do any specific mangling etc, we do it here.
$query = $data['query_data'];
$report = $data['report'];
if (empty($report['title'])) {
    $formatter = $data['l10n']->get_formatter();
    $report['title'] = sprintf($data['l10n']->get('basic report for %s - %s'), $formatter->date($query['start']), $formatter->date($query['end']));
}
?>
<!DOCTYPE html>
<html lang="<?php echo midcom::get()->i18n->get_current_language(); ?>">
    <head>
        <meta charset="UTF-8">
        <title>OpenPSA - &(report['title']);</title>
        <link rel="stylesheet" type="text/css" href="<?php echo MIDCOM_STATIC_URL; ?>/org.openpsa.reports/common.css" />
        <link rel="stylesheet" type="text/css" href="<?php echo MIDCOM_STATIC_URL; ?>/org.openpsa.reports/projects.css" />
    </head>
    <body>
        <div class="error">
            <h1><?php echo $data['l10n']->get('no results'); ?></h1>
            <p><?php echo $data['l10n']->get('no results found matching the report criteria'); ?></p>
        </div>
    </body>
</html>