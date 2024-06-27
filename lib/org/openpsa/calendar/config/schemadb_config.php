<?php
return [
    'config' => [
        'name' => 'config',
        'description' => 'Default Configuration Schema', /* This is a topic */
        'fields' => [
            /* view settings */
            'start_view' => [
                'title' => 'which view to start in',
                'type' => 'text',
                'widget' => 'text',
                'default' => 'week',
                'storage' => [
                    'location' => 'configuration',
                    'domain' => 'org.openpsa.calendar',
                    'name' => 'start_view'
                ],
                'start_fieldset' => [
                    'title' => 'view settings',
                ],
                'end_fieldset' => '',
            ],

            /* time settings */
            'day_start_time' => [
                'title' => 'hour "working day" starts',
                'type' => 'number',
                'widget' => 'text',
                'storage' => [
                    'location' => 'configuration',
                    'domain' => 'org.openpsa.calendar',
                    'name' => 'day_start_time'
                ],
                'default' => 8,
                'start_fieldset' => [
                    'title' => 'time settings',
                ],
            ],
            'day_end_time' => [
                'title' => 'hour "working day" ends',
                'type' => 'number',
                'widget' => 'text',
                'storage' => [
                    'location' => 'configuration',
                    'domain' => 'org.openpsa.calendar',
                    'name' => 'day_end_time'
                ],
                'default' => 18,
            ],

            /* Schema settings */
            'schemadb' => [
                'title' => 'schema database',
                'type' => 'text',
                'widget' => 'text',
                'storage' => [
                    'location' => 'configuration',
                    'domain' => 'org.openpsa.calendar',
                    'name' => 'schemadb'
                ],
                'start_fieldset' => [
                    'title' => 'advanced schema and data settings',
                ],
            ],

            'event_label' => [
                'title' => 'event label field',
                'type' => 'text',
                'widget' => 'text',
                'storage' => [
                    'location' => 'configuration',
                    'domain' => 'org.openpsa.calendar',
                    'name' => 'event_label'
                ],
                'end_fieldset' => '',
            ],
        ],
    ]
];