'default' => array
(
    'description' => 'single delivery',
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
            'widget' => 'textarea',
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
            'widget' => 'chooser',
            'widget_config' => array
            (
                'class'       => 'org_openpsa_contacts_group_dba',
                'component'   => 'org.openpsa.contacts',
                'titlefield'  => 'official',
                'id_field'     => 'id',
                'constraints' => array
                (
                ),
                'searchfields'  => array
                (
                    'name',
                    'official'
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
            'type' => 'number',
            'widget' => 'hidden',
        ),
        'pricePerUnit' => Array
        (
            'title' => 'price per unit',
            'storage' => 'pricePerUnit',
            'type' => 'number',
            'widget'  => 'text',
        ),
        'unit' => Array
        (
            'title' => 'unit',
            'storage' => 'unit',
            'type' => 'select',
            'type_config' => Array
            (
                'options' => Array
                (
                    'p' =>  $_MIDCOM->i18n->get_string('unit', 'org.openpsa.products'),       // per piece
                    'h' =>  $_MIDCOM->i18n->get_string('hour', 'org.openpsa.products'),       // per hour
                    'd' =>  $_MIDCOM->i18n->get_string('day', 'org.openpsa.products'),       // per day
                    'm' =>  $_MIDCOM->i18n->get_string('month', 'org.openpsa.products'),     // per month
                    'q' =>  $_MIDCOM->i18n->get_string('quarter', 'org.openpsa.products'),   // per quarter
                    'hy' => $_MIDCOM->i18n->get_string('half-year', 'org.openpsa.products'), // per half
                    'y' =>  $_MIDCOM->i18n->get_string('year', 'org.openpsa.products'),      // per annum
                ),
            ),
            'readonly' => true,
            'widget' => 'select',
        ),
        'units' => Array
        (
            'title' => 'units',
            'storage' => 'units',
            'type' => 'number',
            'widget'  => 'text',
        ),
        'plannedUnits' => Array
        (
            'title' => 'planned units',
            'storage' => 'plannedUnits',
            'type' => 'number',
            'widget'  => 'text',
        ),
        'price' => Array
        (
            'title' => 'price',
            'storage' => 'price',
            'type' => 'number',
            'widget'  => 'text',
            'readonly' => true,
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
            'type' => 'select',
            'type_config' => Array
            (
                'options' => Array
                (
                    'm' => 'per unit',
                    '%' => '%',
                ),
            ),
            'widget' => 'select',
            'readonly' => true,
        ),
        'cost' => Array
        (
            'title' => 'cost',
            'storage' => 'cost',
            'type' => 'number',
            'widget'  => 'text',
            'readonly' => true,
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
            'callback' => 'validate_subscription',
            'autoload_snippet' => 'file:/org/openpsa/sales/config/validation_callbacks.php',
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
            'widget' => 'chooser',
            'widget_config' => array
            (
                'class'       => 'org_openpsa_contacts_group_dba',
                'component'   => 'org.openpsa.contacts',
                'titlefield'  => 'official',
                'id_field'     => 'id',
                'constraints' => array
                (
                ),
                'searchfields'  => array
                (
                    'name',
                    'official'
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
        'description' => Array
        (
            'title' => 'description',
            'storage' => 'description',
            'type' => 'text',
            'type_config' => Array (
                'output_mode' => 'markdown'
            ),
            'widget' => 'textarea',
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
        'product' => Array
        (
            'title' => 'product',
            'storage' => 'product',
            'type' => 'number',
            'widget' => 'hidden',
        ),
        'pricePerUnit' => Array
        (
            'title' => 'price per unit',
            'storage' => 'pricePerUnit',
            'type' => 'number',
            'widget'  => 'text',
        ),
        'units' => Array
        (
            'title' => 'units',
            'storage' => 'units',
            'type' => 'number',
            'widget'  => 'text',
        ),
        'plannedUnits' => Array
        (
            'title' => 'planned units',
            'storage' => 'plannedUnits',
            'type' => 'number',
            'widget'  => 'text',
            'hidden' => true
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
                    'd' =>  $_MIDCOM->i18n->get_string('day', 'org.openpsa.products'),       // per day
                    'm' =>  $_MIDCOM->i18n->get_string('month', 'org.openpsa.products'),     // per month
                    'q' =>  $_MIDCOM->i18n->get_string('quarter', 'org.openpsa.products'),   // per quarter
                    'hy' => $_MIDCOM->i18n->get_string('half-year', 'org.openpsa.products'), // per half
                    'y' =>  $_MIDCOM->i18n->get_string('year', 'org.openpsa.products'),      // per annum
                ),
            ),
            'readonly' => false,
            'widget' => 'select',
        ),
        'price' => Array
        (
            'title' => 'price',
            'storage' => 'price',
            'type' => 'number',
            'widget'  => 'text',
            'readonly' => true,
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
            'type' => 'select',
            'type_config' => Array
            (
                'options' => Array
                (
                    'm' => 'per unit',
                    '%' => '%',
                ),
            ),
            'widget' => 'select',
            'readonly' => true,
        ),
        'cost' => Array
        (
            'title' => 'cost',
            'storage' => 'cost',
            'type' => 'number',
            'widget'  => 'text',
            'hidden' => true,
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
    )
),