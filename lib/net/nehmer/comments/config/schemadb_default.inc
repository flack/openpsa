'comment' => array
(
    'description' => 'default schema',
    'operations' => array
    (
        'save' => 'post'
    ),
    'fields' => array
    (
        'author' => array
        (
            'title' => 'author',
            'description' => '',
            'helptext' => '',

            'storage' => 'author',
            'required' => true,
            'type' => 'text',
            'widget' => 'text',
        ),
        'title' => array
        (
            'title' => 'title',
            'description' => '',
            'helptext' => '',

            'storage' => 'title',
            'required' => true,
            'type' => 'text',
            'widget' => 'text',
        ),
        'content' => array
        (
            'title' => 'content',
            'description' => '',
            'helptext' => '',

            'required' => 'true',
            'storage' => 'content',
            'type' => 'text',
            'type_config' => array ( 'output_mode' => 'markdown' ),
            'widget' => 'textarea',
        ),
        'rating' => array
        (
            'title' => 'rating',
            'storage' => 'rating',
            'type' => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    0 => '',
                    1 => '*',
                    2 => '**',
                    3 => '***',
                    4 => '****',
                    5 => '*****',
                ),
            ),
            'widget' => 'select',
            'hidden' => true,
        ),
        'subscribe' => array
        (
            'title'      => 'subscribe',
            'storage'   => null,
            'type'      => 'boolean',
            'widget'   => 'checkbox',
            'hidden' => !midcom::get('auth')->user,
        ),
    ),
),
