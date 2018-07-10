'privileges' => [
    'description' => 'privileges',
    'l10n_db'     => 'midgard.admin.asgard',
    'fields'      => [
        'add_assignee' => [
            // COMPONENT-REQUIRED
            'title' => 'add assignee',
            'storage' => null,
            'type' => 'select',
            'type_config' => [
                'options' => [],
            ],
            'widget' => 'select',
            'widget_config' => [
                'jsevents' => [
                    'onchange' => 'submit_privileges(this.form);',
                ],
            ],
        ],
        // This is dynamically filled later
    ]
],