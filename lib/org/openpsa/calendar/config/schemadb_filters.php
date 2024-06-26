<?php
return [
    'default' => [
        'description' => 'filters',
        'fields' => [
            'people' => [
                'title' => 'people',
                'storage' => [
                    'location' => 'parameter',
                    'domain' => 'org.openpsa.calendar.filters',
                    'name' => 'people',
                ],
                'type' => 'select',
                'type_config' => [
                    'options' => [],
                    'allow_other' => true,
                    'allow_multiple' => true,
                    'multiple_storagemode' => 'serialized',
                    'require_corresponding_option' => false,
                ],
                'widget' => 'autocomplete',
                'widget_config' => [
                    'clever_class' => 'contact',
                    'constraints' => [
                        [
                            'field' => 'username',
                            'op'    => '<>',
                            'value' => '',
                        ],
                    ],
                    'id_field' => 'guid',
                ],
            ],
            'groups' => [
                'title' => 'groups',
                'storage' => [
                    'location' => 'parameter',
                    'domain' => 'org.openpsa.calendar.filters',
                    'name' => 'groups',
                ],
                'type' => 'select',
                'type_config' => [
                    'options' => [],
                    'allow_other' => true,
                    'allow_multiple' => true,
                    'multiple_storagemode' => 'serialized',
                    'require_corresponding_option' => false,
                ],
                'widget' => 'autocomplete',
                'widget_config' => [
                    'clever_class' => 'group',
                    'id_field' => 'guid',
                ],
            ],
            'resources' => [
                'title' => 'resources',
                'storage' => [
                    'location' => 'parameter',
                    'domain' => 'org.openpsa.calendar.filters',
                    'name' => 'resources',
                ],
                'type' => 'select',
                'type_config' => [
                    'options' => [],
                    'allow_other' => true,
                    'allow_multiple' => true,
                    'multiple_storagemode' => 'serialized',
                    'require_corresponding_option' => false,
                ],
                'widget' => 'autocomplete',
                'widget_config' => [
                    'class' => org_openpsa_calendar_resource_dba::class,
                    'result_headers' => [
                        [
                            'name' => 'location',
                        ],
                    ],
                    'searchfields' => [
                        'title',
                        'name',
                        'location',
                    ],
                    'orders' => [
                        ['title' => 'ASC'],
                        ['name' => 'ASC'],
                        ['location' => 'ASC'],
                    ],
                    'id_field' => 'id',
                ],
            ],
        ],
    ]
];
