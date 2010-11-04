'default' => array
(
    'description' => 'component',
    'fields'      => array
    (
        'component' => Array
        (
            // COMPONENT-REQUIRED
            'title' => 'component',
            'storage' => 'component',
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
        'pieces' => Array
        (
            // COMPONENT-REQUIRED
            'title' => 'pieces',
            'storage' => 'pieces',
            'required' => true,
            'type' => 'number',
            'widget'  => 'text',
        ),
        'description' => Array
        (
            'title' => 'description',
            'storage' => 'description',
            'type' => 'text',
            'widget' => 'text',
        ),
    )
),
'group' => array
(
    'description' => 'component group',
    'fields'      => array
    (
        'component' => Array
        (
            // COMPONENT-REQUIRED
            'title' => 'component group',
            'storage' => 'componentGroup',
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
            'required' => true,
        ),
        'pieces' => Array
        (
            // COMPONENT-REQUIRED
            'title' => 'pieces',
            'storage' => 'pieces',
            'required' => true,
            'type' => 'number',
            'widget'  => 'text',
        ),
        'description' => Array
        (
            'title' => 'description',
            'storage' => 'description',
            'type' => 'text',
            'widget' => 'text',
        ),
    )
),