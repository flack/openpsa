'name' => 'net.nehmer.comments',
'icon' => 'comments-o',
'class_mapping' => [
    'net_nehmer_comments_comment_db' => 'net_nehmer_comments_comment'
],

'watches' => [
    [
        'classes' => [],
        'operations' => MIDCOM_OPERATION_DBA_DELETE,
    ],
],
'description' => 'Page commenting tool',
'customdata' => [
    'midcom.services.cron' => [
	    [
            'handler' => 'net_nehmer_comments_cron_atom',
            'recurrence' => MIDCOM_CRON_HOUR,
        ],
    ],
    'org.openpsa.notifications' => [
        'comment_posted' => [
            'default' => 'email',
        ],
        'report_abuse' => [
            'default' => 'email',
        ],
    ],
],
