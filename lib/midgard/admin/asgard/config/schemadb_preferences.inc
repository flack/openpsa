'default' => array
(
    'description' => 'user preferences',
    'l10n_db' => 'midgard.admin.asgard',
    'fields' => array
    (
        'tinymce_enabled' => array
        (
            'title' => 'use tinymce editor for editing content',
            'storage' => array
            (
                'location' => 'parameter',
                'domain' => 'midgard.admin.asgard:preferences',
                'name' => 'tinymce_enabled',
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
                'title' => 'editor options',
            ),
        ),
        'codemirror_enabled' => array
        (
            'title' => 'use codemirror editor for editing code snippets',
            'storage' => array
            (
                'location' => 'parameter',
                'domain' => 'midgard.admin.asgard:preferences',
                'name' => 'codemirror_enabled',
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
        'edit_mode' => array
        (
            'title' => 'use edit mode instead of view mode as the primary object management page',
            'storage' => array
            (
                'location' => 'parameter',
                'domain' => 'midgard.admin.asgard:preferences',
                'name' => 'edit_mode',
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
        'interface_language' => array
        (
            'title' => 'interface language',
            'storage' => array
            (
                'location' => 'parameter',
                'domain' => 'midgard.admin.asgard:preferences',
                'name' => 'interface_language',
            ),
            'type' => 'select',
            'type_config' => array
            (
                'options' => midgard_admin_asgard_handler_preferences::get_languages(),
            ),
            'widget' => 'select',
            'start_fieldset' => array
            (
                'title' => 'localisation settings',
            ),
            'end_fieldset' => '',
        ),
        'navigation_type' => array
        (
            'title' => 'navigation type',
            'storage' => array
            (
                'location' => 'parameter',
                'domain' => 'midgard.admin.asgard:preferences',
                'name' => 'navigation_type',
            ),
            'type' => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    '' => 'default setting',
                    'dropdown' => 'dropdown list',
                    'expanded' => 'expanded list',
                ),
            ),
            'widget' => 'select',
            'start_fieldset' => array
            (
                'title' => 'navigation options',
            ),
        ),
        'offset' => array
        (
            'title' => 'navigation width (in pixels)',
            'storage' => array
            (
                'location' => 'parameter',
                'domain' => 'midgard.admin.asgard:preferences',
                'name' => 'offset',
            ),
            'type' => 'text',
            'widget' => 'text',
        ),
        'enable_quicklinks' => array
        (
            'title' => 'enable quicklinks',
            'storage' => array
            (
                'location' => 'parameter',
                'domain' => 'midgard.admin.asgard:preferences',
                'name' => 'enable_quicklinks',
            ),
            'type' => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    '' => 'default setting',
                    'yes' => 'yes',
                    'no' => 'no',
                ),
            ),
            'widget' => 'select',
        ),
        'escape_frameset' => array
        (
            'title' => 'always display in top frame',
            'storage' => array
            (
                'location' => 'parameter',
                'domain' => 'midgard.admin.asgard:preferences',
                'name' => 'escape_frameset',
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
        'midgard_types_model' => array
        (
            'title' => 'model for selecting navigation types',
            'storage' => array
            (
                'location' => 'parameter',
                'domain' => 'midgard.admin.asgard:preferences',
                'name' => 'midgard_types_model',
            ),
            'type' => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    '' => 'default setting',
                    'exclude' => 'exclude',
                    'include' => 'include',
                ),
            ),
            'widget' => 'select',
            'start_fieldset' => array
            (
                'title' => 'mgdschema type visibility in navigation',
            ),
        ),
        'midgard_types' => array
        (
            'title' => 'select the types',
            'storage' => array
            (
                'location' => 'parameter',
                'domain' => 'midgard.admin.asgard:preferences',
                'name' => 'midgard_types_excluded',
            ),
            'type' => 'select',
            'type_config' => array
            (
                'options' => midgard_admin_asgard_plugin::get_root_classes(),
                'allow_multiple' => true,
                'require_corresponding_option' => false,
                'multiple_storagemode' => 'imploded_wrapped',
            ),
            'widget' => 'select',
        ),
        'midgard_types_regexp' => array
        (
            'title' => 'regular expression for selecting mgdschema types',
            'storage' => array
            (
                'location' => 'parameter',
                'domain' => 'midgard.admin.asgard:preferences',
                'name' => 'midgard_types_regexp',
            ),
            'type' => 'text',
            'widget' => 'text',
            'end_fieldset' => 1,
        ),
    ),
),
