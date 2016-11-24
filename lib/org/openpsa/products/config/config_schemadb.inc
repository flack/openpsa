'config' => array
(
    'description' => 'Default Configuration Schema',
    'fields' => array
    (
        'product_page_title' => array
        (
            'title' => 'product page title',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'org.openpsa.products',
                'name' => 'product_page_title',
            ),
            'type' => 'text',
            'widget' => 'text',
        ),
        'groups_listing_order' => array
        (
            'title' => 'index order for groups when browsing groups',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'org.openpsa.products',
                'name' => 'groups_listing_order',
            ),
            'type' => 'text',
            'widget' => 'text',
        ),
        'products_listing_order' => array
        (
            'title' => 'index order for products when browsing groups',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'org.openpsa.products',
                'name' => 'products_listing_order',
            ),
            'type' => 'text',
            'widget' => 'text',
        ),
        'group_list_products' => array
        (
            'title' => 'list group products',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'org.openpsa.products',
                'name' => 'group_list_products',
            ),
            'type' => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    '' => 'default setting',
                    '1' => 'yes',
                    '0' => 'no',
                ),
            ),
            'widget' => 'select',
        ),
        'listing_primary' => array
        (
            'title' => 'if we have subgroups and products in group, what shall we show',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'org.openpsa.products',
                'name' => 'listing_primary',
            ),
            'type' => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    '' => 'default setting',
                    'products' => 'Products',
                    'groups' => 'Groups',
                ),
            ),
            'widget' => 'select',
        ),
        'products_per_page' => array
        (
            'title' => 'products per page',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'org.openpsa.products',
                'name' => 'products_per_page',
            ),
            'type' => 'text',
            'widget' => 'text',
        ),
        'show_items_in_feed' => array
        (
            'title' => 'number of items in feed',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'org.openpsa.products',
                'name' => 'show_items_in_feed',
            ),
            'type' => 'text',
            'widget' => 'text',
        ),
        'root_group' => array
        (
            'title' => 'root product group',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'org.openpsa.products',
                'name' => 'root_group',
            ),
            'type' => 'select',
            'type_config' => array
            (
                'options' => org_openpsa_products_product_group_dba::list_groups(0, '', 'guid'),
            ),
            'widget' => 'select',
            'end_fieldset' => '',
        ),
        'index_products' => array
        (
            'title' => 'Index products',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'org.openpsa.products',
                'name' => 'index_products',
            ),
            'type' => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    '' => 'default setting',
                    '1' => 'yes',
                    '0' => 'no',
                ),
            ),
            'widget' => 'select',
            'start_fieldset' => array
            (
                'title' => 'Indexer related',
            ),
        ),
        'index_groups' => array
        (
            'title' => 'Index groups',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'org.openpsa.products',
                'name' => 'index_groups',
            ),
            'type' => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    '' => 'default setting',
                    '1' => 'yes',
                    '0' => 'no',
                ),
            ),
            'widget' => 'select',
            'end_fieldset' => '',
        ),
        'schemadb_product' => array
        (
            'title' => 'product schema database',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'org.openpsa.products',
                'name' => 'schemadb_product',
            ),
            'type' => 'text',
            'widget' => 'text',
            'start_fieldset' => array
            (
                'title' => 'advanced schema and data settings',
            ),
        ),
        'schemadb_group' => array
        (
            'title' => 'product group schema database',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'org.openpsa.products',
                'name' => 'schemadb_group',
            ),
            'type' => 'text',
            'widget' => 'text',
        ),
    )
),
