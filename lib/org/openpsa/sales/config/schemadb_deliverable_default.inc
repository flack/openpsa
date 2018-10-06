'default' => [
    'description' => 'single delivery',
    'validation' => [
    	[
            'callback' => [new org_openpsa_sales_validator, 'validate_units'],
        ],
    ],
    'fields'      => [
        'title' => [
            // COMPONENT-REQUIRED
            'title' => 'title',
            'storage' => 'title',
            'type' => 'text',
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
        'tags' => [
            'title' => 'tags',
            'storage' => null,
            'type' => 'tags',
            'widget' => 'text',
        ],

        'supplier' => [
            'title'   => 'supplier',
            'storage' => 'supplier',
            'type' => 'select',
            'type_config' => [
                 'require_corresponding_option' => false,
                 'options' => [],
            ],
            'widget' => 'autocomplete',
            'widget_config' => [
                'class'       => 'org_openpsa_contacts_group_dba',
                'component'   => 'org.openpsa.contacts',
                'titlefield'  => 'official',
                'id_field'     => 'id',
                'searchfields'  => [
                    'name',
                    'official'
                ],
                'orders' => [
                    ['official'    => 'ASC'],
                ],
            ],
        ],
        'end' => [
            'title' => 'estimated delivery',
            'storage' => 'end',
            'type' => 'date',
            'type_config' => [
                'storage_type' => 'UNIXTIME'
            ],
            'widget' => 'jsdate',
            'widget_config' => [
                'show_time' => false,
            ],
        ],
        'notify' => [
            'title' => 'notify date',
            'storage' => 'notify',
            'type' => 'date',
            'type_config' => [
                'storage_type' => 'UNIXTIME'
            ],
            'widget' => 'jsdate',
            'widget_config' => [
                'show_time' => false,
            ],
        ],
        'product' => [
            'title' => 'product',
            'storage' => 'product',
            'type' => 'select',
            'type_config' => [
                 'require_corresponding_option' => false,
                 'options' => [],
            ],
            'widget' => 'autocomplete',
            'widget_config' => [
                'class'       => 'org_openpsa_products_product_dba',
                'id_field'     => 'id',
                'constraints' => [
                	[
                        'field' => 'delivery',
                        'op' => '=',
                        'value' => org_openpsa_products_product_dba::DELIVERY_SINGLE
                    ],
                ],
                'searchfields'  => [
                    'code',
                    'title'
                ],
                'result_headers' => [
                	[
                        'name' => 'code',
                    ],
                ],
                'orders'        => [
                    ['title'    => 'ASC'],
                    ['code'    => 'ASC'],
                ],
            ],
            'required' => true
        ],
        'pricePerUnit' => [
            'title' => 'price per unit',
            'storage' => 'pricePerUnit',
            'type' => 'number',
            'widget'  => 'text',
        ],
        'costPerUnit' => [
            'title' => 'cost per unit',
            'storage' => 'costPerUnit',
            'type' => 'number',
            'widget'  => 'text',
        ],
        'costType' => [
            'title' => 'cost type',
            'storage' => 'costType',
            'type' => 'text',
            'widget' => 'hidden',
        ],
        'cost' => [
            'title' => 'cost',
            'storage' => 'cost',
            'type' => 'number',
            'widget'  => 'text',
            'hidden' => true,
        ],
        'units' => [
            'title' => 'units',
            'storage' => 'units',
            'type' => 'number',
            'widget'  => 'text',
            'hidden' => true,
        ],
        'plannedUnits' => [
            'title' => 'planned units',
            'storage' => 'plannedUnits',
            'type' => 'number',
            'widget'  => 'text',
        ],
        'invoiceByActualUnits' => [
            'title'   => 'invoice by actual units',
            'storage' => 'invoiceByActualUnits',
            'type'    => 'boolean',
            'widget'  => 'checkbox',
        ],
        'invoiceApprovedOnly' => [
            'title'   => 'invoice approved only',
            'storage' => 'invoiceApprovedOnly',
            'type'    => 'boolean',
            'widget'  => 'checkbox',
        ],
        'unit' => [
            'title' => 'unit',
            'storage' => 'unit',
            'type' => 'select',
            'type_config' => [
                'options' => org_openpsa_products_viewer::get_unit_options(),
            ],
            'hidden' => true,
            'widget' => 'select',
        ],
    ]
],
'subscription' => [
    'description' => 'recurring subscription',
    'validation' => [
    	[
            'callback' => [new org_openpsa_sales_validator, 'validate_subscription'],
        ],
    ],
    'fields'      => [
        'title' => [
            'title' => 'title',
            'storage' => 'title',
            'type' => 'text',
            'widget'  => 'text',
            'required' => true
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
        'tags' => [
            'title' => 'tags',
            'storage' => null,
            'type' => 'tags',
            'widget' => 'text',
        ],
        'supplier' => [
            'title'   => 'supplier',
            'storage' => 'supplier',
            'type' => 'select',
            'type_config' => [
                 'require_corresponding_option' => false,
                 'options' => [],
            ],
            'widget' => 'autocomplete',
            'widget_config' => [
                'class'       => 'org_openpsa_contacts_group_dba',
                'component'   => 'org.openpsa.contacts',
                'titlefield'  => 'official',
                'id_field'     => 'id',
                'searchfields'  => [
                    'name',
                    'official'
                ],
                'orders'        => [
                    ['official'    => 'ASC'],
                ],
            ],
        ],
        'start' => [
            'title' => 'subscription begins',
            'storage' => 'start',
            'type' => 'date',
            'type_config' => [
                'storage_type' => 'UNIXTIME'
            ],
            'widget' => 'jsdate',
            'widget_config' => [
                'show_time' => false,
            ],
            'required' => true
        ],
        'end' => [
            'title' => 'subscription ends',
            'storage' => 'end',
            'type' => 'date',
            'type_config' => [
                'storage_type' => 'UNIXTIME'
            ],
            'widget' => 'jsdate',
            'widget_config' => [
                'show_time' => false,
            ],
        ],
        'continuous' => [
            'title'   => 'continuous subscription',
            'storage' => 'continuous',
            'type'    => 'boolean',
            'widget'  => 'checkbox',
        ],
        'notify' => [
            'title' => 'notify date',
            'storage' => 'notify',
            'type' => 'date',
            'type_config' => [
                'storage_type' => 'UNIXTIME'
            ],
            'widget' => 'jsdate',
            'widget_config' => [
                'show_time' => false,
            ],
        ],
        'product' => [
            'title' => 'product',
            'storage' => 'product',
            'type' => 'select',
            'type_config' => [
                 'require_corresponding_option' => false,
                 'options' => [],
            ],
            'widget' => 'autocomplete',
            'widget_config' => [
                'class'       => 'org_openpsa_products_product_dba',
                'id_field'     => 'id',
                'constraints' => [
                	[
                        'field' => 'delivery',
                        'op' => '=',
                        'value' => org_openpsa_products_product_dba::DELIVERY_SUBSCRIPTION
                    ],
                ],
                'searchfields'  => [
                    'code',
                    'title'
                ],
                'result_headers' => [
                	[
                        'name' => 'code',
                    ],
                ],
                'orders'        => [
                    ['title'    => 'ASC'],
                    ['code'    => 'ASC'],
                ],
            ],
            'required' => true
        ],
        'pricePerUnit' => [
            'title' => 'price per unit',
            'storage' => 'pricePerUnit',
            'type' => 'number',
            'widget'  => 'text',
        ],
        'costPerUnit' => [
            'title' => 'cost per unit',
            'storage' => 'costPerUnit',
            'type' => 'number',
            'widget'  => 'text',
        ],
        'costType' => [
            'title' => 'cost type',
            'storage' => 'costType',
            'type' => 'text',
            'widget' => 'hidden',
        ],
        'cost' => [
            'title' => 'cost',
            'storage' => 'cost',
            'type' => 'number',
            'widget'  => 'text',
            'hidden' => true,
        ],
        'units' => [
            'title' => 'units',
            'storage' => 'units',
            'type' => 'number',
            'widget'  => 'text',
            'hidden' => true
        ],
        'plannedUnits' => [
            'title' => 'planned units',
            'storage' => 'plannedUnits',
            'type' => 'number',
            'widget'  => 'text',
        ],
        'invoiceByActualUnits' => [
            'title'   => 'invoice by actual units',
            'storage' => 'invoiceByActualUnits',
            'type'    => 'boolean',
            'widget'  => 'checkbox',
        ],
        'invoiceApprovedOnly' => [
            'title'   => 'invoice approved only',
            'storage' => 'invoiceApprovedOnly',
            'type'    => 'boolean',
            'widget'  => 'checkbox',
        ],
        'unit' => [
            'title' => 'invoicing period',
            'storage' => 'unit',
            'type' => 'select',
            'type_config' => [
                'options' => [
                    'd' =>  midcom::get()->i18n->get_string('day', 'org.openpsa.products'),       // per day
                    'm' =>  midcom::get()->i18n->get_string('month', 'org.openpsa.products'),     // per month
                    'q' =>  midcom::get()->i18n->get_string('quarter', 'org.openpsa.products'),   // per quarter
                    'hy' => midcom::get()->i18n->get_string('half-year', 'org.openpsa.products'), // per half
                    'y' =>  midcom::get()->i18n->get_string('year', 'org.openpsa.products'),      // per annum
                ],
            ],
            'readonly' => false,
            'widget' => 'select',
        ],
        'at_entry' => [
            'title'   => '',
            'storage' => null,
            'type'    => 'number',
            'widget'  => 'hidden',
        ],
        'next_cycle' => [
            'title' => 'next run',
            'storage' => null,
            'type' => 'date',
            'type_config' => [
                'storage_type' => 'UNIXTIME'
            ],
            'widget' => 'jsdate',
            'widget_config' => [
                'show_time' => false,
            ],
            'hidden' => true
        ],
    ]
],