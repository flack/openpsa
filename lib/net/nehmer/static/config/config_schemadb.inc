'config' => array
(
    'description' => 'Default Configuration Schema',
    'fields' => array
    (
        'sort_order' => array
        (
            'title' => 'sort_order',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'net.nehmer.static',
                'name' => 'sort_order',
            ),
            'type' => 'select',
            'type_config' => array
            (
                'options' => array
                (
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
                ),
            ),
            'widget' => 'select',
        ),

        'enable_ajax_editing' => array
        (
            'title' => 'enable_ajax_editing',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'net.nehmer.static',
                'name' => 'enable_ajax_editing',
            ),
            'type' => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    '' => 'default setting',
                    '1' => 'yes',
                    '0' => 'no',
                ),
            ),
            'widget' => 'select',
            'hidden' => !midcom::get()->config->get('enable_ajax_editing'),
        ),

        'hide_navigation' => array
        (
            'title' => 'hide_navigation',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'net.nehmer.static',
                'name' => 'hide_navigation',
            ),
            'type' => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    '' => 'default setting',
                    '1' => 'yes',
                    '0' => 'no',
                ),
            ),
            'widget' => 'select',
            'end_fieldset' => '',
        ),
        'autoindex' => array
        (
            'title' => 'autoindex',
            'helptext' => 'autoindex-notes',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'net.nehmer.static',
                'name' => 'autoindex',
            ),
            'type' => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    '' => 'default setting',
                    '1' => 'yes',
                    '0' => 'no',
                ),
            ),
            'widget' => 'select',
            'start_fieldset' => array
            (
                'title' => 'autoindex settings'
            ),

        ),

        'show_blobs_in_autoindex' => array
        (
            'title' => 'show_blobs_in_autoindex',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'net.nehmer.static',
                'name' => 'show_blobs_in_autoindex',
            ),
            'type' => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    '' => 'default setting',
                    '1' => 'yes',
                    '0' => 'no',
                ),
            ),
            'widget' => 'select',
        ),

        'indexinnav' => array
        (
            'title' => 'indexinnav',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'net.nehmer.static',
                'name' => 'indexinnav',
            ),
            'type' => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    '' => 'default setting',
                    '1' => 'yes',
                    '0' => 'no',
                ),
            ),
            'widget' => 'select',
            'end_fieldset' => '',
        ),

        'schemadb' => array
        (
            'title' => 'schemadb',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'net.nehmer.static',
                'name' => 'schemadb',
            ),
            'type' => 'select',
            'type_config' => array
            (
                'options' => array_merge(['' => 'default setting'], midcom_baseclasses_components_configuration::get('net.nehmer.static', 'config')->get('schemadbs')),
            ),
            'widget' => 'select',
            'start_fieldset' => array
            (
                'title' => 'schema settings'
            ),
        ),
        'auto_approve' => array
        (
            'title' => 'auto_approve',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'net.nehmer.static',
                'name' => 'auto_approve',
            ),
            'type' => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    '' => 'default setting',
                    '1' => 'yes',
                    '0' => 'no',
                ),
            ),
            'widget' => 'select',
        ),
        'simple_name_handling' => array
        (
            'title' => 'simple_name_handling',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'net.nehmer.static',
                'name' => 'simple_name_handling',
            ),
            'type' => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    '' => 'default setting',
                    '1' => 'yes',
                    '0' => 'no',
                ),
            ),
            'widget' => 'select',
            'end_fieldset' => '',
        ),
    ),
),
