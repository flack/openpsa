'config' => [
    'name'        => 'config',
    'description' => 'Default Configuration Schema',
    'fields'      => array_merge([
            'index_entries' => [
                'title' => 'index_entries',
                'storage' => [
                    'location' => 'configuration',
                    'domain' => 'net.nehmer.blog',
                    'name' => 'index_entries',
                ],
                'type' => 'text',
                'widget' => 'text',
                'start_fieldset' => [
                    'title' => 'blog settings'
                ],
            ],
            'categories' => [
                'title' => 'categories',
                'storage' => [
                    'location' => 'configuration',
                    'domain' => 'net.nehmer.blog',
                    'name' => 'categories',
                ],
                'type' => 'text',
                'widget' => 'text',
            ],
            'enable_scheduled_publishing' => [
                'title' => 'enable scheduled publishing',
                'storage' => [
                    'location' => 'configuration',
                    'domain' => 'net.nehmer.blog',
                    'name' => 'enable_scheduled_publishing',
                ],
                'type' => 'select',
                'type_config' => [
                    'options' => [
                        '1' => 'yes',
                        '0' => 'no',
                        '' => 'default setting',
                    ],
                ],
                'widget' => 'radiocheckselect',
            ],
            'disable_permalinks' => [
                'title' => 'disable permalinks',
                'storage' => [
                    'location' => 'configuration',
                    'domain' => 'net.nehmer.blog',
                    'name' => 'disable_permalinks',
                ],
                'type' => 'select',
                'type_config' => [
                    'options' => [
                        '1' => 'yes',
                        '0' => 'no',
                        '' => 'default setting',
                    ],
                ],
                'widget' => 'radiocheckselect',
            ],
            'disable_indexing' => [
                'title' => 'disable indexing',
                'storage' => [
                    'location' => 'configuration',
                    'domain' => 'net.nehmer.blog',
                    'name' => 'disable_indexing',
                ],
                'type' => 'select',
                'type_config' => [
                    'options' => [
                        '1' => 'yes',
                        '0' => 'no',
                        '' => 'default setting',
                    ],
                ],
                'widget' => 'radiocheckselect',
            ],
            'archive_enable' => [
                'title' => 'archive_enable',
                'storage' => [
                    'location' => 'configuration',
                    'domain' => 'net.nehmer.blog',
                    'name' => 'archive_enable',
                ],
                'type' => 'select',
                'type_config' => [
                    'options' => [
                        '1' => 'yes',
                        '0' => 'no',
                        '' => 'default setting',
                    ],
                ],
                'widget' => 'radiocheckselect',
            ],
            'archive_item_order' => [
                'title' => 'archive item order',
                'storage' => [
                    'location' => 'configuration',
                    'domain' => 'net.nehmer.blog',
                    'name' => 'archive_item_order',
                ],
                'type' => 'select',
                'type_config' => [
                    'options' => [
                        'ASC' => 'ascending',
                        'DESC' => 'descending',
                        '' => 'default setting',
                    ],
                ],
                'widget' => 'radiocheckselect'
            ],
            'list_from_folders' => [
                'title' => 'folders',
                'storage' => [
                    'location' => 'configuration',
                    'domain' => 'net.nehmer.blog',
                    'name' => 'list_from_folders',
                ],
                'type' => 'select',
                'type_config' => [
                    'require_corresponding_option' => false,
                    'allow_multiple' => true,
                    'options' => [],
                    'multiple_storagemode' => 'imploded_wrapped',
                ],
                'widget' => 'autocomplete',
                'widget_config' => [
                    'class'       => 'midcom_db_topic',
                    'titlefield'  => 'extra',
                    'idfield'     => 'guid',
                    'searchfields' => [
                        'extra',
                        'name',
                    ],
                    'constraints' => [
                        [
                            'field' => 'extra',
                            'op' => '<>',
                            'value' => '',
                        ],
                        [
                            'field' => 'component',
                            'op' => '=',
                            'value' => 'net.nehmer.blog',
                        ],
                    ],
                    'result_headers' => [
                        [
                            'name' => 'name',
                        ],
                    ],
                    'categorize_by_parent_label' => true,
                    'orders' => [
	                    [
                            'extra' => 'ASC',
                        ],
                        [
                            'name' => 'ASC',
                        ],
                    ],
                ],
                'start_fieldset' => [
                    'title' => 'list articles from folders'
                ],
            ],
            'list_from_folders_categories' => [
                'title' => 'categories',
                'storage' => [
                    'location' => 'configuration',
                    'domain' => 'net.nehmer.blog',
                    'name' => 'list_from_folders_categories',
                ],
                'type' => 'text',
                'widget' => 'text',
                'end_fieldset' => '2',
            ],

            'schemadb' => [
                'title' => 'schemadb',
                'storage' => [
                    'location' => 'configuration',
                    'domain' => 'net.nehmer.blog',
                    'name' => 'schemadb',
                ],
                'type' => 'select',
                'type_config' => [
                    'options' => array_merge(['' => 'default setting'], midcom_baseclasses_components_configuration::get('net.nehmer.blog', 'config')->get('schemadbs')),
                ],
                'widget' => 'select',
                'start_fieldset' => [
                    'title' => 'schema settings',
                ],
            ],
            'comments_enable' => [
                'title' => 'comments_enable',
                'storage' => [
                    'location' => 'configuration',
                    'domain' => 'net.nehmer.blog',
                    'name' => 'comments_enable',
                ],
                'type' => 'select',
                'type_config' => [
                    'options' => [
                        '1' => 'yes',
                        '0' => 'no',
                        '' => 'default setting',
                    ],
                ],
                'widget' => 'radiocheckselect',
            ],
            'ajax_comments_enable' => [
                'title' => 'ajax_comments_enable',
                'storage' => [
                    'location' => 'configuration',
                    'domain' => 'net.nehmer.blog',
                    'name' => 'ajax_comments_enable',
                ],
                'type' => 'select',
                'type_config' => [
                    'options' => [
                        '1' => 'yes',
                        '0' => 'no',
                        '' => 'default setting',
                    ],
                ],
                'widget' => 'radiocheckselect',
            ],
            'rss_subscription_enable' => [
                'title' => 'rss_subscription_enable',
                'storage' => [
                    'location' => 'configuration',
                    'domain' => 'net.nehmer.blog',
                    'name' => 'rss_subscription_enable',
                ],
                'type' => 'select',
                'type_config' => [
                    'options' => [
                        '1' => 'yes',
                        '0' => 'no',
                        '' => 'default setting',
                    ],
                ],
                'widget' => 'radiocheckselect',
            ],
            'link_to_external_url' => [
                'title' => 'link_to_external_url',
                'storage' => [
                    'location' => 'configuration',
                    'domain' => 'net.nehmer.blog',
                    'name' => 'link_to_external_url',
                ],
                'type' => 'select',
                'type_config' => [
                    'options' => [
                        '1' => 'yes',
                        '0' => 'no',
                        '' => 'default setting',
                    ],
                ],
                'widget' => 'radiocheckselect',
                'end_fieldset' => '1',
            ],
            'show_latest_in_navigation' => [
                'title' => 'show latest items in navigation',
                'storage' => [
                    'location' => 'configuration',
                    'domain' => 'net.nehmer.blog',
                    'name' => 'show_latest_in_navigation',
                ],
                'type' => 'select',
                'type_config' => [
                    'options' => [
                        '1' => 'yes',
                        '0' => 'no',
                        '' => 'default setting',
                    ],
                ],
                'widget' => 'radiocheckselect',
                'start_fieldset' => [
                    'title' => 'navigation options',
                ],
            ],

            'show_navigation_pseudo_leaves' => [
                'title' => 'show pseudo leaves in navigation',
                'helptext' => 'set this to no if you want to hide feeds and archive links in navigation',
                'storage' => [
                    'location' => 'configuration',
                    'domain' => 'net.nehmer.blog',
                    'name' => 'show_navigation_pseudo_leaves',
                ],
                'type' => 'select',
                'type_config' => [
                    'options' => [
                        '1' => 'yes',
                        '0' => 'no',
                        '' => 'default setting',
                    ],
                ],
                'widget' => 'radiocheckselect',
            ],
            'feeds_in_navigation' => [
                'title' => 'show feeds in navigation',
                'helptext' => 'set this to no if you want to hide feeds link in navigation',
                'storage' => [
                    'location' => 'configuration',
                    'domain' => 'net.nehmer.blog',
                    'name' => 'feeds_in_navigation',
                ],
                'type' => 'select',
                'type_config' => [
                    'options' => [
                        '1' => 'yes',
                        '0' => 'no',
                        '' => 'default setting',
                    ],
                ],
                'widget' => 'radiocheckselect',
            ],
            'view_in_url' => [
                'title' => 'use view prefix in url',
                'helptext' => 'set to no for de.linkm.newsticker compatibility',
                'storage' => [
                    'location' => 'configuration',
                    'domain' => 'net.nehmer.blog',
                    'name' => 'view_in_url',
                ],
                'type' => 'select',
                'type_config' => [
                    'options' => [
                        '1' => 'yes',
                        '0' => 'no',
                        '' => 'default setting',
                    ],
                ],
                'widget' => 'radiocheckselect',
                'end_fieldset' => 1,
            ],
        ],
        class_exists('net_nemein_rss_helpers') ? net_nemein_rss_helpers::default_rss_config_schema_fields('net.nehmer.blog') : []
    ),
],
