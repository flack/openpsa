'default' => array
(
    'description' => 'product component',
    'fields'      => array
    (
        'title' => Array
        (
            // COMPONENT-REQUIRED
            'title' => 'title',
            'storage' => 'title',
            'required' => true,
            'type' => 'text',
            'widget'  => 'text',
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
                    'p' => 'unit',       // per piece
                    'h' => 'hour',    // per hour
                    'd' => 'day',     // per day
                    'm' => 'month',   // per month
                    'q' => 'quarter', // per quarter
                    'y' => 'year',    // per annum
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
        'price' => Array
        (
            'title' => 'price',
            'storage' => 'price',
            'type' => 'number',
            'widget'  => 'text',
            'readonly' => true,
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
        'purchase_ok' => Array
        (
            'title'   => 'purchase invoice',
            'storage' => 'parameter',
            'type'    => 'boolean',
            'widget'  => 'checkbox',
        ),
    )
),
'subscription' => array
(
    'description' => 'subscription component',
    'fields'      => array
    (
        'title' => Array
        (
            // COMPONENT-REQUIRED
            'title' => 'title',
            'storage' => 'title',
            'required' => true,
            'type' => 'text',
            'widget'  => 'text',
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
                    'p' => 'unit',       // per piece
                    'h' => 'hour',    // per hour
                    'd' => 'day',     // per day
                    'm' => 'month',   // per month
                    'q' => 'quarter', // per quarter
                    'y' => 'year',    // per annum
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
        'price' => Array
        (
            'title' => 'price',
            'storage' => 'price',
            'type' => 'number',
            'widget'  => 'text',
            'readonly' => true,
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