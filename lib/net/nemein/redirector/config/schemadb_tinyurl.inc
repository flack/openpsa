'tinyurl' => array
(
    'description' => 'tinyurl',
    'fields' => array
    (
        'title' => array
        (
            'title' => 'title',
            'storage' => 'title',
            'widget' => 'text',
            'type' => 'text',
            'required' => true,
        ),
        'name' => array
        (
            'title' => 'url name',
            'storage' => 'name',
            'widget' => 'text',
            'type' => 'text',
            'default' => net_nemein_redirector_tinyurl_dba::generate(),
            'required' => true,
        ),
        'redirection_code' => array
        (
            'title' => 'redirection http code',
            'storage' => 'code',
            'type' => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    301 => '301 Moved Permanently',
                    302 => '302 Found',
                    410 => '410 Gone',
                ),
            ),
            'widget' => 'select',
            'default' => 301,
        ),
        'url' => array
        (
            'title' => 'url',
            'storage' => 'url',
            'type' => 'text',
            'widget' => 'text',
            'required' => true,
        ),
        'description' => array
        (
            'title' => 'description',
            'storage' => 'description',
            'type' => 'text',
            'type_config' => array
            (
                'output_mode' => 'html',
            ),
            'widget' => 'textarea',
        ),
    ),
),
