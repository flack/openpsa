'name' => 'net.nemein.wiki',
'icon' => 'pencil',
'purecode' => false,
'privileges' => [],
'class_mapping' => [
    'net_nemein_wiki_link' => 'net_nemein_wiki_link_dba',
],
'description' => 'Wiki online notebook',
'customdata' => [
    'org.openpsa.notifications' => [
        'page_updated' => [
            'default' => 'email',
        ],
    ],
],
