'name' => 'net.nemein.tag',
'icon' => 'net.nemein.tag/tag-16.png',
'purecode' => true,
'version' => '9.0.0',
'state' => 'stable',
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
'package.xml' => array
(
    'summary' => 'Tag library',
    'description' => 'Tag library',
    'maintainers' => array
    (
        'rambo' => array
        (
            'name' => 'Eero af Heurlin',
            'email' => 'eero.afheurlin@iki.fi',
            'role' => 'lead',
        ),
        'bergie' => array
        (
            'name' => 'Henri Bergius',
            'email' => 'henri.bergius@iki.fi',
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
            'handler' => 'net_nemein_tag_cron_clean',
            'recurrence' => MIDCOM_CRON_DAY,
        ),
    ),
),
