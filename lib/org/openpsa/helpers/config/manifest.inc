'name' => 'org.openpsa.helpers',
'purecode' => true,
'privileges' => array(),
'class_mapping' => array(),
'version' => '9.1.0-dev',
'state' => 'stable',
'package.xml' => array
(
    'summary' => 'OpenPSA helpers library',
    'description' => 'OpenPSA helpers for AJAX and workgroup filtering, and some common housekeeping tasks',
    'maintainers' => array
    (
        'rambo' => array
        (
            'name' => 'Eero af Heurlin',
            'email' => 'eero.afheurlin@iki.fi',
            'role' => 'lead',
        ),
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
        'midcom' => array(),
    ),
),
'customdata' => array
(
    'request_handler_plugin' => array
    (
        'class' => 'org_openpsa_helpers_plugin',
        'name' => 'OpenPSA helpers',
        'config' => '',
    ),
),
