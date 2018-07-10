'tinyurl' => [
    'description' => 'tinyurl',
    'fields' => [
        'title' => [
            'title' => 'title',
            'storage' => 'title',
            'widget' => 'text',
            'type' => 'text',
            'required' => true,
        ],
        'name' => [
            'title' => 'url name',
            'storage' => 'name',
            'widget' => 'text',
            'type' => 'text',
            'default' => net_nemein_redirector_tinyurl_dba::generate(),
            'required' => true,
        ],
        'redirection_code' => [
            'title' => 'redirection http code',
            'storage' => 'code',
            'type' => 'select',
            'type_config' => [
                'options' => [
                    301 => '301 Moved Permanently',
                    302 => '302 Found',
                    410 => '410 Gone',
                ],
            ],
            'widget' => 'select',
            'default' => 301,
        ],
        'url' => [
            'title' => 'url',
            'storage' => 'url',
            'type' => 'text',
            'widget' => 'text',
            'required' => true,
        ],
        'description' => [
            'title' => 'description',
            'storage' => 'description',
            'type' => 'text',
            'type_config' => [
                'output_mode' => 'html',
            ],
            'widget' => 'textarea',
        ],
    ],
],
