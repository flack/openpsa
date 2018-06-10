'default' => array
(
    'description' => 'salesproject',
    'fields'      => array
    (
    'designation' => array
        (
            'title' => 'designation',
            'storage' => 'designation',
            'type' => 'text',
            'widget' => 'text',
        ),
        'introduction' => array
        (
            'title' => 'introduction',
            'storage' => 'introduction',
            'type' => 'text',
            'type_config' => array
            (
                'output_mode' => 'markdown'
            ),

            'widget' => 'textarea',
            'required' => true,

        ),
        'notice' => array
        (
            'title' => 'notice',
            'storage' => 'notice',
            'type' => 'text',
            'type_config' => array
            (
                'output_mode' => 'markdown'
            ),
            'widget' => 'textarea',
        ),
    )
),