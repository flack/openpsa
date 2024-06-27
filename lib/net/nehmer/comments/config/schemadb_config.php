<?php
return [
    'config' => [
        'description' => 'Default Configuration Schema', /* This is a topic */
        'fields'      => [
            'allow_anonymous' => [
                'title' => 'allow_anonymous',
                'storage' => [
                    'location' => 'configuration',
                    'domain' => 'net.nehmer.comments',
                    'name' => 'allow_anonymous',
                ],
                'type' => 'select',
                'type_config' => [
                    'options' => [
                        '' => 'default setting',
                        '1' => 'yes',
                        '0' => 'no',
                    ],
                ],
                'widget' => 'select',
            ],

            'ratings_enable' => [
                'title' => 'ratings_enable',
                'storage' => [
                    'location' => 'configuration',
                    'domain' => 'net.nehmer.comments',
                    'name' => 'ratings_enable',
                ],
                'type' => 'select',
                'type_config' => [
                    'options' => [
                        '' => 'default setting',
                        '1' => 'yes',
                        '0' => 'no',
                    ],
                ],
                'widget' => 'select',
                'start_fieldset' => [
                    'title' => 'ratings',
                ]
            ],

            'ratings_cache_to_object' => [
                'title' => 'ratings_cache_to_object',
                'storage' => [
                    'location' => 'configuration',
                    'domain' => 'net.nehmer.comments',
                    'name' => 'ratings_cache_to_object',
                ],
                'type' => 'select',
                'type_config' => [
                    'options' => [
                        '' => 'default setting',
                        '1' => 'yes',
                        '0' => 'no',
                    ],
                ],
                'widget' => 'select',
            ],

            'ratings_cache_to_object_property' => [
                'title' => 'ratings_cache_to_object_property',
                'storage' => [
                    'location' => 'configuration',
                    'domain' => 'net.nehmer.comments',
                    'name' => 'ratings_cache_to_object_property',
                ],
                'type' => 'text',
                'widget' => 'text',
            ],

            'ratings_cache_total' => [
                'title' => 'ratings_cache_total',
                'storage' => [
                    'location' => 'configuration',
                    'domain' => 'net.nehmer.comments',
                    'name' => 'ratings_cache_total',
                ],
                'type' => 'select',
                'type_config' => [
                    'options' => [
                        '' => 'default setting',
                        '1' => 'yes',
                        '0' => 'no',
                    ],
                ],
                'widget' => 'select',
                'end_fieldset' => '',
            ],

            'comment_count_cache_to_object' => [
                'title' => 'comment_count_cache_to_object',
                'storage' => [
                    'location' => 'configuration',
                    'domain' => 'net.nehmer.comments',
                    'name' => 'comment_count_cache_to_object',
                ],
                'type' => 'select',
                'type_config' => [
                    'options' => [
                        '' => 'default setting',
                        '1' => 'yes',
                        '0' => 'no',
                    ],
                ],
                'widget' => 'select',
            ],

            'comment_count_cache_to_object_property' => [
                'title' => 'comment_count_cache_to_object_property',
                'storage' => [
                    'location' => 'configuration',
                    'domain' => 'net.nehmer.comments',
                    'name' => 'comment_count_cache_to_object_property',
                ],
                'type' => 'text',
                'widget' => 'text',
            ],

            'schemadb' => [
                'title' => 'schemadb',
                'storage' => [
                    'location' => 'configuration',
                    'domain' => 'net.nehmer.comments',
                    'name' => 'schemadb',
                ],
                'type' => 'text',
                'widget' => 'text',
            ],
        ]
    ]
];