<?php
return [
    'default' => [
        'name' => 'default',
        'description' => 'send mail',
        'fields' => [
            'to_email' => [
                'title' => 'to_email',
                'storage' => 'to_email',
                'type' => 'text',
                'widget' => 'text',
                'readonly' => true,
            ],
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