'config' => [
    'description' => 'Default Configuration Schema',
    'fields' => [
        'sort_order' => [
            'title' => 'sort_order',
            'storage' => [
                'location' => 'configuration',
                'domain' => 'net.nehmer.static',
                'name' => 'sort_order',
            ],
            'type' => 'select',
            'type_config' => [
                'options' => [
                    '' => 'default setting',
                    'score' => 'score',
                    'created' => 'created',
                    'reverse created' => 'reverse created',
                    'name' => 'name',
                    'reverse name' => 'reverse name',
                    'reverse score' => 'reverse score',
                    'revised' => 'revised',
                    'reverse revised' => 'reverse revised',
                    'calendar' => 'calendar',
                    'reverse calendar' => 'reverse calendar'
                ],
            ],
            'widget' => 'select',
        ],
        'hide_navigation' => [
            'title' => 'hide_navigation',
            'storage' => [
                'location' => 'configuration',
                'domain' => 'net.nehmer.static',
                'name' => 'hide_navigation',
            ],
            'type' => 'select',
            'type_config' => [
                'options' => [
                    '' => 'default setting',
                    '1' => 'yes',
                    '0' => 'no',
                ],
            ],
            'widget' => 'select',
            'end_fieldset' => '',
        ],
        'autoindex' => [
            'title' => 'autoindex',
            'helptext' => 'autoindex-notes',
            'storage' => [
                'location' => 'configuration',
                'domain' => 'net.nehmer.static',
                'name' => 'autoindex',
            ],
            'type' => 'select',
            'type_config' => [
                'options' => [
                    '' => 'default setting',
                    '1' => 'yes',
                    '0' => 'no',
                ],
            ],
            'widget' => 'select',
            'start_fieldset' => [
                'title' => 'autoindex settings'
            ],
        ],

        'show_blobs_in_autoindex' => [
            'title' => 'show blobs in autoindex',
            'storage' => [
                'location' => 'configuration',
                'domain' => 'net.nehmer.static',
                'name' => 'show_blobs_in_autoindex',
            ],
            'type' => 'select',
            'type_config' => [
                'options' => [
                    '' => 'default setting',
                    '1' => 'yes',
                    '0' => 'no',
                ],
            ],
            'widget' => 'select',
        ],

        'indexinnav' => [
            'title' => 'indexinnav',
            'storage' => [
                'location' => 'configuration',
                'domain' => 'net.nehmer.static',
                'name' => 'indexinnav',
            ],
            'type' => 'select',
            'type_config' => [
                'options' => [
                    '' => 'default setting',
                    '1' => 'yes',
                    '0' => 'no',
                ],
            ],
            'widget' => 'select',
            'end_fieldset' => '',
        ],

        'schemadb' => [
            'title' => 'schemadb',
            'storage' => [
                'location' => 'configuration',
                'domain' => 'net.nehmer.static',
                'name' => 'schemadb',
            ],
            'type' => 'select',
            'type_config' => [
                'options' => array_merge(['' => 'default setting'], midcom_baseclasses_components_configuration::get('net.nehmer.static', 'config')->get('schemadbs')),
            ],
            'widget' => 'select',
            'start_fieldset' => [
                'title' => 'schema settings'
            ],
        ],
        'simple_name_handling' => [
            'title' => 'simple_name_handling',
            'storage' => [
                'location' => 'configuration',
                'domain' => 'net.nehmer.static',
                'name' => 'simple_name_handling',
            ],
            'type' => 'select',
            'type_config' => [
                'options' => [
                    '' => 'default setting',
                    '1' => 'yes',
                    '0' => 'no',
                ],
            ],
            'widget' => 'select',
            'end_fieldset' => '',
        ],
    ],
],
