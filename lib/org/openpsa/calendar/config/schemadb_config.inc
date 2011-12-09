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
        ),
        'calendar_popup_height' => array
        (
            'title' => 'height of the event popup window (px)',
            'type' => 'number',
            'widget' => 'text',
            'default' => 450,
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'org.openpsa.calendar',
                'name' => 'calendar_popup_height'
            ),
        ),
        'calendar_popup_width' => array
        (
            'title' => 'width of the event popup window (px)',
            'type' => 'number',
            'widget' => 'text',
            'default' => 300,
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'org.openpsa.calendar',
                'name' => 'calendar_popup_width'
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
        'day_slot_length' => array
        (
            'title' => 'size of week day slot (minutes)',
            'type' => 'number',
            'widget' => 'text',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'org.openpsa.calendar',
                'name' => 'day_slot_length'
            ),
            'default' => 60,
        ),
        'week_slot_length' => array
        (
            'title' => 'size of week view slot (minutes)',
            'type' => 'number',
            'widget' => 'text',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'org.openpsa.calendar',
                'name' => 'week_slot_length'
            ),
            'default' => 120,
            'end_fieldset' => '',
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
        'always_show_group' => array
        (
            'title' => 'always display group',
            'type' => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    1 => 'yes',
                    0 => 'no',
                ),
            ),
            'widget' => 'radiocheckselect',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'org.openpsa.calendar',
                'name' => 'always_show_group'
            ),
        ),

        'always_show_self' => array
        (
            'title' => 'always display self',
            'type' => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    1 => 'yes',
                    0 => 'no',
                ),
            ),
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'org.openpsa.calendar',
                'name' => 'always_show_self'
            ),
            'widget' => 'radiocheckselect',
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