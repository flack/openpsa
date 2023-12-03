'default' => [
    'description' => 'user preferences',
    'l10n_db' => 'midgard.admin.asgard',
    'templates' => [
        'form' => \midcom\datamanager\template\form::class
    ],
    'fields' => [
        'tinymce_enabled' => [
            'title' => 'use tinymce editor for editing content',
            'storage' => [
                'location' => 'parameter',
                'domain' => 'midgard.admin.asgard:preferences',
                'name' => 'tinymce_enabled',
            ],
            'type' => 'select',
            'type_config' => [
                'options' => [
                    '' => 'default setting',
                    '1' => 'yes',
                    '0' => 'no',
                ],
            ],
            'widget' => 'radiocheckselect',
            'start_fieldset' => [
                'title' => 'editor options',
            ],
        ],
        'codemirror_enabled' => [
            'title' => 'use codemirror editor for editing code snippets',
            'storage' => [
                'location' => 'parameter',
                'domain' => 'midgard.admin.asgard:preferences',
                'name' => 'codemirror_enabled',
            ],
            'type' => 'select',
            'type_config' => [
                'options' => [
                    '' => 'default setting',
                    '1' => 'yes',
                    '0' => 'no',
                ],
            ],
            'widget' => 'radiocheckselect',
        ],
        'edit_mode' => [
            'title' => 'use edit mode instead of view mode as the primary object management page',
            'storage' => [
                'location' => 'parameter',
                'domain' => 'midgard.admin.asgard:preferences',
                'name' => 'edit_mode',
            ],
            'type' => 'select',
            'type_config' => [
                'options' => [
                    '' => 'default setting',
                    '1' => 'yes',
                    '0' => 'no',
                ],
            ],
            'widget' => 'radiocheckselect',
            'end_fieldset' => '',
        ],
        'interface_language' => [
            'title' => 'interface language',
            'storage' => [
                'location' => 'parameter',
                'domain' => 'midgard.admin.asgard:preferences',
                'name' => 'interface_language',
            ],
            'type' => 'select',
            'type_config' => [
                'options' => midgard_admin_asgard_handler_preferences::get_languages(),
            ],
            'widget' => 'select',
            'start_fieldset' => [
                'title' => 'localisation settings',
            ],
            'end_fieldset' => '',
        ],
        'navigation_type' => [
            'title' => 'navigation type',
            'storage' => [
                'location' => 'parameter',
                'domain' => 'midgard.admin.asgard:preferences',
                'name' => 'navigation_type',
            ],
            'type' => 'select',
            'type_config' => [
                'options' => [
                    '' => 'default setting',
                    'dropdown' => 'dropdown list',
                    'expanded' => 'expanded list',
                ],
            ],
            'widget' => 'radiocheckselect',
            'start_fieldset' => [
                'title' => 'navigation options',
            ],
        ],
        'offset' => [
            'title' => 'navigation width (in pixels)',
            'storage' => [
                'location' => 'parameter',
                'domain' => 'midgard.admin.asgard:preferences',
                'name' => 'offset',
            ],
            'type' => 'text',
            'widget' => 'text',
        ],
        'enable_quicklinks' => [
            'title' => 'enable quicklinks',
            'storage' => [
                'location' => 'parameter',
                'domain' => 'midgard.admin.asgard:preferences',
                'name' => 'enable_quicklinks',
            ],
            'type' => 'select',
            'type_config' => [
                'options' => [
                    '' => 'default setting',
                    'yes' => 'yes',
                    'no' => 'no',
                ],
            ],
            'widget' => 'radiocheckselect',
        ],
        'escape_frameset' => [
            'title' => 'always display in top frame',
            'storage' => [
                'location' => 'parameter',
                'domain' => 'midgard.admin.asgard:preferences',
                'name' => 'escape_frameset',
            ],
            'type' => 'select',
            'type_config' => [
                'options' => [
                    '' => 'default setting',
                    '1' => 'yes',
                    '0' => 'no',
                ],
            ],
            'widget' => 'radiocheckselect',
            'end_fieldset' => '',
        ],
        'midgard_types_model' => [
            'title' => 'model for selecting navigation types',
            'storage' => [
                'location' => 'parameter',
                'domain' => 'midgard.admin.asgard:preferences',
                'name' => 'midgard_types_model',
            ],
            'type' => 'select',
            'type_config' => [
                'options' => [
                    '' => 'default setting',
                    'exclude' => 'exclude',
                    'include' => 'include',
                ],
            ],
            'widget' => 'radiocheckselect',
            'start_fieldset' => [
                'title' => 'mgdschema type visibility in navigation',
            ],
        ],
        'midgard_types' => [
            'title' => 'select the types',
            'storage' => [
                'location' => 'parameter',
                'domain' => 'midgard.admin.asgard:preferences',
                'name' => 'midgard_types',
            ],
            'type' => 'select',
            'type_config' => [
                'options' => midgard_admin_asgard_plugin::get_root_classes(),
                'allow_multiple' => true,
                'require_corresponding_option' => false,
                'multiple_storagemode' => 'imploded_wrapped',
            ],
            'widget' => 'select',
        ],
        'midgard_types_regexp' => [
            'title' => 'regular expression for selecting mgdschema types',
            'storage' => [
                'location' => 'parameter',
                'domain' => 'midgard.admin.asgard:preferences',
                'name' => 'midgard_types_regexp',
            ],
            'type' => 'text',
            'widget' => 'text',
            'end_fieldset' => 1,
        ],
    ],
],
