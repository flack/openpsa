'default' => array
(
    'description' => 'product link',
    'fields'      => array
    (
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
        'product' => Array
        (
            'title' => 'product',
            'storage' => 'product',
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
                'class' => 'org_openpsa_products_product_dba',
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
                    array('name' => 'productGroup', 'title' => 'productGroup'),
                ),
                'generate_path_for' => 'productGroup',
            ),
        ),
    )
),