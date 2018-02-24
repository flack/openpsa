'default' => array
(
    'description' => 'single delivery',
    'validation' => array
    (
        array
        (
            'callback' => array(new org_openpsa_sales_validator, 'validate_units'),
        ),
    ),
    'fields'      => array
    (
        'title' => Array
        (
            // COMPONENT-REQUIRED
            'title' => 'title',
            'storage' => 'title',
            'type' => 'text',
            'widget'  => 'text',
            'required' => true,
        ),
        'description' => Array
        (
            'title' => 'description',
            'storage' => 'description',
            'type' => 'text',
            'type_config' => Array (
                'output_mode' => 'markdown'
            ),
            'widget' => 'markdown',
        ),
        'tags' => Array
        (
            'title' => 'tags',
            'storage' => null,
            'type' => 'tags',
            'widget' => 'text',
        ),

        'supplier' => array
        (
            'title'   => 'supplier',
            'storage' => 'supplier',
            'type' => 'select',
            'type_config' => array
            (
                 'require_corresponding_option' => false,
                 'options' => array(),
            ),
            'widget' => 'autocomplete',
            'widget_config' => array
            (
                'class'       => 'org_openpsa_contacts_group_dba',
                'component'   => 'org.openpsa.contacts',
                'titlefield'  => 'official',
                'id_field'     => 'id',
                'searchfields'  => array
                (
                    'name',
                    'official'
                ),
                'result_headers' => array
                (
                    array
                    (
                        'title' => 'official',
                        'name' => 'official',
                    ),
                ),
                'orders' => array
                (
                    array('official'    => 'ASC'),
                ),
            ),
        ),
        'end' => Array
        (
            'title' => 'estimated delivery',
            'storage' => 'end',
            'type' => 'date',
            'type_config' => Array
            (
                'storage_type' => 'UNIXTIME'
            ),
            'widget' => 'jsdate',
            'widget_config' => Array
            (
                'show_time' => false,
            ),
        ),
        'notify' => Array
        (
            'title' => 'notify date',
            'storage' => 'notify',
            'type' => 'date',
            'type_config' => Array
            (
                'storage_type' => 'UNIXTIME'
            ),
            'widget' => 'jsdate',
            'widget_config' => Array
            (
                'show_time' => false,
            ),
        ),
        'product' => Array
        (
            'title' => 'product',
            'storage' => 'product',
            'type' => 'select',
            'type_config' => array
            (
                 'require_corresponding_option' => false,
                 'options' => array(),
            ),
            'widget' => 'autocomplete',
            'widget_config' => array
            (
                'class'       => 'org_openpsa_products_product_dba',
                'id_field'     => 'id',
                'constraints' => array
                (
                    array
                    (
                        'field' => 'delivery',
                        'op' => '=',
                        'value' => org_openpsa_products_product_dba::DELIVERY_SINGLE
                    ),
                ),
                'searchfields'  => array
                (
                    'code',
                    'title'
                ),
                'result_headers' => array
                (
                    array
                    (
                        'name' => 'code',
                    ),
                    array
                    (
                        'name' => 'title',
                    ),
                ),
                'orders'        => array
                (
                    array('title'    => 'ASC'),
                    array('code'    => 'ASC'),
                ),
            ),
            'required' => true
        ),
        'pricePerUnit' => Array
        (
            'title' => 'price per unit',
            'storage' => 'pricePerUnit',
            'type' => 'number',
            'widget'  => 'text',
        ),
        'costPerUnit' => Array
        (
            'title' => 'cost per unit',
            'storage' => 'costPerUnit',
            'type' => 'number',
            'widget'  => 'text',
        ),
        'costType' => Array
        (
            'title' => 'cost type',
            'storage' => 'costType',
            'type' => 'text',
            'widget' => 'hidden',
        ),
        'cost' => Array
        (
            'title' => 'cost',
            'storage' => 'cost',
            'type' => 'number',
            'widget'  => 'text',
            'hidden' => true,
        ),
        'units' => Array
        (
            'title' => 'units',
            'storage' => 'units',
            'type' => 'number',
            'widget'  => 'text',
            'hidden' => true,
        ),
        'plannedUnits' => Array
        (
            'title' => 'planned units',
            'storage' => 'plannedUnits',
            'type' => 'number',
            'widget'  => 'text',
        ),
        'invoiceByActualUnits' => Array
        (
            'title'   => 'invoice by actual units',
            'storage' => 'invoiceByActualUnits',
            'type'    => 'boolean',
            'widget'  => 'checkbox',
        ),
        'invoiceApprovedOnly' => Array
        (
            'title'   => 'invoice approved only',
            'storage' => 'invoiceApprovedOnly',
            'type'    => 'boolean',
            'widget'  => 'checkbox',
        ),
        'unit' => Array
        (
            'title' => 'unit',
            'storage' => 'unit',
            'type' => 'select',
            'type_config' => Array
            (
                'options' => org_openpsa_products_viewer::get_unit_options(),
            ),
            'hidden' => true,
            'widget' => 'select',
        ),
    )
),
'subscription' => array
(
    'description' => 'recurring subscription',
    'validation' => array
    (
        array
        (
            'callback' => array(new org_openpsa_sales_validator, 'validate_subscription'),
        ),
    ),
    'fields'      => array
    (
        'title' => Array
        (
            'title' => 'title',
            'storage' => 'title',
            'type' => 'text',
            'widget'  => 'text',
            'required' => true
        ),
        'description' => Array
        (
            'title' => 'description',
            'storage' => 'description',
            'type' => 'text',
            'type_config' => Array (
                'output_mode' => 'markdown'
            ),
            'widget' => 'markdown',
        ),
        'tags' => Array
        (
            'title' => 'tags',
            'storage' => null,
            'type' => 'tags',
            'widget' => 'text',
        ),
        'supplier' => array
        (
            'title'   => 'supplier',
            'storage' => 'supplier',
            'type' => 'select',
            'type_config' => array
            (
                 'require_corresponding_option' => false,
                 'options' => array(),
            ),
            'widget' => 'autocomplete',
            'widget_config' => array
            (
                'class'       => 'org_openpsa_contacts_group_dba',
                'component'   => 'org.openpsa.contacts',
                'titlefield'  => 'official',
                'id_field'     => 'id',
                'searchfields'  => array
                (
                    'name',
                    'official'
                ),
                'result_headers' => array
                (
                    array
                    (
                        'title' => 'official',
                        'name' => 'official',
                    ),
                ),
                'orders'        => array
                (
                    array('official'    => 'ASC'),
                ),
            ),
        ),
        'start' => Array
        (
            'title' => 'subscription begins',
            'storage' => 'start',
            'type' => 'date',
            'type_config' => Array
            (
                'storage_type' => 'UNIXTIME'
            ),
            'widget' => 'jsdate',
            'widget_config' => Array
            (
                'show_time' => false,
            ),
            'required' => true
        ),
        'end' => Array
        (
            'title' => 'subscription ends',
            'storage' => 'end',
            'type' => 'date',
            'type_config' => Array
            (
                'storage_type' => 'UNIXTIME'
            ),
            'widget' => 'jsdate',
            'widget_config' => Array
            (
                'show_time' => false,
            ),
        ),
        'continuous' => Array
        (
            'title'   => 'continuous subscription',
            'storage' => 'continuous',
            'type'    => 'boolean',
            'widget'  => 'checkbox',
        ),
        'notify' => Array
        (
            'title' => 'notify date',
            'storage' => 'notify',
            'type' => 'date',
            'type_config' => Array
            (
                'storage_type' => 'UNIXTIME'
            ),
            'widget' => 'jsdate',
            'widget_config' => Array
            (
                'show_time' => false,
            ),
        ),
        'product' => Array
        (
            'title' => 'product',
            'storage' => 'product',
            'type' => 'select',
            'type_config' => array
            (
                 'require_corresponding_option' => false,
                 'options' => array(),
            ),
            'widget' => 'autocomplete',
            'widget_config' => array
            (
                'class'       => 'org_openpsa_products_product_dba',
                'id_field'     => 'id',
                'constraints' => array
                (
                    array
                    (
                        'field' => 'delivery',
                        'op' => '=',
                        'value' => org_openpsa_products_product_dba::DELIVERY_SUBSCRIPTION
                    ),
                ),
                'searchfields'  => array
                (
                    'code',
                    'title'
                ),
                'result_headers' => array
                (
                    array
                    (
                        'name' => 'code',
                    ),
                    array
                    (
                        'name' => 'title',
                    ),
                ),
                'orders'        => array
                (
                    array('title'    => 'ASC'),
                    array('code'    => 'ASC'),
                ),
            ),
            'required' => true
        ),
        'pricePerUnit' => Array
        (
            'title' => 'price per unit',
            'storage' => 'pricePerUnit',
            'type' => 'number',
            'widget'  => 'text',
        ),
        'costPerUnit' => Array
        (
            'title' => 'cost per unit',
            'storage' => 'costPerUnit',
            'type' => 'number',
            'widget'  => 'text',
        ),
        'costType' => Array
        (
            'title' => 'cost type',
            'storage' => 'costType',
            'type' => 'text',
            'widget' => 'hidden',
        ),
        'cost' => Array
        (
            'title' => 'cost',
            'storage' => 'cost',
            'type' => 'number',
            'widget'  => 'text',
            'hidden' => true,
        ),
        'units' => Array
        (
            'title' => 'units',
            'storage' => 'units',
            'type' => 'number',
            'widget'  => 'text',
            'hidden' => true
        ),
        'plannedUnits' => Array
        (
            'title' => 'planned units',
            'storage' => 'plannedUnits',
            'type' => 'number',
            'widget'  => 'text',
        ),
        'invoiceByActualUnits' => Array
        (
            'title'   => 'invoice by actual units',
            'storage' => 'invoiceByActualUnits',
            'type'    => 'boolean',
            'widget'  => 'checkbox',
        ),
        'invoiceApprovedOnly' => Array
        (
            'title'   => 'invoice approved only',
            'storage' => 'invoiceApprovedOnly',
            'type'    => 'boolean',
            'widget'  => 'checkbox',
        ),
        'unit' => Array
        (
            'title' => 'invoicing period',
            'storage' => 'unit',
            'type' => 'select',
            'type_config' => Array
            (
                'options' => Array
                (
                    'd' =>  midcom::get()->i18n->get_string('day', 'org.openpsa.products'),       // per day
                    'm' =>  midcom::get()->i18n->get_string('month', 'org.openpsa.products'),     // per month
                    'q' =>  midcom::get()->i18n->get_string('quarter', 'org.openpsa.products'),   // per quarter
                    'hy' => midcom::get()->i18n->get_string('half-year', 'org.openpsa.products'), // per half
                    'y' =>  midcom::get()->i18n->get_string('year', 'org.openpsa.products'),      // per annum
                ),
            ),
            'readonly' => false,
            'widget' => 'select',
        ),
        'at_entry' => Array
        (
            'title'   => '',
            'storage' => null,
            'type'    => 'number',
            'widget'  => 'hidden',
        ),
        'next_cycle' => Array
        (
            'title' => 'next run',
            'storage' => null,
            'type' => 'date',
            'type_config' => Array
            (
                'storage_type' => 'UNIXTIME'
            ),
            'widget' => 'jsdate',
            'widget_config' => Array
            (
                'show_time' => false,
            ),
            'hidden' => true
        ),
    )
),