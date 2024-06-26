<?php
return [
    'default' => [
        'description' => 'group',
        'fields'      => [
            'name' => [
                'title'       => 'name',
                'storage'     => 'name',
                'type'        => 'urlname',
                'widget'      => 'text',
                'index_method' => 'noindex',
                'type_config' => [
                    'allow_catenate' => true,
                    'title_field' => 'official',
                    'allow_unclean' => true,
                ],
            ],
            'official' => [
                'title'       => 'official',
                'storage'     => 'official',
                'type'        => 'text',
                'widget'      => 'text',
                'required'    => true,
            ],
            'owner' => [
                'title' => 'owner group',
                'storage' => 'owner',
                'type' => 'select',
                'type_config' => [
                     'require_corresponding_option' => false,
                     'allow_multiple' => false,
                     'options' => [],
                ],
                'widget' => 'autocomplete',
                'widget_config' => [
                    'clever_class' => 'group',
                ],
            ],
            'email' => [
                'title'       => 'email',
                'type'        => 'text',
                'widget'      => 'text',
                'storage'     => 'email',
                'validation'  => 'email',
            ],
            'postcode' => [
                'title'       => 'postcode',
                'type'        => 'text',
                'widget'      => 'text',
                'storage'     => 'postcode',
            ],
            'city' => [
                'title'       => 'city',
                'type'        => 'text',
                'widget'      => 'text',
                'storage'     => 'city',
            ],
            'persons' => [
                'title' => 'members',
                'storage' => null,
                'type' => 'mnrelation',
                'type_config' => [
                    'mapping_class_name' => midcom_db_member::class,
                    'master_fieldname' => 'gid',
                    'member_fieldname' => 'uid',
                    'master_is_id' => true,
                ],
                'widget' => 'autocomplete',
                'widget_config' => [
                    'class' => 'midcom_db_person',
                    'id_field' => 'id',
                    'titlefield' => 'name',
                    'searchfields' => [
                        'lastname',
                        'firstname',
                        'email'
                    ],
                    'result_headers' => [
                        [
                            'name' => 'email',
                        ],
                        [
                            'name' => 'username',
                        ],
                    ],
                ],
            ],
        ],
    ]
];