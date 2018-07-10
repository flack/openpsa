'name' => 'net.nemein.tag',
'icon' => 'tags',
'purecode' => true,
'privileges' => [],
'class_mapping' => [
    'net_nemein_tag' => 'net_nemein_tag_tag_dba',
    'net_nemein_tag_link' => 'net_nemein_tag_link_dba',
],
'watches' => [
    [
        'classes' => [],
        'operations' => MIDCOM_OPERATION_DBA_DELETE,
    ],
],
'description' => 'Tag library',
'customdata' => [
    'midcom.services.cron' => [
        [
            'handler' => 'net_nemein_tag_cron_clean',
            'recurrence' => MIDCOM_CRON_DAY,
        ],
    ],
],
