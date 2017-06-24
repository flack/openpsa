'config' => array
(
    'name' => 'config',
    'description' => 'Default Configuration Schema', /* This is a topic */
    'fields' => array
    (
        /* view settings */
        'start_view' => array
        (
            'title' => 'which view to start in',
            'type' => 'text',
            'widget' => 'text',
            'default' => 'week',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'org.openpsa.calendar',
                'name' => 'start_view'
            ),
            'start_fieldset' => array
            (
                'title' => 'view settings',
            ),
            'end_fieldset' => '',
        ),

        /* time settings */
        'day_start_time' => array
        (
            'title' => 'hour "working day" starts',
            'type' => 'number',
            'widget' => 'text',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'org.openpsa.calendar',
                'name' => 'day_start_time'
            ),
            'default' => 8,
            'start_fieldset' => array
            (
                'title' => 'time settings',
            ),
        ),
        'day_end_time' => array
        (
            'title' => 'hour "working day" ends',
            'type' => 'number',
            'widget' => 'text',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'org.openpsa.calendar',
                'name' => 'day_end_time'
            ),
            'default' => 18,
        ),

        /* Schema settings */
        'schemadb' => array
        (
            'title' => 'schema database',
            'type' => 'text',
            'widget' => 'text',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'org.openpsa.calendar',
                'name' => 'schemadb'
            ),
            'start_fieldset' => array
            (
                'title' => 'advanced schema and data settings',
            ),
        ),

        'event_label' => array
        (
            'title' => 'event label field',
            'type' => 'text',
            'widget' => 'text',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'org.openpsa.calendar',
                'name' => 'event_label'
            ),
            'end_fieldset' => '',
        ),
    ),
),