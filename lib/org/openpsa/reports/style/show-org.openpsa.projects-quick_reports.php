<?php
require_once 'Calendar/Month.php';
$this_month = new Calendar_Month(date('Y', time()), date('m', time()));
$prev_month = $this_month->prevMonth('object');
$next_month = $this_month->nextMonth('object');
?>
<ul>
    <li>
        <?php
        // Invoiceable hours in last month
        $report_params  = '?org_openpsa_reports_query_data[start]=' . $this_month->getTimestamp();
        $report_params .= '&org_openpsa_reports_query_data[end]=' . $next_month->getTimestamp();
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
        $report_params  = '?org_openpsa_reports_query_data[start]=' . $prev_month->getTimestamp();
        $report_params .= '&org_openpsa_reports_query_data[end]=' . $this_month->getTimestamp();
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