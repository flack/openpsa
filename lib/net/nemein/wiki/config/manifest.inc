'name' => 'net.nemein.wiki',
'icon' => 'net.nemein.wiki/wiki.png',
'purecode' => false,
'privileges' => array(),
'class_mapping' => array
(
    'net_nemein_wiki_link' => 'net_nemein_wiki_link_dba',
),
'description' => 'Wiki online notebook',
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
