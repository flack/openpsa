'default' => array
(
    'description' => 'product group',
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
        'up' => Array
        (
            'title' => 'parent group',
            'storage' => 'up',
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
    )
),