'hour_report' => [
    'description'        => 'hour report',
    'fields'      => [
        'date' => [
            'title' => 'date',
            'storage' => 'date',
            'type' => 'date',
            'type_config' => [
                'storage_type' => 'UNIXTIME'
            ],
            'widget' => 'jsdate',
            'widget_config' => [
                'show_time' => false,
            ],
            'required' => true,
        ],
        'hours' => [
            'title' => 'hours',
            'storage' => 'hours',
            'type' => 'number',
            'type_config' => [
                'precision' => 2,
            ],
            'widget'  => 'text',
            'required' => true,
        ],
        'description' => [
            'title' => 'description',
            'storage' => 'description',
            'type' => 'text',
            'type_config' => [
                'output_mode' => 'markdown'
            ],
            'widget' => 'markdown',
        ],
        'invoiceable' => [
            'title'   => 'invoiceable',
            'storage' => 'invoiceable',
            'type'    => 'boolean',
            'widget'  => 'checkbox',
        ],
        'task' => [
            'title'   => 'task',
            'storage' => 'task',
            'required' => true,
            'type' => 'select',
            'type_config' => [
                 'require_corresponding_option' => false,
                 'options' => [],
            ],
            'widget' => 'autocomplete',
            'widget_config' => [
                'class'       => 'org_openpsa_projects_task_dba',
                'component'   => 'org.openpsa.projects',
                'titlefield'  => 'title',
                'id_field'     => 'id',
                'result_headers' => [
                    [
                        'title' => 'title',
                        'name' => 'title',
                    ],
                ],
                'searchfields'  => [
                    'title'
                ],
                'orders'        => [
                    ['title'    => 'ASC']
                ],
                'categorize_by_parent_label' => 'title',
            ],
        ],
        'person' => [
            'title'   => 'person',
            'storage' => 'person',
            'required' => true,
            'type' => 'select',
            'type_config' => [
                 'require_corresponding_option' => false,
                 'options' => [],
            ],
            'widget' => 'autocomplete',
            'widget_config' => [
                'class'       => 'org_openpsa_contacts_person_dba',
                'component'   => 'org.openpsa.contacts',
                'titlefield'  => 'name',
                'id_field'     => 'id',
                'constraints' => [
                    [
                        'field' => 'username',
                        'op'    => '<>',
                        'value' => '',
                    ],
                ],
                'result_headers' => [
                    [
                        'title' => 'name',
                        'name' => 'name',
                    ], [
                        'title' => 'email',
                        'name' => 'email',
                    ],
                ],
                'searchfields'  => [
                    'firstname',
                    'lastname',
                    'username',
                ],
                'orders'        => [
                    ['lastname'    => 'ASC'],
                    ['firstname'    => 'ASC'],
                ],
            ],
        ],
        'invoice' => [
            'title'   => 'invoice',
            'storage' => 'invoice',
            'type' => 'select',
            'type_config' => [
                 'require_corresponding_option' => false,
            ],
            'widget' => 'autocomplete',
            'widget_config' => [
                'class'       => 'org_openpsa_invoices_invoice_dba',
                'component'   => 'org.openpsa.invoices',
                'titlefield'  => 'number',
                'id_field'     => 'id',
                'result_headers' => [
                    [
                        'title' => 'invoice number',
                        'name' => 'number',
                    ],
                ],
                'searchfields'  => [
                    'number',
                ],
                'auto_wildcards' => false,
                'orders'        => [
                    ['number'    => 'ASC'],
                ],
                'get_label_for' => 'number'
            ],
        ],
    ]
],

