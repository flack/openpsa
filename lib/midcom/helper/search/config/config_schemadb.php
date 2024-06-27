<?php
return [
    'config' => [
        'name'        => 'config',
        'description' => 'Default Configuration Schema', /* This is a topic */
        'fields'      => [
            'results_per_page' => [
                'title' => 'results_per_page',
                'type' => 'select',
                'storage' => 'config',
                'widget' => 'select',
                'type_config' => [
                    'options' => [
                        '' => 'default setting',
                        '5' => '5',
                        '10' => '10',
                        '15' => '15',
                        '20' => '20',
                        '30' => '30',
                        '50' => '50',
                    ]
                ]
            ],
            'search_help_message' => [
                'title' => 'search_help_message',
                'type' => 'select',
                'storage' => 'config',
                'widget' => 'select',
                'type_config' => [
                    'options' => [
                        '' => 'default setting',
                        'lucene' => 'Lucene'
                    ]
                ]
            ],
        ]
    ]
];