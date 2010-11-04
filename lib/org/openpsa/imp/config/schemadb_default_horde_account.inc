'default' => array
(
    'name'        => 'default',
    'description' => 'Horde/Imp account settings',
    'fields'      => array
    (
        'uri'  => array
        (
            'title'   => 'Horde/Imp server (fully qualified) URL',
            'type'      => 'text',
            'widget'      => 'text',
            'storage' => array
            (
                'location' => 'configuration',
                'domain'   => 'org.openpsa.imp',
                'name'     => 'imp_uri',
            ),
            'start_fieldset'  => array 
            (
                'title'     => 'Account information',
                'css_group' => 'area',
            ),
        ),
        'username'  => array
        (
            'title'   => 'Horde/Imp username',
            'type'      => 'text',
            'widget'      => 'text',
            'storage' => array
            (
                'location' => 'configuration',
                'domain'   => 'org.openpsa.imp',
                'name'     => 'imp_username',
            ),
        ),
        'password'  => array
        (
            'title'   => 'Horde/Imp password',
            'type'      => 'text',
            'widget'      => 'text',
            'storage' => array
            (
                'location' => 'configuration',
                'domain'   => 'org.openpsa.imp',
                'name'     => 'imp_password',
            ),
            'end_fieldset'    => '',
        ),
    ),
),
'globalserver' => array
(
    'name'        => 'globalserver',
    'description' => 'Horde/Imp account settings',
    'fields'      => array
    (
        'username'  => array
        (
            'title'   => 'Horde/Imp username',
            'type'      => 'text',
            'widget'      => 'text',
            'storage' => array
            (
                'location' => 'configuration',
                'domain'   => 'org.openpsa.imp',
                'name'     => 'imp_username',
            ),
        ),
        'password'  => array
        (
            'title'   => 'Horde/Imp password',
            'type'      => 'text',
            'widget'      => 'text',
            'storage' => array
            (
                'location' => 'configuration',
                'domain'   => 'org.openpsa.imp',
                'name'     => 'imp_password',
            ),
            'end_fieldset'    => '',
        ),
    ),
),