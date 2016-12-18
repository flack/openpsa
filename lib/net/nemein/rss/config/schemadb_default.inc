'default' => array
(
    'description' => 'feed',
    'l10n_db'     => 'net.nemein.rss',
    'fields'      => array
    (
        'title' => Array
        (
            'title' => 'feed title',
            'storage' => 'title',
            'required' => true,
            'type' => 'text',
            'widget' => 'text',
            'index_method' => 'title',
        ),
        'url' => Array
        (
            'title' => 'feed url',
            'storage' => 'url',
            'required' => true,
            'type' => 'text',
            'widget' => 'text',
        ),
        'keepremoved' => Array
        (
            'title'   => 'keep removed items',
            'storage' => 'keepremoved',
            'type'    => 'boolean',
            'widget'  => 'checkbox',
        ),
        'autoapprove' => Array
        (
            'title'   => 'approve new items automatically',
            'storage' => 'autoapprove',
            'type'    => 'boolean',
            'widget'  => 'checkbox',
            'hidden' => !midcom::get()->config->get('metadata_approval'),
        ),
        'defaultauthor' => array
        (
            'title' => 'default author for items',
            'storage' => 'defaultauthor',
            'type' => 'select',
            'type_config' => array
            (
                 'require_corresponding_option' => false,
                 'options' => array(),
            ),
            'widget' => 'autocomplete',
            'widget_config' => array
            (
                'class' => 'midcom_db_person',
                'component' => 'midgard.admin.asgard',
                'titlefield' => 'name',
                'id_field' => 'id',
                'searchfields' => array
                (
                    'firstname',
                    'lastname',
                    'username',
                ),
                'result_headers' => array
                (
                    array('name' => 'name', 'title' => 'name'),
                    array('name' => 'email', 'title' => 'email'),
                ),
                'orders' => array(),
                'creation_mode_enabled' => true,
                'creation_handler' => midcom_connection::get_url('self') . "__mfa/asgard/object/create/chooser/midgard_person/",
                'creation_default_key' => 'lastname',
            ),
        ),
        'forceauthor' => Array
        (
            'title'   => 'always use the default author',
            'storage' => 'forceauthor',
            'type'    => 'boolean',
            'widget'  => 'checkbox',
        ),
    )
),

