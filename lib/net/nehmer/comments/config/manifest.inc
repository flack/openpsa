'name' => 'net.nehmer.comments',
'version' => '9.1.0-dev',
'icon' => 'stock-icons/16x16/stock-discussion.png',
'class_mapping' => array
(
    'net_nehmer_comments_comment_db' => 'net_nehmer_comments_comment'
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
    'summary' => 'Page commenting',
    'description' => 'Page commenting tool',
    'maintainers' => array
    (
        'torben' => array
        (
            'name' => 'Torben Nehmer',
            'email' => 'torben@nehmer.net',
            'role' => 'lead',
            'active' => 'no',
        ),
        'bergie' => array
        (
            'name' => 'Henri Bergius',
            'email' => 'henri.bergius@iki.fi',
            'role' => 'lead',
            'active' => 'yes',
        ),
        'xfade' => array
        (
            'name' => 'Niels Breet',
            'email' => 'nbreet@gmail.com',
            'role' => 'developer',
            'active' => 'yes',
        ),
        'jbergius' => array
        (
            'name' => 'Joonas Bergius',
            'email' => 'joonas.bergius@gmail.com',
            'role' => 'developer',
        ),
    ),
    'dependencies' => array
    (
        'midcom.helper.datamanager2' => array(),
        'org.openpsa.notifications' => array(),
        'org.openpsa.qbpager' => array(),
        'midcom' => array(),
    ),
),
'customdata' => array
(
    'midcom.services.cron' => array
    (
        array
        (
            'handler' => 'net_nehmer_comments_cron_atom',
            'recurrence' => MIDCOM_CRON_HOUR,
        ),
    ),
    'org.openpsa.notifications' => array
    (
        'comment_posted' => array
        (
            'default' => 'email',
        ),
        'report_abuse' => array
        (
            'default' => 'email',
        ),
    ),
),
'state' => 'stable',
