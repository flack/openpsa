'name' => 'org.openpsa.mail',
'icon' => 'stock-icons/16x16/stock_mail-open.png',
'purecode' => true,
'privileges' => array(),
'class_mapping' => array(),
'version' => '9.0.0+git',
'state' => 'stable',
'package.xml' => array
(
    'summary' => 'Email sending and decoding library',
    'description' => 'Library for handling outgoing and incoming emails, including HTML emails and attachments',
    'maintainers' => array
    (
        'rambo' => array
        (
            'name' => 'Eero af Heurlin',
            'email' => 'eero.afheurlin@iki.fi',
            'role' => 'lead',
        ),
        'hirvinen' => array
        (
            'name' => 'Patrik Hirvinen',
            'email' => 'patrik.hirvinen@iki.fi',
            'role' => 'developer',
        ),
    ),
    'dependencies' => array
    (
        'midcom' => array(),
    ),
),
