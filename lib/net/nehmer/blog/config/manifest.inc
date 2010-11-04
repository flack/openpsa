'name' => 'net.nehmer.blog',
'icon' => 'net.nehmer.blog/news-listing.png',
'version' => '8.09.9.1',
'state' => 'stable',
'class_definitions' => array
(
    'midcom_dba_classes.inc',
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
        'de.bitfolge.feedcreator' => array(),
        'midcom.helper.datamanager2' => array(),
        'net.nehmer.comments' => array(),
        'org.openpsa.mail' => array(),
        'org.openpsa.qbpager' => array(),
        'net.nemein.rss' => array(),
        'XML_RPC' => array
        (
            'channel' => 'pear.php.net',
        ),
    ),
),
'customdata' => array
(
    'skip_cache_generation' => true,
),