'default' => [
    'description' => 'salesproject',
    'fields'      => [
    'designation' => [
            'title' => 'designation',
            'storage' => 'designation',
            'type' => 'text',
            'widget' => 'text',
        ],
        'introduction' => [
            'title' => 'introduction',
            'storage' => 'introduction',
            'type' => 'text',
            'type_config' => [
                'output_mode' => 'markdown'
            ],

            'widget' => 'textarea',
            'required' => true,

        ],
        'notice' => [
            'title' => 'notice',
            'storage' => 'notice',
            'type' => 'text',
            'type_config' => [
                'output_mode' => 'markdown'
            ],
            'widget' => 'textarea',
        ],
    ]
],