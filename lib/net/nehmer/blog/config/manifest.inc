'name' => 'net.nehmer.blog',
'icon' => 'net.nehmer.blog/news-listing.png',
'version' => '9.1.0-dev',
'state' => 'stable',
'class_mapping' => array
(
    'net_nehmer_blog_link' => 'net_nehmer_blog_link_dba',
),
'package.xml' => array
(
    'summary' => 'Weblog system',
    'description' => 'System for displaying and managing news posts',
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
        'adrenalin' => array
        (
            'name' => 'Arttu Manninen',
            'email' => 'arttu@kaktus.cc',
            'role' => 'developer',
            'active' => 'yes',
        ),
    ),
    'dependencies' => array
    (
        'midcom.helper.datamanager2' => array(),
        'net.nehmer.comments' => array(),
        'org.openpsa.mail' => array(),
        'org.openpsa.qbpager' => array(),
        'net.nemein.rss' => array(),
    ),
),
'watches' => array
(
    array
    (
        'classes' => array('midcom_db_article'),
        'operations' => MIDCOM_OPERATION_DBA_DELETE,
    ),
),
