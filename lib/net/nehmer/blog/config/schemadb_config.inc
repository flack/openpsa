'config' => array
(
    'name'        => 'config',
    'description' => 'Default Configuration Schema',
    'fields'      => array_merge(
        array
        (
            'index_entries' => array
            (
                'title' => 'index_entries',
                'storage' => array
                (
                    'location' => 'configuration',
                    'domain' => 'net.nehmer.blog',
                    'name' => 'index_entries',
                ),
                'type' => 'text',
                'widget' => 'text',
                'start_fieldset' => array
                (
                    'title' => 'blog settings'
                ),
            ),
            'categories' => array
            (
                'title' => 'categories',
                'storage' => array
                (
                    'location' => 'configuration',
                    'domain' => 'net.nehmer.blog',
                    'name' => 'categories',
                ),
                'type' => 'text',
                'widget' => 'text',
            ),
            'enable_scheduled_publishing' => array
            (
                'title' => 'enable scheduled publishing',
                'storage' => array
                (
                    'location' => 'configuration',
                    'domain' => 'net.nehmer.blog',
                    'name' => 'enable_scheduled_publishing',
                ),
                'type' => 'select',
                'type_config' => array
                (
                    'options' => array
                    (
                        '1' => 'yes',
                        '0' => 'no',
                        '' => 'default setting',
                    ),
                ),
                'widget' => 'radiocheckselect',
            ),
            'disable_permalinks' => array
            (
                'title' => 'disable permalinks',
                'storage' => array
                (
                    'location' => 'configuration',
                    'domain' => 'net.nehmer.blog',
                    'name' => 'disable_permalinks',
                ),
                'type' => 'select',
                'type_config' => array
                (
                    'options' => array
                    (
                        '1' => 'yes',
                        '0' => 'no',
                        '' => 'default setting',
                    ),
                ),
                'widget' => 'radiocheckselect',
            ),
            'disable_indexing' => array
            (
                'title' => 'disable indexing',
                'storage' => array
                (
                    'location' => 'configuration',
                    'domain' => 'net.nehmer.blog',
                    'name' => 'disable_indexing',
                ),
                'type' => 'select',
                'type_config' => array
                (
                    'options' => array
                    (
                        '1' => 'yes',
                        '0' => 'no',
                        '' => 'default setting',
                    ),
                ),
                'widget' => 'radiocheckselect',
            ),
            'list_from_folders' => array
            (
                'title' => 'folders',
                'storage' => array
                (
                    'location' => 'configuration',
                    'domain' => 'net.nehmer.blog',
                    'name' => 'list_from_folders',
                ),
                'type' => 'select',
                'type_config' => array
                (
                    'require_corresponding_option' => false,
                    'allow_multiple' => true,
                    'options' => array (),
                    'multiple_storagemode' => 'imploded_wrapped',
                ),
                'widget' => 'autocomplete',
                'widget_config' => array
                (
                    'class'       => 'midcom_db_topic',
                    'component'   => 'net.nehmer.blog',
                    'titlefield'  => 'extra',
                    'idfield'     => 'guid',
                    'searchfields' => array
                    (
                        'extra',
                        'name',
                    ),
                    'constraints' => array
                    (
                        array
                        (
                            'field' => 'extra',
                            'op' => '<>',
                            'value' => '',
                        ),
                        array
                        (
                            'field' => 'component',
                            'op' => '=',
                            'value' => 'net.nehmer.blog',
                        ),
                    ),
                    'result_headers' => array
                    (
                        array
                        (
                            'name' => 'extra',
                            'title' => midcom::get()->i18n->get_string('title', 'midcom'),
                        ),
                        array
                        (
                            'name' => 'name',
                            'title' => midcom::get()->i18n->get_string('name', 'midcom'),
                        ),
                    ),
                    'categorize_by_parent_label' => true,
                    'orders' => array
                    (
                        array
                        (
                            'extra' => 'ASC',
                        ),
                        array
                        (
                            'name' => 'ASC',
                        ),
                    ),
                ),
                'start_fieldset' => array
                (
                    'title' => 'list articles from folders'
                ),
            ),
            'list_from_folders_categories' => array
            (
                'title' => 'categories',
                'storage' => array
                (
                    'location' => 'configuration',
                    'domain' => 'net.nehmer.blog',
                    'name' => 'list_from_folders_categories',
                ),
                'type' => 'text',
                'widget' => 'text',
                'end_fieldset' => '1',
            ),
            'archive_enable' => array
            (
                'title' => 'archive_enable',
                'storage' => array
                (
                    'location' => 'configuration',
                    'domain' => 'net.nehmer.blog',
                    'name' => 'archive_enable',
                ),
                'type' => 'select',
                'type_config' => array
                (
                    'options' => array
                    (
                        '1' => 'yes',
                        '0' => 'no',
                        '' => 'default setting',
                    ),
                ),
                'widget' => 'radiocheckselect',
            ),
            'archive_item_order' => array
            (
                'title' => 'archive item order',
                'storage' => array
                (
                    'location' => 'configuration',
                    'domain' => 'net.nehmer.blog',
                    'name' => 'archive_item_order',
                ),
                'type' => 'select',
                'type_config' => array
                (
                    'options' => array
                    (
                        'ASC' => 'ascending',
                        'DESC' => 'descending',
                        '' => 'default setting',
                    ),
                ),
                'widget' => 'radiocheckselect',
                'end_fieldset' => '1',
            ),

            'schemadb' => array
            (
                'title' => 'schemadb',
                'storage' => array
                (
                    'location' => 'configuration',
                    'domain' => 'net.nehmer.blog',
                    'name' => 'schemadb',
                ),
                'type' => 'select',
                'type_config' => array
                (
                    'options' => array_merge(['' => 'default setting'], midcom_baseclasses_components_configuration::get('net.nehmer.blog', 'config')->get('schemadbs')),
                ),
                'widget' => 'select',
                'start_fieldset' => array
                (
                    'title' => 'schema settings',
                ),
            ),
            'comments_enable' => array
            (
                'title' => 'comments_enable',
                'storage' => array
                (
                    'location' => 'configuration',
                    'domain' => 'net.nehmer.blog',
                    'name' => 'comments_enable',
                ),
                'type' => 'select',
                'type_config' => array
                (
                    'options' => array
                    (
                        '1' => 'yes',
                        '0' => 'no',
                        '' => 'default setting',
                    ),
                ),
                'widget' => 'radiocheckselect',
            ),
            'ajax_comments_enable' => array
            (
                'title' => 'ajax_comments_enable',
                'storage' => array
                (
                    'location' => 'configuration',
                    'domain' => 'net.nehmer.blog',
                    'name' => 'ajax_comments_enable',
                ),
                'type' => 'select',
                'type_config' => array
                (
                    'options' => array
                    (
                        '1' => 'yes',
                        '0' => 'no',
                        '' => 'default setting',
                    ),
                ),
                'widget' => 'radiocheckselect',
            ),
            'ajax_enable' => array
            (
                'title' => 'ajax_enable',
                'storage' => array
                (
                    'location' => 'configuration',
                    'domain' => 'net.nehmer.blog',
                    'name' => 'enable_ajax_editing',
                ),
                'type' => 'select',
                'type_config' => array
                (
                    'options' => array
                    (
                        '1' => 'yes',
                        '0' => 'no',
                        '' => 'default setting',
                    ),
                ),
                'widget' => 'radiocheckselect',
                'hidden' => !midcom::get()->config->get('enable_ajax_editing'),
            ),
            'rss_subscription_enable' => array
            (
                'title' => 'rss_subscription_enable',
                'storage' => array
                (
                    'location' => 'configuration',
                    'domain' => 'net.nehmer.blog',
                    'name' => 'rss_subscription_enable',
                ),
                'type' => 'select',
                'type_config' => array
                (
                    'options' => array
                    (
                        '1' => 'yes',
                        '0' => 'no',
                        '' => 'default setting',
                    ),
                ),
                'widget' => 'radiocheckselect',
            ),
            'link_to_external_url' => array
            (
                'title' => 'link_to_external_url',
                'storage' => array
                (
                    'location' => 'configuration',
                    'domain' => 'net.nehmer.blog',
                    'name' => 'link_to_external_url',
                ),
                'type' => 'select',
                'type_config' => array
                (
                    'options' => array
                    (
                        '1' => 'yes',
                        '0' => 'no',
                        '' => 'default setting',
                    ),
                ),
                'widget' => 'radiocheckselect',
            ),
            'show_latest_in_navigation' => array
            (
                'title' => 'show latest items in navigation',
                'storage' => array
                (
                    'location' => 'configuration',
                    'domain' => 'net.nehmer.blog',
                    'name' => 'show_latest_in_navigation',
                ),
                'type' => 'select',
                'type_config' => array
                (
                    'options' => array
                    (
                        '1' => 'yes',
                        '0' => 'no',
                        '' => 'default setting',
                    ),
                ),
                'widget' => 'radiocheckselect',
                'start_fieldset' => array
                (
                    'title' => 'navigation options',
                ),
            ),

            'show_navigation_pseudo_leaves' => array
            (
                'title' => 'show pseudo leaves in navigation',
                'helptext' => 'set this to no if you want to hide feeds and archive links in navigation',
                'storage' => array
                (
                    'location' => 'configuration',
                    'domain' => 'net.nehmer.blog',
                    'name' => 'show_navigation_pseudo_leaves',
                ),
                'type' => 'select',
                'type_config' => array
                (
                    'options' => array
                    (
                        '1' => 'yes',
                        '0' => 'no',
                        '' => 'default setting',
                    ),
                ),
                'widget' => 'radiocheckselect',
            ),
            'feeds_in_navigation' => array
            (
                'title' => 'show feeds in navigation',
                'helptext' => 'set this to no if you want to hide feeds link in navigation',
                'storage' => array
                (
                    'location' => 'configuration',
                    'domain' => 'net.nehmer.blog',
                    'name' => 'feeds_in_navigation',
                ),
                'type' => 'select',
                'type_config' => array
                (
                    'options' => array
                    (
                        '1' => 'yes',
                        '0' => 'no',
                        '' => 'default setting',
                    ),
                ),
                'widget' => 'radiocheckselect',
            ),
            'view_in_url' => array
            (
                'title' => 'use view prefix in url',
                'helptext' => 'set to no for de.linkm.newsticker compatibility',
                'storage' => array
                (
                    'location' => 'configuration',
                    'domain' => 'net.nehmer.blog',
                    'name' => 'view_in_url',
                ),
                'type' => 'select',
                'type_config' => array
                (
                    'options' => array
                    (
                        '1' => 'yes',
                        '0' => 'no',
                        '' => 'default setting',
                    ),
                ),
                'widget' => 'radiocheckselect',
                'end_fieldset' => 1,
            ),
        ),
        net_nemein_rss_helpers::default_rss_config_schema_fields('net.nehmer.blog')
    ),
),
