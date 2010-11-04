'name' => 'midcom.services.at',
'icon' => 'stock-icons/16x16/stock_new-event.png',
'purecode' => true,
'privileges' => array(),
'class_definitions' => array
(
    'midcom_dba_classes.inc',
),
'version' => '8.09.9',
'state' => 'stable',
'package.xml' => array
(
    'summary' => 'Library for stored queries',
    'description' => 'AT service',
    'maintainers' => array
    (
        'rambo' => array
        (
            'name' => 'Eero af Heurlin',
            'email' => 'eero.afheurlin@iki.fi',
            'role' => 'lead',
        ),
    ),
    'dependencies' => array
    (
        'midcom' => array(),
    ),
),
'customdata' => array
(
    'midcom.services.cron' => array
    (
        array
        (
            'handler' => 'midcom_services_at_cron_check',
            'recurrence' => MIDCOM_CRON_MINUTE,
        ),
        array
        (
            'handler' => 'midcom_services_at_cron_clean',
            'recurrence' => MIDCOM_CRON_DAY,
        ),
    ),
    'skip_cache_generation' => true,
),
