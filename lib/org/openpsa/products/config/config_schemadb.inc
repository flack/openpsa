'config' => [
    'description' => 'Default Configuration Schema',
    'fields' => [
        'product_page_title' => [
            'title' => 'product page title',
            'storage' => [
                'location' => 'configuration',
                'domain' => 'org.openpsa.products',
                'name' => 'product_page_title',
            ],
            'type' => 'text',
            'widget' => 'text',
            'start_fieldset' => [
                'title' => 'display settings',
            ],
        ],
        'show_items_in_feed' => [
            'title' => 'number of items in feed',
            'storage' => [
                'location' => 'configuration',
                'domain' => 'org.openpsa.products',
                'name' => 'show_items_in_feed',
            ],
            'type' => 'text',
            'widget' => 'text',
        ],
        'root_group' => [
            'title' => 'root product group',
            'storage' => [
                'location' => 'configuration',
                'domain' => 'org.openpsa.products',
                'name' => 'root_group',
            ],
            'type' => 'select',
            'type_config' => [
                'options' => org_openpsa_products_product_group_dba::list_groups(0, '', 'guid'),
            ],
            'widget' => 'select',
            'end_fieldset' => '',
        ],
        'index_products' => [
            'title' => 'Index products',
            'storage' => [
                'location' => 'configuration',
                'domain' => 'org.openpsa.products',
                'name' => 'index_products',
            ],
            'type' => 'select',
            'type_config' => [
                'options' => [
                    '' => 'default setting',
                    '1' => 'yes',
                    '0' => 'no',
                ],
            ],
            'widget' => 'select',
            'start_fieldset' => [
                'title' => 'Indexer related',
            ],
        ],
        'index_groups' => [
            'title' => 'Index groups',
            'storage' => [
                'location' => 'configuration',
                'domain' => 'org.openpsa.products',
                'name' => 'index_groups',
            ],
            'type' => 'select',
            'type_config' => [
                'options' => [
                    '' => 'default setting',
                    '1' => 'yes',
                    '0' => 'no',
                ],
            ],
            'widget' => 'select',
            'end_fieldset' => '',
        ],
        'schemadb_product' => [
            'title' => 'product schema database',
            'storage' => [
                'location' => 'configuration',
                'domain' => 'org.openpsa.products',
                'name' => 'schemadb_product',
            ],
            'type' => 'text',
            'widget' => 'text',
            'start_fieldset' => [
                'title' => 'advanced schema and data settings',
            ],
        ],
        'schemadb_group' => [
            'title' => 'product group schema database',
            'storage' => [
                'location' => 'configuration',
                'domain' => 'org.openpsa.products',
                'name' => 'schemadb_group',
            ],
            'type' => 'text',
            'widget' => 'text',
        ],
    ]
],
