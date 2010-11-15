'config' => array
(
    'name'        => 'config',
    'description' => 'component configuration',
    'fields'      => array
    (
        'schemadb_account_creation' => array
        (
            'title' => 'schemadb_account_creation',
            'storage' => array
            (
                'location' => 'configuration',
                'domain'   => 'net.nehmer.account',
                'name'     => 'schemadb_account_creation',
            ),
            'type' => 'text',
            'widget' => 'text',
            'start_fieldgroup' => array
            (
                'title' => 'schemadb settings',
            ),
        ),
        'schemadb_account' => array
        (
            'title' => 'schemadb_account',
            'storage' => array
            (
                'location' => 'configuration',
                'domain'   => 'net.nehmer.account',
                'name'     => 'schemadb_account',
            ),
            'type' => 'text',
            'widget' => 'text',
            'end_fieldgroup' => '',
        ),
        'password_minlength' => array
        (
            'title' => 'password_minlength',
            'storage' => array
            (
                'location' => 'configuration',
                'domain'   => 'net.nehmer.account',
                'name'     => 'password_minlength',
            ),
            'type' => 'text',
            'widget' => 'text',
        ),
        'publish_all_on_activation' => array
        (
            'title' => 'publish_all_on_activation',
            'storage' => array
            (
                'location' => 'configuration',
                'domain'   => 'net.nehmer.account',
                'name'     => 'publish_all_on_activation',
            ),
            'type' => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    '' => 'default setting',
                    '1' => 'yes',
                    '0' => 'no',
                ),
            ),
            'widget' => 'select',
        ),
        
        // Registrations
        'allow_register' => array
        (
            'title' => 'allow users to register',
            'storage' => array
            (
                'location' => 'configuration',
                'domain'   => 'net.nehmer.account',
                'name'     => 'allow_register',
            ),
            'type' => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    '' => 'default setting',
                    '1' => 'yes',
                    '0' => 'no',
                ),
            ),
            'widget' => 'select',
            'begin_fieldgroup' => array
            (
                'title' => 'user registrations',
            ),
        ),
        'allow_change_username' => array
        (
            'title' => 'allow users to change their usernames',
            'storage' => array
            (
                'location' => 'configuration',
                'domain'   => 'net.nehmer.account',
                'name'     => 'allow_change_username',
            ),
            'type' => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    '' => 'default setting',
                    '1' => 'yes',
                    '0' => 'no',
                ),
            ),
            'widget' => 'select',
        ),
        'username_is_email' => array
        (
            'title' => 'username is email address',
            'storage' => array
            (
                'location' => 'configuration',
                'domain'   => 'net.nehmer.account',
                'name'     => 'username_is_email',
            ),
            'type' => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    '' => 'default setting',
                    '1' => 'yes',
                    '0' => 'no',
                ),
            ),
            'widget' => 'select',
        ),
        'allow_cancel_membership' => array
        (
            'title' => 'allow users to cancel their memberships',
            'datatype' => 'text',
            'storage' => array
            (
                'location' => 'configuration',
                'domain'   => 'net.nehmer.account',
                'name'     => 'allow_cancel_membership',
            ),
            'type' => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    '' => 'default setting',
                    '1' => 'yes',
                    '0' => 'no',
                ),
            ),
            'widget' => 'select',
        ),
        'allow_publish' => array
        (
            'title' => 'allow publishing user accounts',
            'storage' => array
            (
                'location' => 'configuration',
                'domain'   => 'net.nehmer.account',
                'name'     => 'allow_publish',
            ),
            'type' => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    '' => 'default setting',
                    '1' => 'yes',
                    '0' => 'no',
                ),
            ),
            'widget' => 'select',
        ),
        'allow_view_by_username' => array
        (
            'title' => 'allow viewing by username',
            'storage' => array
            (
                'location' => 'configuration',
                'domain'   => 'net.nehmer.account',
                'name'     => 'allow_publish',
            ),
            'type' => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    '' => 'default setting',
                    '1' => 'yes',
                    '0' => 'no',
                ),
            ),
            'widget' => 'select',
        ),
        'allow_view_by_username_only' => array
        (
            'title' => 'allow publishing user accounts without /view/',
            'storage' => array
            (
                'location' => 'configuration',
                'domain'   => 'net.nehmer.account',
                'name'     => 'allow_publish_direct',
            ),
            'type' => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    '' => 'default setting',
                    '1' => 'yes',
                    '0' => 'no',
                ),
            ),
            'widget' => 'select',
        ),
        'require_activation' => array
        (
            'title' => 'require administrator approval for a new user account',
            'storage' => array
            (
                'location' => 'configuration',
                'domain'   => 'net.nehmer.account',
                'name'     => 'require_activation',
            ),
            'type' => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    '' => 'default setting',
                    '1' => 'yes',
                    '0' => 'no',
                ),
            ),
            'widget' => 'select',
        ),
        'show_lost_password_in_navi' => array
        (
            'title' => 'show lost password in navi',
            'storage' => array
            (
                'location' => 'configuration',
                'domain'   => 'net.nehmer.account',
                'name'     => 'show_lost_password_in_navi',
            ),
            'type' => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    '' => 'default setting',
                    '1' => 'yes',
                    '0' => 'no',
                ),
            ),
            'widget' => 'select',
        ),
        'redirect_edit_to' => array
        (
            'title' => 'where to redirect from edit page',
            'storage' => array
            (
                'location' => 'configuration',
                'domain'   => 'net.nehmer.account',
                'name'     => 'redirect_edit_to',
            ),
            'type' => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    '' => 'default setting',
                    'view' => 'view page',
                    'publish' => 'publish account details page',
                ),
            ),
            'widget' => 'select',
        ),
        'administrator_email' => array
        (
            'title' => 'administrator email address where a notice will be sent',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'net.nehmer.account',
                'name' => 'administrator_email',
            ),
            'type' => 'text',
            'widget' => 'text',
            'end_fieldgroup' => '',
        ),
        'openidprovider_link' => array
        (
            'title' => 'Absolute URL of net.nemein.openidprovider installation (optional)',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'net.nehmer.account',
                'name' => 'openidprovider_link',
            ),
            'type' => 'text',
            'widget' => 'text',
        ),
        'assign_to_group' => array
        (
            'title' => 'assign new users to group',
            'storage' => array
            (
                'location' => 'configuration',
                'domain'   => 'net.nehmer.account',
                'name'     => 'assign_to_group',
            ),
            'type' => 'select',
            'type_config' => array
            (
                'require_corresponding_option' => false,
                'options' => array (),
                'allow_multiple' => true,
                'multiple_storagemode' => 'serialized',
                'allow_other' => true,
            ),
            'widget' => 'chooser',
            'widget_config' => array
            (
                'class' => 'midcom_db_group',
                'component' => 'midgard.admin.asgard',
                'titlefield' => 'name',
                'id_field' => 'guid',
                'searchfields' => array
                (
                    'name',
                    'official',
                ),
                'result_headers' => array
                (
                    array('name' => 'name', 'title' => 'name'),
                    array('name' => 'official', 'title' => 'official'),
                ),
                'constraints' => array
                (
                    array
                    (
                        'field' => 'name',
                        'op' => '<>',
                        'value' => '',
                    ),
                ),
                'orders' => array(),
                'creation_mode_enabled' => true,
                'creation_handler' => "{$_MIDGARD['self']}__mfa/asgard/object/create/chooser/midgard_group/",
                'creation_default_key' => 'official',
            ),   
        ),
    ),
),
