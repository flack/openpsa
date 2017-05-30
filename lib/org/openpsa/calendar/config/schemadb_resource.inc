'default' => array
(
    'name' => 'default',
    'description' => 'resource',
    'fields' => array
    (
        'title' => array
        (
            'title' => 'title',
            'storage' => 'title',
            'type' => 'text',
            'widget' =>  'text',
            'required' => true,
        ),
        'location' => array
        (
            'title' => 'location',
            'storage' => 'location',
            'type' => 'text',
            'widget' =>  'text',
        ),
        'description' => array
        (
            'title' => 'description',
            'storage' => 'description',
            'type' => 'text',
            'type_config' => array
            (
                'output_mode' => 'markdown',
            ),
            'widget' => 'markdown',
        ),
    ),
),