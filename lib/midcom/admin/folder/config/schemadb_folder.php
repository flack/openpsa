<?php
return [
    'default' => [
        'description' => 'topic',
        'l10n_db' => 'midcom.admin.folder',
        'fields' => [
            'name' => [
                'title' => 'url name',
                'storage' => 'name',
                'type' => 'urlname',
                'widget' => 'text',
            ],
            'title' => [
                'title' => 'title',
                'storage' => 'extra',
                'type' => 'text',
                'widget' => 'text',
                'required' => true,
            ],
            'component' => [
                'title' => 'component',
                'storage' => 'component',
                'type' => 'select',
                'type_config' => [
                    'options' => midcom_admin_folder_management::list_components(midcom_core_context::get()->get_key(MIDCOM_CONTEXT_COMPONENT)),
                ],
                'widget' => 'select',
            ],
            'style' => [
                'title' => 'style template',
                'storage' => 'style',
                'type' => 'select',
                'type_config' => [
                    'options' => midcom_admin_folder_management::list_styles(),
                ],
                'widget' => 'select',
            ],
            'style_inherit' => [
                'title' => 'inherit style',
                'storage' => 'styleInherit',
                'type' => 'boolean',
                'widget' => 'checkbox',
            ],
            'nav_order' => [
                'title' => 'nav order',
                'storage' => [
                    'location' => 'configuration',
                    'domain' => 'midcom.helper.nav',
                    'name' => 'navorder',
                ],
                'type' => 'select',
                'type_config' => [
                    'options' => [
                        MIDCOM_NAVORDER_DEFAULT => 'default sort order',
                        MIDCOM_NAVORDER_TOPICSFIRST => 'folders first',
                        MIDCOM_NAVORDER_ARTICLESFIRST => 'pages first',
                        MIDCOM_NAVORDER_SCORE => 'by score',
                    ],
                ],
                'widget' => 'select',
            ],
            'page_class' => [
                'title' => 'folder page class',
                'storage' => [
                    'location' => 'configuration',
                    'domain'   => 'midcom.services.metadata',
                    'name'     => 'page_class',
                ],
                'type' => 'text',
                'widget' => 'text',
                'write_privilege' => [
                    'privilege' => 'midcom.admin.folder:template_management',
                ],
            ],
        ],
    ]
];