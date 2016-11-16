'name' => 'org.openpsa.reports',
'purecode' => false,
'privileges' => array(),
'class_mapping' => array
(
    'org_openpsa_query' => 'org_openpsa_reports_query_dba',
),
'version' => '9.1.0-dev',
'description' => 'Run reports on data from various other modules',
'customdata' => array
(
    'midcom.services.cron' => array
    (
        array
        (
            'handler' => 'org_openpsa_reports_cron_clearold',
            'recurrence' => MIDCOM_CRON_DAY,
        ),
    ),
),
'state' => 'stable',
