<?php
$date = new DateTime(date('Y-m-1'));
$this_month = $date->format('U');

$date->modify('+1 month');
$next_month = $date->format('U');

$date->modify('-2 months');
$prev_month = $date->format('U');

$invoiceable_this_month = $invoiceable_prev_month = [
    'org_openpsa_reports_query_data' => [
        'style' => 'builtin:basic',
        'invoiceable_filter' => 1,
        'mimetype' => 'text/html',
        'resource' => 'all',
        'task' => 'all'
    ]
];
$invoiceable_this_month['org_openpsa_reports_query_data']['start'] = $this_month;
$invoiceable_this_month['org_openpsa_reports_query_data']['end'] = $next_month;
$invoiceable_prev_month['org_openpsa_reports_query_data']['start'] = $prev_month;
$invoiceable_prev_month['org_openpsa_reports_query_data']['end'] = $this_month;
?>
<ul>
    <li>
        <a href="&(data['report_prefix']);get/?<?php echo http_build_query($invoiceable_this_month); ?>" target="_blank">
            <?php echo $data['l10n']->get('invoiceable hours this month'); ?>
        </a>
    </li>
    <li>
        <a href="&(data['report_prefix']);get/?<?php echo http_build_query($invoiceable_prev_month); ?>" target="_blank">
            <?php echo $data['l10n']->get('invoiceable hours last month'); ?>
        </a>
    </li>
    <li>
        <a href="&(data['report_prefix']);">
            <?php echo $data['l10n']->get('define custom report'); ?>
        </a>
    </li>
</ul>