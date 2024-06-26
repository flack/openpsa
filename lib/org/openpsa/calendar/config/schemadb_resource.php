<?php
return [
    'default' => [
        'name' => 'default',
        'description' => 'resource',
        'fields' => [
            'title' => [
                'title' => 'title',
                'storage' => 'title',
                'type' => 'text',
                'widget' =>  'text',
                'required' => true,
            ],
            'location' => [
                'title' => 'location',
                'storage' => 'location',
                'type' => 'text',
                'widget' =>  'text',
            ],
            'description' => [
                'title' => 'description',
                'storage' => 'description',
                'type' => 'text',
                'type_config' => [
                    'output_mode' => 'markdown',
                ],
                'widget' => 'markdown',
            ],
        ],
    ]
];