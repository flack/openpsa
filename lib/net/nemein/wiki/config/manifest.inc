'name' => 'net.nemein.wiki',
'icon' => 'net.nemein.wiki/wiki.png',
'purecode' => false,
'version' => '9.1.0-dev',
'state' => 'stable',
'privileges' => array(),
'class_mapping' => array
(
    'net_nemein_wiki_link' => 'net_nemein_wiki_link_dba',
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
        'midcom.admin.rcs' => array(),
        'org.openpsa.widgets' => array(),
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
),
