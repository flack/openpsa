'default' => [
    'description' => 'product group',
    'fields'      => [
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
        'up' => [
            'title' => 'parent group',
            'storage' => 'up',
            'type'    => 'select',
            'type_config' => [
                'require_corresponding_option' => false,
                'allow_multiple' => false,
                'options' => [],
            ],
            'widget'  => 'autocomplete',
            'widget_config' => [
                'class' => 'org_openpsa_products_product_group_dba',
                'titlefield' => 'title',
                'id_field' => 'id',
                'searchfields' => [
                    'code',
                    'title',
                ],
                'result_headers' => [
                    ['name' => 'code'],
                ],
                'categorize_by_parent_label' => true,
            ],
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
    ]
],