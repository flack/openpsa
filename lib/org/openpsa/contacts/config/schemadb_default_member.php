<?php
return [
    'default' => [
        'description' => 'member',
        'l10n_db' => 'org.openpsa.contacts',
        'fields'  => [
            'title' => [
                'title'    => 'job title',
                'storage'  => 'extra',
                'type'     => 'text',
                'widget'   => 'text',
            ],
            'organization' => [
                'title'    => 'organization',
                'storage'  => 'gid',
                'type'     => 'text',
                'widget'   => 'text',
                'hidden'   => true,
            ],
            'person' => [
                'title'    => 'person',
                'storage'  => 'uid',
                'type'     => 'text',
                'widget'   => 'text',
                'hidden'   => true,
            ],
        ]
    ]
];