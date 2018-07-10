'name' => 'midcom.services.at',
'icon' => 'clock-o',
'purecode' => true,
'privileges' => array(),
'class_mapping' => array
(
    'midcom_services_at_entry_db' => 'midcom_services_at_entry_dba',
),
'description' => 'AT service',
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
),
