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
            'hidden' => !$GLOBALS['midcom_config']['metadata_approval'],
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
            'widget' => 'chooser',
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
                'creation_handler' => "{$_MIDGARD['self']}__mfa/asgard/object/create/chooser/midgard_person/",
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
        'item_lang'   => array
        (
            'title'   => 'store items in language',
            'storage' => 'itemlang',
            'type'    => 'select',
            'type_config' => array
            (
                'require_corresponding_option' => false,
                'options' => array(),
            ),
            'widget'  => 'chooser',
            'widget_config' => array
            (
                'class' => 'midcom_baseclasses_database_language',
                'component' => 'midcom.helper.datamanager2',
                'searchfields' => array
                (
                    'code',
                    'name',
                    'native',
                ),
                'result_headers' => array
                (
                    array
                    (
                        'name' => 'code',
                        'title' =>  $_MIDCOM->i18n->get_string('ISO code', 'net.nemein.rss'),
                    ),
                    array
                    (
                        'name' => 'name',
                        'title' =>  $_MIDCOM->i18n->get_string('language (in english)', 'net.nemein.rss'),
                    ),
                    array
                    (
                        'name' => 'native',
                        'title' =>  $_MIDCOM->i18n->get_string('language (native)', 'net.nemein.rss'),
                    ),
                ),
                'id_field' => 'id',
            ),
        ),
    )
),

