'name' => 'org.openpsa.reports',
'purecode' => false,
'privileges' => array(),
'class_mapping' => array
(
    'org_openpsa_query' => 'org_openpsa_reports_query_dba',
),
'version' => '9.1.0-dev',
'package.xml' => array
(
    'summary' => 'OpenPSA reporting module',
    'description' => 'Run reports on data from various other modules',
    'maintainers' => array
    (
        'rambo' => array
        (
            'name' => 'Eero af Heurlin',
            'email' => 'eero.afheurlin@iki.fi',
            'role' => 'lead',
        ),
        'flack' => array
        (
            'name' => 'Andreas Flack',
            'email' => 'flack@contentcontrol-berlin.de',
            'role' => 'developer',
        ),
    ),
    'dependencies' => array
    (
        'org.openpsa.core' => array(),
        'org.openpsa.helpers' => array(),
        'midcom.helper.datamanager2' => array(),
        'midcom' => array(),
    ),
),
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
