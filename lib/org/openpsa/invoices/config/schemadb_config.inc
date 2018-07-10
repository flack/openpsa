'config' => [
    'name'        => 'config',
    'description' => 'Default Configuration Schema', /* This is a topic */
    'fields'      => [
        /* invoice settings */
        'default_due_days' => [
            'title'   => 'default due days',
            'type'      => 'number',
            'storage' => [
                'location'      => 'configuration',
                'domain' => 'org.openpsa.invoices',
                'name'    => 'default_due_days',
             ],
            'widget' => 'text',
            'start_fieldset' => [
                'title' => 'invoice',
            ],
        ],
        'vat_percentages' => [
            'title'   => 'vat percentages',
            'type'      => 'text',
            'storage' => [
                'location'      => 'configuration',
                'domain' => 'org.openpsa.invoices',
                'name'    => 'vat_percentages',
             ],
            'widget' => 'text',
        ],
        'default_hourly_price' => [
            'title'   => 'default hourly price',
            'type'      => 'number',
            'storage' => [
                'location'      => 'configuration',
                'domain' => 'org.openpsa.invoices',
                'name'    => 'default_hourly_price',
             ],
            'widget' => 'text',
            'end_fieldset' => '',
        ],

        /* Schema settings */
        'schemadb' => [
            'title' => 'schema database',
            'type' => 'text',
            'storage' => [
                'location' => 'configuration',
                'domain' => 'org.openpsa.invoices',
                'name' => 'schemadb',
             ],
            'widget' => 'text',
            'start_fieldset' => [
                'title' => 'advanced schema and data settings',
            ],
            'end_fieldset' => '',
        ],

    ],
],