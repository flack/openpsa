'default' => [
    'description' => 'article',
    'fields'      => [
        'name' => [
            // COMPONENT-REQUIRED
            'title' => 'url name',
            'storage' => 'name',
            'type' => 'urlname',
            'widget' => 'text',
            /**
             * the urlname datatype makes this redundant, but if you wish to check for some other privilege
             * set that here.
             *
            'write_privilege' => [
                'privilege' => 'midcom:urlname',
            ],
             */
        ],
        'title' => [
            // COMPONENT-REQUIRED
            'title' => 'title',
            'storage' => 'title',
            'required' => true,
            'type' => 'text',
            'widget' => 'text',
        ],
        'content' => [
            // COMPONENT-REQUIRED
            'title' => 'content',
            'storage' => 'content',
            'type' => 'text',
            'type_config' => [
                'output_mode' => 'html',
            ],
            'widget' => 'tinymce',
        ],
        'image' => [
            'title' => 'image',
            'storage' => null,
            'type' => 'image',
            'type_config' => [
                'filter_chain' => 'resize(800,600)',
                'auto_thumbnail' => [200,200],
            ],
            'widget' => 'image',
            'hidden' => true,
        ],
    ],
], // default

