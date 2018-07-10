'name' => 'org.openpsa.products',
'icon' => 'cubes',
'purecode' => false,
'privileges' => [],
'class_mapping' => [
    'org_openpsa_products_product' => 'org_openpsa_products_product_dba',
    'org_openpsa_products_product_group' => 'org_openpsa_products_product_group_dba',
],
'watches' => [
    [
        'classes' => [
            'org_openpsa_products_product_group_dba',
        ],
        'operations' => MIDCOM_OPERATION_DBA_ALL,
    ],
],
'description' => 'Product database',