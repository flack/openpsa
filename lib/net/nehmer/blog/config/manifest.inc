'name' => 'net.nehmer.blog',
'icon' => 'net.nehmer.blog/news-listing.png',
'version' => '9.1.0',
'state' => 'stable',
'class_mapping' => array
(
    'net_nehmer_blog_link' => 'net_nehmer_blog_link_dba',
),
'description' => 'System for displaying and managing news posts',
'watches' => array
(
    array
    (
        'classes' => array('midcom_db_article'),
        'operations' => MIDCOM_OPERATION_DBA_DELETE,
    ),
),
