<?php
$date = new DateTime(date('Y-m-1'));
$this_month = $date->format('U');

$date->modify('+1 month');
$next_month = $date->format('U');

$date->modify('-2 months');
$prev_month = $date->format('U');
?>
<ul>
    <li>
        <?php
        // Invoiceable hours in last month
        $report_params  = '?org_openpsa_reports_query_data[start]=' . $this_month;
        $report_params .= '&org_openpsa_reports_query_data[end]=' . $next_month;
        $report_params .= '&org_openpsa_reports_query_data[style]=builtin:basic';
        $report_params .= '&org_openpsa_reports_query_data[invoiceable_filter]=1';
        $report_params .= '&org_openpsa_reports_query_data[mimetype]=text/html';
        $report_params .= '&org_openpsa_reports_query_data[resource]=all';
        $report_params .= '&org_openpsa_reports_query_data[task]=all';
        ?>
        <a href="&(data['report_prefix']);get/&(report_params);" target="_blank">
            <?php echo $data['l10n']->get('invoiceable hours this month'); ?>
        </a>
    </li>
    <li>
        <?php
        // Invoiceable hours in last month
        $report_params  = '?org_openpsa_reports_query_data[start]=' . $prev_month;
        $report_params .= '&org_openpsa_reports_query_data[end]=' . $this_month;
        $report_params .= '&org_openpsa_reports_query_data[style]=builtin:basic';
        $report_params .= '&org_openpsa_reports_query_data[invoiceable_filter]=1';
        $report_params .= '&org_openpsa_reports_query_data[mimetype]=text/html';
        $report_params .= '&org_openpsa_reports_query_data[resource]=all';
        $report_params .= '&org_openpsa_reports_query_data[task]=all';
        ?>
        <a href="&(data['report_prefix']);get/&(report_params);" target="_blank">
            <?php echo $data['l10n']->get('invoiceable hours last month'); ?>
        </a>
    </li>
    <li>
        <a href="&(data['report_prefix']);">
            <?php echo $data['l10n']->get('define custom report'); ?>
        </a>
    </li>
</ul>