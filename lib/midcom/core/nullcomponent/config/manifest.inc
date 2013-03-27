'name' => 'midcom.core.nullcomponent',
'icon' => 'stock-icons/16x16/script.png',
'purecode' => false,
'version' => '0.0.1',
'state' => 'devel',
'privileges' => array(),
'class_mapping' => array(),
/**
 * This is used by the PEAR packaging.
 */
'package.xml' => array
(
    'summary' => 'MidCOM nomodule handler',
    'description' => 'MidCOM nomodule handler',
    'maintainers' => array
    (
        'tarjei' => array
        (
            'name' => 'Tarjei Huse',
            'email' => 'tarjei@nu.no',
            'role' => 'lead',
        ),
    ),
    'dependencies' => Array
    (
        'midcom.helper.datamanager2' => array(),
    ),
),