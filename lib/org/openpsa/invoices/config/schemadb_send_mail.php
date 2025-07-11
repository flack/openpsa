<?php
return [
    'default' => [
        'name' => 'default',
        'description' => 'send mail',
        'fields' => [
            'subject' => [
                'title' => 'subject',
                'storage' => 'subject',
                'type' => 'text',
                'widget' => 'text',
                'required' => true,
            ],
            'message' => [
                'title' => 'message',
                'storage' => 'message',
                'type' => 'text',
                'widget' => 'textarea',
                'required' => true,
            ],
        ],
    ],
];