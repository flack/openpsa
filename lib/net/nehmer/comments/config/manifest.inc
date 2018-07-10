'name' => 'net.nehmer.comments',
'icon' => 'comments-o',
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
'description' => 'Page commenting tool',
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
