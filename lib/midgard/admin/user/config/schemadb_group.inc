// This is for a midcom_db_person object
'default' => [
    'description' => 'group',
    'l10n_db' => 'midgard.admin.user',
    'fields'      => [
        'name' => [
            // COMPONENT-REQUIRED
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
            // COMPONENT-RECOMMENDED
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
                'id_field' => 'id',
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
                'mapping_class_name' => 'midcom_db_member',
                'master_fieldname' => 'gid',
                'member_fieldname' => 'uid',
                'master_is_id' => true,
            ],
            'widget' => 'autocomplete',
            'widget_config' => [
                'class' => 'midcom_db_person',
                'id_field' => 'id',
                'searchfields' => [
                    'lastname',
                    'firstname',
                    'email'
                ],
                'result_headers' => [
                    [
                        'name' => 'name',
                        'title' => 'Name',
                    ],
                    [
                        'name' => 'email',
                        'title' => 'Email',
                    ],
                    [
                        'name' => 'username',
                        'title' => 'Username',
                    ],
                ],
            ],
        ],
        'centralized_toolbar' => [
            'title'       => 'enable centralized toolbar',
            'type'        => 'privilege',
            'type_config' => [
                'privilege_name' => 'midcom:centralized_toolbar',
                'assignee'       => 'SELF',
                'classname'      => 'midcom_services_toolbars',
            ],
            'widget'      => 'privilege',
            'storage'     => null,
        ],
        'ajax_toolbar' => [
            'title'       => 'enable ajax in toolbar',
            'type'        => 'privilege',
            'type_config' => [
                'privilege_name' => 'midcom:ajax',
                'assignee'       => 'SELF',
                'classname'      => 'midcom_services_toolbars',
            ],
            'widget'      => 'privilege',
            'storage'     => null,
        ],
        'ajax_uimessages' => [
            'title'       => 'enable ajax in uimessages',
            'type'        => 'privilege',
            'type_config' => [
                'privilege_name' => 'midcom:ajax',
                'assignee'       => 'SELF',
                'classname'      => 'midcom_services_uimessages',
            ],
            'widget'      => 'privilege',
            'storage'     => null,
        ],
        'asgard_access' => [
            'title'       => 'enable asgard',
            'type'        => 'privilege',
            'type_config' => [
                'privilege_name' => 'midgard.admin.asgard:access',
                'assignee'       => 'SELF',
                'classname'      => 'midgard_admin_asgard_plugin',
            ],
            'widget'      => 'privilege',
            'storage'     => null,
        ],
        'usermanager_access' => [
            'title'       => 'enable asgard user manager plugin',
            'type'        => 'privilege',
            'type_config' => [
                'privilege_name' => 'midgard.admin.user:access',
                'assignee'       => 'SELF',
                'classname'      => 'midgard_admin_user_plugin',
            ],
            'widget'      => 'privilege',
            'storage'     => null,
        ],
        'midcom_unlock' => [
            'title'       => 'enable unlocking locked objects',
            'type'        => 'privilege',
            'type_config' => [
                'privilege_name' => 'midcom:unlock',
                'assignee'       => 'SELF',
                'classname'      => 'midcom_services_auth',
            ],
            'widget'      => 'privilege',
            'storage'     => null,
        ],
    ],
],
