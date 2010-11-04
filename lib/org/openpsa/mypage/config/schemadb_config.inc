'config' => array
(
    'name'        => 'config',
    'description' => 'Default Configuration Schema',
    'fields'      => array
    (
        'show_buddylist' => array
        (
            'title' => 'show buddy list',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'org.openpsa.mypage',
                'name' => 'show_buddylist',
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
    ),
),