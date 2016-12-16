'name' => 'org.openpsa.products',
'icon' => 'stock-icons/16x16/package.png',
'purecode' => false,
'version' => '9.1.0+git',
'state' => 'stable',
'privileges' => array(),
'class_mapping' => array
(
    'org_openpsa_products_product' => 'org_openpsa_products_product_dba',
    'org_openpsa_products_product_group' => 'org_openpsa_products_product_group_dba',
),
'watches' => array
(
    array
    (
        'classes' => array
        (
            'org_openpsa_products_product_group_dba',
        ),
        'operations' => MIDCOM_OPERATION_DBA_ALL,
    ),
),
'description' => 'Product database',