'name' => 'midcom.services.at',
'icon' => 'clock-o',
'purecode' => true,
'privileges' => [],
'class_mapping' => [
    'midcom_services_at_entry_db' => 'midcom_services_at_entry_dba',
],
'description' => 'AT service',
'customdata' => [
    'midcom.services.cron' => [
        [
            'handler' => 'midcom_services_at_cron_check',
            'recurrence' => MIDCOM_CRON_MINUTE,
        ], [
            'handler' => 'midcom_services_at_cron_clean',
            'recurrence' => MIDCOM_CRON_DAY,
        ],
    ],
],
