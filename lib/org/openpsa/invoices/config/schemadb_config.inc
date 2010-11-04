'config' => array
(
    'name'        => 'config',
    'description' => 'Default Configuration Schema', /* This is a topic */
    'locktimeout' => 0, /* Don't lock this, we display the edit view always */
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
        'invoice_sender' => array
        (
            'title'   => 'invoice sender',
            'type'      => 'select',
            'type_config' => array
            (
                 'require_corresponding_option' => false,
                 'options' => array(),
            ),
            'storage' => array
            (
                'location'      => 'configuration',
                'domain' => 'org.openpsa.invoices',
                'name'    => 'invoice_sender',
             ),
            'widget' => 'chooser',
            'widget_config' => array
            (
                'class'       => 'org_openpsa_contacts_person_dba',
                'component'   => 'org.openpsa.contacts',
                'titlefield'  => 'name',
                'id_field'     => 'id',
                'constraints' => array
                (
                    array
                    (
                        'field' => 'username',
                        'op'    => '<>',
                        'value' => '',
                    ),
                ),
                'searchfields'  => array
                (
                    'firstname',
                    'lastname',
                    'username',
                ),
                'result_headers' => array
                (
                    array
                    (
                        'title' => 'name',
                        'name' => 'name',
                    ),
                    array
                    (
                        'title' => 'email',
                        'name' => 'email',
                    ),
                ),
                'orders'        => array
                (
                    array('lastname'    => 'ASC'),
                    array('firstname'    => 'ASC'),

                ),
            ),
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