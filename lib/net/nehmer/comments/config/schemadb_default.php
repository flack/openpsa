<?php
return [
    'comment' => [
        'description' => 'default schema',
        'operations' => [
            'save' => 'post'
        ],
        'fields' => [
            'csrf' => [
                'title' => 'csrf',
                'type' => 'text',
                'widget' => 'csrf',
                'required' => true,
            ],
            'author' => [
                'title' => 'author',
                'storage' => 'author',
                'required' => true,
                'type' => 'text',
                'widget' => 'text',
            ],
            'title' => [
                'title' => 'title',
                'storage' => 'title',
                'required' => true,
                'type' => 'text',
                'widget' => 'text',
            ],
            'content' => [
                'title' => 'content',
                'required' => true,
                'storage' => 'content',
                'type' => 'text',
                'type_config' => [
                    'output_mode' => 'markdown'
                ],
                'widget' => 'textarea',
            ],
            'rating' => [
                'title' => 'rating',
                'storage' => 'rating',
                'type' => 'select',
                'type_config' => [
                    'options' => [
                        0 => '',
                        1 => '*',
                        2 => '**',
                        3 => '***',
                        4 => '****',
                        5 => '*****',
                    ],
                ],
                'widget' => 'select',
                'hidden' => true,
            ],
            'subscribe' => [
                'title'      => 'subscribe',
                'storage'   => null,
                'type'      => 'boolean',
                'widget'   => 'checkbox',
                'hidden' => !midcom::get()->auth->user,
            ],
        ],
    ]
];
