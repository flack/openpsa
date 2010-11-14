'default' => array
(
    'description' => 'product',
    'fields'      => array
    (
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
            'widget'  => 'chooser',
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
                'generate_path_for' => 'title',
            ),
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
                'class'       => 'midcom_db_group',
                'component'   => 'midgard.admin.asgard',
                'titlefield'  => 'official',
                'id_field'     => 'id',
                'constraints' => array
                (
                ),
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
                'options' => Array
                (
                    'p' => 'piece',   // per piece
                    'h' => 'hour',    // per hour
                    'd' => 'day',     // per day
                    'm' => 'month',   // per month
                    'q' => 'quarter', // per quarter
                    'y' => 'year',    // per annum
                ),
            ),
            'widget' => 'select',
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
                    ORG_OPENPSA_PRODUCTS_DELIVERY_SINGLE       => 'single delivery',
                    ORG_OPENPSA_PRODUCTS_DELIVERY_SUBSCRIPTION => 'subscription',
                ),
            ),
            'widget' => 'select',
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
                    ORG_OPENPSA_PRODUCTS_PRODUCT_TYPE_SERVICE   => 'service',
                    ORG_OPENPSA_PRODUCTS_PRODUCT_TYPE_GOODS     => 'material goods',
                    ORG_OPENPSA_PRODUCTS_PRODUCT_TYPE_SOLUTION  => 'solution',
                    ORG_OPENPSA_PRODUCTS_PRODUCT_TYPE_COMPONENT => 'component',
                ),
            ),
            'widget' => 'select',
        ),
        /*
        'start' => Array
        (
            // COMPONENT-REQUIRED
            'title' => 'in market from',
            'storage' => 'start',
            'type' => 'date',
            'type_config' => Array
            (
                'storage_type' => 'UNIXTIME'
            ),
            'widget' => 'jsdate',
            'hidden' => true,
        ),
        'end' => Array
        (
            // COMPONENT-REQUIRED
            'title' => 'in market until',
            'storage' => 'end',
            'type' => 'date',
            'type_config' => Array
            (
                'storage_type' => 'UNIXTIME'
            ),
            'widget' => 'jsdate',
            'hidden' => true,
        ),
        'components' => Array
        (
            'title' => 'components',
            'storage' => null,
            'type' => 'composite',
            'type_config' => Array
            (
                'child_class'                 => 'org_openpsa_products_product_member_dba',
                'child_schemadb'              => 'file:/org/openpsa/products/config/schemadb_member_default.inc',
                'child_foreign_key_fieldname' => 'product',
                'style_element_name'          => 'component',
                'area_element'                => 'tr',
            ),
            'widget' => 'composite',
        ),*/
    )
),