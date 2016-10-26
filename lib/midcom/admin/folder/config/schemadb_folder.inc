'default' => array
(
    'description' => 'topic',
    'l10n_db' => 'midcom.admin.folder',
    'fields' => array
    (
        'name' => array
        (
            'title' => 'url name',
            'storage' => 'name',
            'type' => 'urlname',
            'widget' => 'text',
        ),
        'title' => array
        (
            'title' => 'title',
            'storage' => 'extra',
            'type' => 'text',
            'widget' => 'text',
            'required' => true,
        ),
        'component' => array
        (
            'title' => 'component',
            'storage' => 'component',
            'type' => 'select',
            'type_config' => array
            (
                'options' => midcom_admin_folder_management::list_components(midcom_core_context::get()->get_key(MIDCOM_CONTEXT_COMPONENT)),
            ),
            'widget' => 'midcom_admin_folder_selectcomponent',
        ),
        'style' => array
        (
            'title' => 'style template',
            'storage' => 'style',
            'type' => 'select',
            'type_config' => array
            (
                'options' => midcom_admin_folder_management::list_styles(),
            ),
            'widget' => 'select',
        ),
        'style_inherit' => array
        (
            'title' => 'inherit style',
            'storage' => 'styleInherit',
            'type' => 'boolean',
            'widget' => 'checkbox',
        ),
        'nav_order' => array
        (
            'title' => 'nav order',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'midcom.helper.nav',
                'name' => 'navorder',
            ),
            'type' => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    MIDCOM_NAVORDER_DEFAULT => midcom::get()->i18n->get_string('default sort order', 'midcom.admin.folder'),
                    MIDCOM_NAVORDER_TOPICSFIRST => midcom::get()->i18n->get_string('folders first', 'midcom.admin.folder'),
                    MIDCOM_NAVORDER_ARTICLESFIRST => midcom::get()->i18n->get_string('pages first', 'midcom.admin.folder'),
                    MIDCOM_NAVORDER_SCORE => midcom::get()->i18n->get_string('by score', 'midcom.admin.folder'),
                ),
            ),
            'widget' => 'select',
        ),
        'page_class' => array
        (
            'title' => 'folder page class',
            'storage' => array
            (
                'location' => 'configuration',
                'domain'   => 'midcom.services.metadata',
                'name'     => 'page_class',
            ),
            'type' => 'text',
            'widget' => 'text',
            'write_privilege' => array
            (
                'privilege' => 'midcom.admin.folder:template_management',
            ),
        ),
    ),
),