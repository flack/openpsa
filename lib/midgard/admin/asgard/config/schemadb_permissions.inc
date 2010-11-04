'privileges' => array
(
    'description' => 'privileges',
    'l10n_db'     => 'midgard.admin.asgard',
    'fields'      => array
    (
        'add_assignee' => Array
        (
            // COMPONENT-REQUIRED
            'title' => 'add assignee',
            'storage' => array
            (
                'location' => 'configuration',
                'domain'   => 'midgard.admin.asgard.acl',
                'name'     => 'add_assignee',
            ),
            'type' => 'select',
            'type_config' => Array
            (
                'options' => Array(),
            ),
            'widget' => 'select',
            'widget_config' => array
            (
                'jsevents' => array
                (
                    'onchange' => 'submit_privileges(this.form);',
                ),
            ),
        ),         
        // This is dynamically filled later
        
    )
),