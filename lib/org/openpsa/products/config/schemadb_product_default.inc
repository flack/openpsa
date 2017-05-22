'default' => array
(
    'validation' => Array
    (
        Array
        (
            'callback' => array(new org_openpsa_products_validation, 'is_code_available'),
        ),
    ),
    'description' => 'product',
    'fields'      => array
    (
        'id' => Array
        (
            // COMPONENT-REQUIRED
            'title'   => 'id',
            'storage' => 'id',
            'type'    => 'number',
            'widget'  => 'hidden',
            'readonly' => true,
        ),
        'code' => Array
        (
            // COMPONENT-REQUIRED
            'title'   => 'code',
            'storage' => 'code',
            'type'    => 'text',
            'widget'  => 'text',
            'required' => true,
        ),
        'title' => Array
        (
            // COMPONENT-REQUIRED
            'title' => 'title',
            'storage' => 'title',
            'required' => true,
            'type' => 'text',
            'widget'  => 'text',
        ),
        'description' => Array
        (
            'title' => 'description',
            'storage' => 'description',
            'type' => 'text',
            'type_config' => Array
            (
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
        'productGroup' => Array
        (
            'title' => 'product group',
            'storage' => 'productGroup',
            'type'    => 'select',
            'type_config' => array
            (
                'require_corresponding_option' => false,
                'allow_multiple' => false,
                'options' => array(),
            ),
            'widget'  => 'autocomplete',
            'widget_config' => array
            (
                'class' => 'org_openpsa_products_product_group_dba',
                'component' => 'org.openpsa.products',
                'titlefield' => 'title',
                'id_field' => 'id',
                'searchfields' => array
                (
                    'code',
                    'title',
                ),
                'result_headers' => array
                (
                    array('name' => 'title', 'title' => 'title'),
                    array('name' => 'code', 'title' => 'code'),
                ),
                'categorize_by_parent_label' => true,
            ),
            'required' => true,
        ),
        'orgOpenpsaObtype' => Array
        (
            // COMPONENT-REQUIRED
            'title' => 'type',
            'storage' => 'orgOpenpsaObtype',
            'type' => 'select',
            'required' => true,
            'type_config' => Array
            (
                'options' => Array
                (
                    org_openpsa_products_product_dba::TYPE_SERVICE   => 'service',
                    org_openpsa_products_product_dba::TYPE_GOODS     => 'material goods',
                    org_openpsa_products_product_dba::TYPE_SOLUTION  => 'solution',
                ),
            ),
            'widget' => 'select',
        ),
        'delivery' => Array
        (
            // COMPONENT-REQUIRED
            'title' => 'delivery type',
            'storage' => 'delivery',
            'type' => 'select',
            'required' => true,
            'type_config' => Array
            (
                'options' => Array
                (
                    org_openpsa_products_product_dba::DELIVERY_SINGLE       => 'single delivery',
                    org_openpsa_products_product_dba::DELIVERY_SUBSCRIPTION => 'subscription',
                ),
            ),
            'widget' => 'select',
        ),
        'price' => Array
        (
            'title' => 'price',
            'storage' => 'price',
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
                'options' => midcom_baseclasses_components_configuration::get('org.openpsa.products', 'config')->get('unit_options')
            ),
            'widget' => 'select',
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
                'class'       => 'midcom_db_group',
                'component'   => 'midgard.admin.asgard',
                'titlefield'  => 'official',
                'id_field'     => 'id',
                'result_headers' => array
                (
                    array('name' => 'name', 'title' => 'name'),
                    array('name' => 'official', 'title' => 'official'),
                ),
                'searchfields'  => array
                (
                    'name',
                    'official'
                ),
                'orders'        => array
                (
                    array('official'    => 'ASC'),
                ),
            ),
        ),
        'cost' => Array
        (
            'title' => 'cost',
            'storage' => 'cost',
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
        ),
    )
),