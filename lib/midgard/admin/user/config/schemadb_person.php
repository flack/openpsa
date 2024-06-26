<?php
return [
    // This is for a midcom_db_person object
    'default' => [
        'description' => 'person',
        'l10n_db' => 'midgard.admin.user',
        'templates' => [
            'view' => midcom\datamanager\template\view::class,
            'form' => midcom\datamanager\template\form::class,
            'plaintext' => midcom\datamanager\template\plaintext::class,
            'csv' => midcom\datamanager\template\csv::class,
        ],
        'fields' => [
            'firstname' => [
                'title' => 'firstname',
                'type' => 'text',
                'widget' => 'text',
                'storage' => 'firstname',
                'index_method' => 'noindex',
            ],
            'lastname' => [
                'title' => 'lastname',
                'type' => 'text',
                'widget' => 'text',
                'storage' => 'lastname',
                'index_method' => 'noindex',
            ],
            'email' => [
                'title' => 'email',
                'type' => 'text',
                'widget' => 'text',
                'storage' => 'email',
                'validation' => 'email',
            ],
            'workphone' => [
                'title' => 'workphone',
                'type' => 'text',
                'widget' => 'text',
                'storage' => 'workphone'
            ],
            'street' => [
                'title' => 'street',
                'type' => 'text',
                'widget' => 'text',
                'storage' => 'street',
            ],
            'postcode' => [
                'title' => 'postcode',
                'type' => 'text',
                'widget' => 'text',
                'storage' => 'postcode',
            ],
            'city' => [
                'title' => 'city',
                'type' => 'text',
                'widget' => 'text',
                'storage' => 'city',
            ],
            'groups' => [
                'title' => 'groups',
                'storage' => null,
                'type' => 'mnrelation',
                'type_config' => [
                    'mapping_class_name' => 'midcom_db_member',
                    'master_fieldname' => 'uid',
                    'member_fieldname' => 'gid',
                    'master_is_id' => true,
                    'allow_multiple' => true,
                ],
                'widget' => 'autocomplete',
                'widget_config' => [
                    'class' => 'midcom_db_group',
                    'result_headers' => [
    	                [
                            'name' => 'name',
                        ],
                    ],
                    'searchfields' => [
                        'name',
                        'official',
                    ],
                    'orders' => [
                        ['owner' => 'ASC'],
                        ['name' => 'ASC'],
                    ],
                    'id_field' => 'id',
                ],
            ],
        ],
    ]
];