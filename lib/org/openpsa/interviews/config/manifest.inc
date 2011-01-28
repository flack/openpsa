'name' => 'org.openpsa.interviews',
'icon' => 'stock-icons/16x16/stock_landline-phone.png',
'purecode' => false,
'version' => '9.0pre',
'privileges' => array(),
'class_mapping' => array(),
'package.xml' => array
(
    'summary' => 'Interviews',
    'description' => 'Phone interview manager',
    'maintainers' => array
    (
        'bergie' => array
        (
            'name' => 'Henri Bergius',
            'email' => 'henri.bergius@iki.fi',
            'role' => 'lead',
            'active' => 'no',
        ),
    ),
    'dependencies' => array
    (
        'midcom.helper.datamanager2' => array(),
        'org.openpsa.contactwidget' => array(),
        'org.openpsa.directmarketing' => array(),
        'midcom' => array(),
    ),
),
'state' => 'stable',
