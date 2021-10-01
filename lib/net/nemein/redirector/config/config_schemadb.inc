'config' => [
    'description' => 'Default Configuration Schema', /* This is a topic */
    'fields' => [
        /* redirection settings */
        'redirection_type' => [
            'title' => 'redirection type',
            'storage' => [
                'location' => 'configuration',
                'domain' => 'net.nemein.redirector',
                'name' => 'redirection_type',
            ],
            'type' => 'select',
            'type_config' => [
                'options' => [
                    'subnode' => 'first subfolder',
                    'node' => 'selected node',
                    'url' => 'url entered below',
                    'permalink' => 'permalink guid',
                ],
            ],
            'widget' => 'select',
            'start_fieldset' => [
                'title' => 'redirection settings',
            ],
        ],
        'redirection_url' => [
            'title' => 'redirection url',
            'storage' => [
                'location' => 'configuration',
                'domain' => 'net.nemein.redirector',
                'name' => 'redirection_url',
            ],
            'type' => 'text',
            'widget' => 'text',
        ],
        'redirection_node' => [
            'title' => 'redirection node',
            'storage' => [
                'location' => 'configuration',
                'domain' => 'net.nemein.redirector',
                'name' => 'redirection_node',
            ],
            'type' => 'select',
            'type_config' => [
                'options' => [],
                'allow_other' => true,
                'require_corresponding_option' => false,
            ],
            'widget' => 'autocomplete',
            'widget_config' => [
                'class'       => 'midcom_db_topic',
                'titlefield'  => 'extra',
                'id_field'     => 'guid',
                'searchfields' => [
                    'title',
                    'extra',
                    'component',
                    'name',
                ],
                'constraints' => [
                    [
                        'field' => 'extra',
                        'op' => '<>',
                        'value' => '',
                    ], [
                        'field' => 'up',
                        'op' => 'INTREE',
                        'value' => midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ROOTTOPIC)->id,
                    ],
                ],
                'result_headers' => [
                    [
                        'name' => 'name',
                    ], [
                        'name' => 'component',
                    ],
                ],
                'orders' => [
                    [
                        'title' => 'ASC',
                    ], [
                        'extra' => 'ASC',
                    ], [
                        'name' => 'ASC',
                    ],
                ],
            ],
        ],
        'redirection_guid' => [
            'title' => 'permalink guid',
            'storage' => [
                'location' => 'configuration',
                'domain' => 'net.nemein.redirector',
                'name' => 'redirection_guid',
            ],
            'type' => 'text',
            'widget' => 'text',
        ],
        'redirection_code' => [
            'title' => 'redirection http code',
            'storage' => [
                'location' => 'configuration',
                'domain' => 'net.nemein.redirector',
                'name' => 'redirection_code',
            ],
            'type' => 'select',
            'type_config' => [
                'options' => [
                    301 => '301 Moved Permanently',
                    302 => '302 Found',
                    410 => '410 Gone',
                ],
            ],
            'widget' => 'select',
            'default' => 301,
        ],
        'redirection_metatag' => [
            'title' => 'redirect with metatag',
            'storage' => [
                'location' => 'configuration',
                'domain' => 'net.nemein.redirector',
                'name' => 'redirection_metatag',
            ],
            'type' => 'select',
            'type_config' => [
                'options' => [
                    '' => 'default setting',
                    1 => 'yes',
                    0 => 'no',
                ],
            ],
            'widget' => 'select',
        ],
        'redirection_metatag_speed' => [
            'title' => 'metatag refresh speed',
            'storage' => [
                'location' => 'configuration',
                'domain' => 'net.nemein.redirector',
                'name' => 'redirection_metatag_speed',
            ],
            'type' => 'text',
            'widget' => 'text',
        ],
        'admin_redirection' => [
            'title' => 'redirect administrators',
            'storage' => [
                'location' => 'configuration',
                'domain' => 'net.nemein.redirector',
                'name' => 'admin_redirection',
            ],
            'type' => 'select',
            'type_config' => [
                'options' => [
                    '' => 'default setting',
                    1 => 'yes',
                    0 => 'no',
                ],
            ],
            'widget' => 'select',
            'end_fieldset' => 1,
        ],
    ],
],