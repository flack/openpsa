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
            'additional_attachments' => [
                'title' => 'additional_attachments',
                'storage' => 'additional_attachments',
                'type' => 'select',
                'type_config' => [
                    'options' => [],
                    'allow_multiple' => true,
                ],
                'widget' => 'radiocheckselect',
                'hidden' => true,
            ],
        ],
    ],
];