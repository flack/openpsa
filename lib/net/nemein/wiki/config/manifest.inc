'name' => 'net.nemein.wiki',
'icon' => 'net.nemein.wiki/wiki.png',
'purecode' => false,
'version' => '8.09.9',
'state' => 'stable',
'privileges' => array(),
'class_definitions' => array
(
    'midcom_dba_classes.inc',
),
'package.xml' => array
(
    'summary' => 'Wiki',
    'description' => 'Wiki online notebook',
    'maintainers' => array
    (
        'bergie' => array
        (
            'name' => 'Henri Bergius',
            'email' => 'henri.bergius@iki.fi',
            'role' => 'lead',
        ),
        'rambo' => array
        (
            'name' => 'Eero af Heurlin',
            'email' => 'eero.afheurlin@iki.fi',
            'role' => 'lead',
        ),
    ),
    'dependencies' => array
    (
        'midcom.helper.datamanager2' => array(),
        'de.bitfolge.feedcreator' => array(),
        'no.bergfald.rcs' => array(),
        'org.openpsa.contactwidget' => array(),
        'org.openpsa.notifications' => array(),
        'org.openpsa.mail' => array(),
        'net.nemein.tag' => array(),
        'midcom' => array(),
    ),
),
'customdata' => array
(
    'org.openpsa.notifications' => array
    (
        'page_updated' => array
        (
            'default' => 'email',
        ),
    ),
    'skip_cache_generation' => true,
),
