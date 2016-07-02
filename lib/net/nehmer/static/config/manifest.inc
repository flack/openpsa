'name' => 'net.nehmer.static',
'version' => '9.1.0-dev',
'icon' => 'stock-icons/16x16/document.png',
'state' => 'stable',
'class_mapping' => array
(
    'net_nehmer_static_link' => 'net_nehmer_static_link_dba',
),
'package.xml' => array
(
    'summary' => 'Static article content display',
    'description' => 'Static content display component emulating regular directory of HTML files',
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
            'role' => 'lead',
            'active' => 'yes',
        ),
        'netblade' => array
        (
            'name' => 'Oskari Kokko',
            'email' => 'oskari.kokko@iki.fi',
            'role' => 'lead',
            'active' => 'yes',
        ),
    ),
    'dependencies' => array
    (
        'midcom.helper.datamanager2' => array(),
        'midcom' => array(),
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
