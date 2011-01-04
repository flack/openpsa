'name' => 'org.openpsa.products',
'icon' => 'stock-icons/16x16/package.png',
'purecode' => false,
'version' => '8.09.9',
'state' => 'stable',
'privileges' => array(),
'class_mapping' => array
(
    'org_openpsa_products_product' => 'org_openpsa_products_product_dba',
    'org_openpsa_products_product_group' => 'org_openpsa_products_product_group_dba',
    'org_openpsa_products_product_member' => 'org_openpsa_products_product_member_dba',
    'org_openpsa_products_product_link' => 'org_openpsa_products_product_link_dba',
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
        'de.bitfolge.feedcreator' => array(),
        'midcom.helper.datamanager2' => array(),
        'org.openpsa.qbpager' => array(),
        'org.openpsa.helpers' => array(),
        'midcom' => array(),
    ),
),
'customdata' => array
(
    'midcom.services.cron' => array
    (
        array
        (
            'handler' => 'org_openpsa_products_cron_groupsync',
            'recurrence' => MIDCOM_CRON_DAY,
        ),
    ),
),