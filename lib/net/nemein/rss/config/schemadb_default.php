<?php
return [
    'default' => [
        'description' => 'feed',
        'l10n_db'     => 'net.nemein.rss',
        'fields'      => [
            'title' => [
                'title' => 'feed title',
                'storage' => 'title',
                'required' => true,
                'type' => 'text',
                'widget' => 'text',
                'index_method' => 'title',
            ],
            'url' => [
                'title' => 'feed url',
                'storage' => 'url',
                'required' => true,
                'type' => 'text',
                'widget' => 'text',
            ],
            'keepremoved' => [
                'title'   => 'keep removed items',
                'storage' => 'keepremoved',
                'type'    => 'boolean',
                'widget'  => 'checkbox',
            ],
            'autoapprove' => [
                'title'   => 'approve new items automatically',
                'storage' => 'autoapprove',
                'type'    => 'boolean',
                'widget'  => 'checkbox',
                'hidden' => !midcom::get()->config->get('metadata_approval'),
            ],
            'defaultauthor' => [
                'title' => 'default author for items',
                'storage' => 'defaultauthor',
                'type' => 'select',
                'type_config' => [
                     'require_corresponding_option' => false,
                     'options' => [],
                ],
                'widget' => 'autocomplete',
                'widget_config' => [
                    'class' => 'midcom_db_person',
                    'titlefield' => 'name',
                    'id_field' => 'id',
                    'searchfields' => [
                        'firstname',
                        'lastname',
                        'username',
                    ],
                    'result_headers' => [
                        ['name' => 'email'],
                    ],
                    'orders' => [],
                    'creation_mode_enabled' => true,
                    'creation_handler' => midcom_connection::get_url('self') . "__mfa/asgard/object/create/chooser/midgard_person/",
                    'creation_default_key' => 'lastname',
                ],
            ],
            'forceauthor' => [
                'title'   => 'always use the default author',
                'storage' => 'forceauthor',
                'type'    => 'boolean',
                'widget'  => 'checkbox',
            ],
        ]
    ]
];
