'name' => 'org.openpsa.reports',
'purecode' => false,
'privileges' => [],
'class_mapping' => [
    'org_openpsa_query' => 'org_openpsa_reports_query_dba',
],
'description' => 'Run reports on data from various other modules',
'customdata' => [
    'midcom.services.cron' => [
    	[
            'handler' => 'org_openpsa_reports_cron_clearold',
            'recurrence' => MIDCOM_CRON_DAY,
        ],
    ],
],
