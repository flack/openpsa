<?php
return [
    'default' => [
        'description' => 'wizard',
        'fields' => [
            'existing' => [
                'title' => 'topic',
                'type' => 'select',
                'type_config' => [
                    'options' => [],
                    'allow_other' => true,
                    'require_corresponding_option' => false,
                ],
                'widget' => 'autocomplete',
                'widget_config' => [
                    'class'       => 'midcom_db_topic',
                    'titlefield'  => 'extra',
                    'id_field'     => 'guid',
                    'searchfields' => [
                        'title',
                        'extra',
                        'component',
                        'name',
                    ],
                    'constraints' => [
                        [
                            'field' => 'up',
                            'op' => '=',
                            'value' => 0,
                        ],
                    ],
                    'result_headers' => [
                        [
                            'name' => 'name',
                        ], [
                            'name' => 'component',
                        ],
                    ],
                    'orders' => [
                        [
                            'title' => 'ASC',
                        ], [
                            'extra' => 'ASC',
                        ], [
                            'name' => 'ASC',
                        ],
                    ],
                ],
                'start_fieldset' => [
                    'title' => 'select existing folder'
                ],
                'end_fieldset' => 1
            ],
            'title' => [
                'title' => 'title',
                'storage' => 'extra',
                'type' => 'text',
                'widget' => 'text',
                'start_fieldset' => [
                    'title' => 'create new folder'
                ],
            ],
            'component' => [
                'title' => 'component',
                'storage' => 'component',
                'type' => 'select',
                'type_config' => [
                    'options' => midcom_admin_folder_management::list_components(midcom_core_context::get()->get_key(MIDCOM_CONTEXT_COMPONENT)),
                ],
                'widget' => 'select',
                'end_fieldset' => 1
            ],
        ],
    ]
];