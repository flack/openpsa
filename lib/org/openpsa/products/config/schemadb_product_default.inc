'default' => [
    'validation' => [
        [
            'callback' => [new org_openpsa_products_validation, 'is_code_available'],
        ],
    ],
    'description' => 'product',
    'fields'      => [
        'id' => [
            // COMPONENT-REQUIRED
            'title'   => 'id',
            'storage' => 'id',
            'type'    => 'number',
            'widget'  => 'hidden',
            'readonly' => true,
        ],
        'code' => [
            // COMPONENT-REQUIRED
            'title'   => 'code',
            'storage' => 'code',
            'type'    => 'text',
            'widget'  => 'text',
            'required' => true,
        ],
        'title' => [
            // COMPONENT-REQUIRED
            'title' => 'title',
            'storage' => 'title',
            'required' => true,
            'type' => 'text',
            'widget'  => 'text',
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
        'productGroup' => [
            'title' => 'product group',
            'storage' => 'productGroup',
            'type'    => 'select',
            'type_config' => [
                'require_corresponding_option' => false,
                'allow_multiple' => false,
                'options' => [],
            ],
            'widget'  => 'autocomplete',
            'widget_config' => [
                'class' => 'org_openpsa_products_product_group_dba',
                'component' => 'org.openpsa.products',
                'titlefield' => 'title',
                'id_field' => 'id',
                'searchfields' => [
                    'code',
                    'title',
                ],
                'result_headers' => [
                    ['name' => 'code', 'title' => 'code'],
                ],
                'categorize_by_parent_label' => true,
            ],
            'required' => true,
        ],
        'orgOpenpsaObtype' => [
            // COMPONENT-REQUIRED
            'title' => 'type',
            'storage' => 'orgOpenpsaObtype',
            'type' => 'select',
            'required' => true,
            'type_config' => [
                'options' => [
                    org_openpsa_products_product_dba::TYPE_SERVICE   => 'service',
                    org_openpsa_products_product_dba::TYPE_GOODS     => 'material goods',
                    org_openpsa_products_product_dba::TYPE_SOLUTION  => 'solution',
                ],
            ],
            'widget' => 'select',
        ],
        'delivery' => [
            // COMPONENT-REQUIRED
            'title' => 'delivery type',
            'storage' => 'delivery',
            'type' => 'select',
            'required' => true,
            'type_config' => [
                'options' => [
                    org_openpsa_products_product_dba::DELIVERY_SINGLE       => 'single delivery',
                    org_openpsa_products_product_dba::DELIVERY_SUBSCRIPTION => 'subscription',
                ],
            ],
            'widget' => 'select',
        ],
        'price' => [
            'title' => 'price',
            'storage' => 'price',
            'type' => 'number',
            'widget'  => 'text',
        ],
        'unit' => [
            'title' => 'unit',
            'storage' => 'unit',
            'type' => 'select',
            'type_config' => [
                'options' => midcom_baseclasses_components_configuration::get('org.openpsa.products', 'config')->get('unit_options')
            ],
            'widget' => 'select',
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
                'class'       => 'midcom_db_group',
                'component'   => 'midgard.admin.asgard',
                'titlefield'  => 'official',
                'id_field'     => 'id',
                'result_headers' => [
                    ['name' => 'name', 'title' => 'name'],
                ],
                'searchfields'  => [
                    'name',
                    'official'
                ],
                'orders'        => [
                    ['official'    => 'ASC'],
                ],
            ],
        ],
        'cost' => [
            'title' => 'cost',
            'storage' => 'cost',
            'type' => 'number',
            'widget'  => 'text',
        ],
        'costType' => [
            'title' => 'cost type',
            'storage' => 'costType',
            'type' => 'select',
            'type_config' => [
                'options' => [
                    'm' => 'per unit',
                    '%' => '%',
                ],
            ],
            'widget' => 'select',
        ],
    ]
],