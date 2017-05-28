'config' => array
(
    'name'        => 'config',
    'description' => 'Default Configuration Schema', /* This is a topic */
    'fields'      => array
    (
        /* invoice settings */
        'default_due_days' => array
        (
            'title'   => 'default due days',
            'type'      => 'number',
            'storage' => array
            (
                'location'      => 'configuration',
                'domain' => 'org.openpsa.invoices',
                'name'    => 'default_due_days',
             ),
            'widget' => 'text',
            'start_fieldset' => array
            (
                'title' => 'invoice',
            ),
        ),
        'vat_percentages' => array
        (
            'title'   => 'vat percentages',
            'type'      => 'text',
            'storage' => array
            (
                'location'      => 'configuration',
                'domain' => 'org.openpsa.invoices',
                'name'    => 'vat_percentages',
             ),
            'widget' => 'text',
        ),
        'default_hourly_price' => array
        (
            'title'   => 'default hourly price',
            'type'      => 'number',
            'storage' => array
            (
                'location'      => 'configuration',
                'domain' => 'org.openpsa.invoices',
                'name'    => 'default_hourly_price',
             ),
            'widget' => 'text',
            'end_fieldset' => '',
        ),

        /* Schema settings */
        'schemadb' => array
        (
            'title' => 'schema database',
            'type' => 'text',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'org.openpsa.invoices',
                'name' => 'schemadb',
             ),
            'widget' => 'text',
            'start_fieldset' => array
            (
                'title' => 'advanced schema and data settings',
            ),
            'end_fieldset' => '',
        ),

    ),
),