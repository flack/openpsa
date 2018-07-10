'name' => 'net.nemein.tag',
'icon' => 'tags',
'purecode' => true,
'privileges' => array(),
'class_mapping' => array
(
    'net_nemein_tag' => 'net_nemein_tag_tag_dba',
    'net_nemein_tag_link' => 'net_nemein_tag_link_dba',
),
'watches' => array
(
    array
    (
        'classes' => array(),
        'operations' => MIDCOM_OPERATION_DBA_DELETE,
    ),
),
'description' => 'Tag library',
'customdata' => array
(
    'midcom.services.cron' => array
    (
        array
        (
            'handler' => 'net_nemein_tag_cron_clean',
            'recurrence' => MIDCOM_CRON_DAY,
        ),
    ),
),
