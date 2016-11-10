<?php
//If we need to do any specific mangling to the report data etc, we do it here.
$query = $data['query_data'];
if (empty($data['title'])) {
    $formatter = $data['l10n']->get_formatter();
    $data['title'] = sprintf($data['l10n']->get('basic report for %s - %s'), $formatter->date($query['start']), $formatter->date($query['end']));
}
?>
<!DOCTYPE html>
<html lang="<?php echo midcom::get()->i18n->get_current_language(); ?>">
    <head>
        <meta charset="UTF-8">
        <title>OpenPSA - &(data['title']);</title>
        <link rel="stylesheet" type="text/css" href="<?php echo MIDCOM_STATIC_URL; ?>/org.openpsa.reports/common.css" />
        <link rel="stylesheet" type="text/css" href="<?php echo MIDCOM_STATIC_URL; ?>/org.openpsa.reports/projects.css" />
        <script type="text/javascript" src="<?php echo MIDCOM_STATIC_URL; ?>/org.openpsa.core/table2csv.js"></script>
    </head>
    <body>