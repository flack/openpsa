'name' => 'org.openpsa.products',
'icon' => 'stock-icons/16x16/package.png',
'purecode' => false,
'version' => '9.0.0+git',
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
'package.xml' => array
(
    'summary' => 'Product database',
    'description' => 'Product database',
    'maintainers' => array
    (
        'bergie' => array
        (
            'name' => 'Henri Bergius',
            'email' => 'henri.bergius@iki.fi',
            'role' => 'lead',
        ),
        'netblade' => array
        (
            'name' => 'Oskari Kokko',
            'email' => 'oskari.kokko@nemein.com',
            'role' => 'lead',
        ),
        'xfade' => array
        (
            'name' => 'Niels Breet',
            'email' => 'nbreet@gmail.com',
            'role' => 'lead',
        ),
    ),
    'dependencies' => array
    (
        'midcom.helper.datamanager2' => array(),
        'org.openpsa.qbpager' => array(),
        'org.openpsa.helpers' => array(),
        'midcom' => array(),
    ),
),