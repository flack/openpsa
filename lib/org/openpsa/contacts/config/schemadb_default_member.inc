'default' => array
(
    'description' => 'member',
    'l10n_db' => 'org.openpsa.contacts',
    'fields'  => array
    (
        'title' => array
        (
            'title'    => 'job title',
            'storage'  => 'extra',
            'type'     => 'text',
            'widget'   => 'text',
        ),
        'organization' => array
        (
            'title'    => 'organization',
            'storage'  => 'gid',
            'type'     => 'text',
            'widget'   => 'text',
            'hidden'   => true,
        ),
        'person' => array
        (
            'title'    => 'person',
            'storage'  => 'uid',
            'type'     => 'text',
            'widget'   => 'text',
            'hidden'   => true,
        ),
    )
),