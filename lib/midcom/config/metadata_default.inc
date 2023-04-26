/* Attention to all developers:
 * Changes to this schema must be documented in midcom_helper_metadata's
 * class introduction
 */
'metadata' => [
    'description' => 'metadata',
    'l10n_db'     => 'midcom.admin.folder',
    'fields' => [
        'opengraph_type' => [
            'start_fieldset' => [
                'title' => 'opengraph information',
            ],
            'end_fieldset' => '',
            'title' => 'opengraph type',
            'type' => 'select',
            'type_config' => [
                'options' => midcom::get()->metadata->get_opengraph_types(),
            ],
            'widget' => 'select',
            'storage' => [
                'location' => 'configuration',
                'domain'   => 'midcom.helper.metadata',
                'name'     => 'opengraph_type',
            ],
            'default' => midcom::get()->metadata->get_opengraph_type_default(),
            'hidden' => !midcom::get()->config->get('metadata_opengraph'),
        ],
        'navnoentry' => [
            'start_fieldset' => [
                'title' => 'visibility settings',
            ],
            'title' => 'hide from navigation',
            'helptext' => 'objects with no navigation entry are still accessible on-site if the url is known',
            'type' => 'boolean',
            'storage' => 'metadata',
            'widget' => 'checkbox',
        ],
        'do_not_index' => [
            'title' => "do not index (midcom's indexer)",
            'helptext' => "hides object from midcom indexer'",
            'type' => 'boolean',
            'storage' => [
                'location' => 'parameter',
                'domain' => 'midcom.services.indexer',
                'name' => "do_not_index",
            ],
            'widget' => 'checkbox',
            'hidden' => !midcom::get()->indexer->enabled(),
        ],
        'schedulestart' => [
            'hidden' => !midcom::get()->config->get('metadata_scheduling'),
            'title' => 'schedule start',
            'type' => 'date',
            'type_config' => [
                // MidCOM DBA converts dates to/from unixtimes to MgdSchema's ISO format
                'storage_type' => 'UNIXTIME',
            ],
            'storage' => 'metadata',
            'widget' => 'jsdate',
        ],
        'scheduleend' => [
            'hidden' => !midcom::get()->config->get('metadata_scheduling'),
            'title' => 'schedule end',
            'type' => 'date',
            'type_config' => [
                // MidCOM DBA converts dates to/from unixtimes to MgdSchema's ISO format
                'storage_type' => 'UNIXTIME',
            ],
            'storage' => 'metadata',
            'widget' => 'jsdate',
        ],
        'score' => [
            'title' => 'arrangement score',
            'storage' => 'metadata',
            'type' => 'number',
            'widget' => 'text',
            'write_privilege' => [
                'privilege' => 'midcom.admin.folder:topic_management',
            ],
        ],
        'hidden' => [
            'title' => 'prevent entering',
            'type' => 'boolean',
            'storage' => 'metadata',
            'widget' => 'checkbox',
            'end_fieldset' => '',
        ],
        'description' => [
            'title' => 'description',
            'storage' => [
                'location' => 'configuration',
                'domain'   => 'midcom.helper.metadata',
                'name'     => 'description',
            ],
            'type' => 'text',
            'widget' => 'textarea',
            'start_fieldset' => [
                'title' => 'metadata',
            ],
        ],
        'keywords' => [
            'title' => 'keywords',
            'storage' => [
                'location' => 'configuration',
                'domain'   => 'midcom.helper.metadata',
                'name'     => 'keywords',
            ],
            'type' => 'text',
            'widget' => 'text',
        ],
        'tags' => [
            'title' => 'tags',
            'type' => 'tags',
            'widget' => 'text',
        ],
        'published' => [
            'title' => 'published',
            'storage' => 'metadata',
            'type' => 'date',
            'type_config' => [
                // MidCOM DBA converts dates to/from unixtimes to MgdSchema's ISO format
                'storage_type' => 'UNIXTIME',
            ],
            'widget' => 'jsdate',
        ],
        'authors' => [
            'title' => 'authors',
            'storage' => 'metadata',
            'type' => 'select',
            'type_config' => [
                'require_corresponding_option' => false,
                'options' => [],
                'allow_other' => true,
                'allow_multiple' => true,
                'multiple_storagemode' => 'imploded_wrapped',
            ],
            'widget' => 'autocomplete',
            'widget_config' => [
                'class' => 'midcom_db_person',
                'titlefield' => 'name',
                'id_field' => 'guid',
                'searchfields' => [
                    'firstname',
                    'lastname',
                    'username',
                ],
                'result_headers' => [
                    ['name' => 'email'],
                ],
                'orders' => [],
                'creation_mode_enabled' => true,
                'creation_handler' => midcom_connection::get_url('self') . "__mfa/asgard/object/create/chooser/midgard_person/",
                'creation_default_key' => 'lastname',
            ],
        ],
        'owner' => [
            'title' => 'owner',
            'storage' => 'metadata',
            'type' => 'select',
            'type_config' => [
                'require_corresponding_option' => false,
                'options' => [],
                'allow_other' => true,
                'allow_multiple' => false,
            ],
            'widget' => 'autocomplete',
            'widget_config' => [
                'class' => 'midcom_db_group',
                'titlefield' => 'name',
                'id_field' => 'guid',
                'searchfields' => [
                    'name',
                    'official',
                ],
                'result_headers' => [
                    ['name' => 'official'],
                ],
                'categorize_by_parent_label' => true,
                'orders' => [],
                'creation_mode_enabled' => true,
                'creation_handler' => midcom_connection::get_url('self') . "__mfa/asgard/object/create/chooser/midgard_group/",
                'creation_default_key' => 'official',
            ],
            'end_fieldset' => '',
        ],
        'created' => [
            'title' => 'created',
            'storage' => 'metadata',
            'type' => 'date',
            'type_config' => [
                // MidCOM DBA converts dates to/from unixtimes to MgdSchema's ISO format
                'storage_type' => 'UNIXTIME',
            ],
            'start_fieldset' => [
                'title' => 'internal metadata',
            ],
            'widget' => 'jsdate',
            'widget_config' => [
                'hide_seconds' => false
            ],
            'readonly' => true,
        ],
        'creator' => [
            'title' => 'creator',
            'storage' => 'metadata',
            'type' => 'select',
            'type_config' => [
                'require_corresponding_option' => false,
                'options' => [],
                'allow_other' => true,
            ],
            'widget' => 'autocomplete',
            'widget_config' => [
                'class' => 'midcom_db_person',
                'titlefield' => 'name',
                'id_field' => 'guid',
                'searchfields' => [
                    'firstname',
                    'lastname',
                ],
                'result_headers' => [
                    ['name' => 'email'],
                ],
                'orders' => [['lastname' => 'ASC']],
            ],
            'readonly' => true,
        ],
        'revised' => [
            'title' => 'revised',
            'storage' => 'metadata',
            'type' => 'date',
            'type_config' => [
                // MidCOM DBA converts dates to/from unixtimes to MgdSchema's ISO format
                'storage_type' => 'UNIXTIME',
            ],
            'widget' => 'jsdate',
            'widget_config' => [
                'hide_seconds' => false
            ],
            'readonly' => true,
        ],
        'revisor' => [
            'title' => 'revisor',
            'storage' => 'metadata',
            'type' => 'select',
            'type_config' => [
                'require_corresponding_option' => false,
                'options' => [],
                'allow_other' => true,
            ],
            'widget' => 'autocomplete',
            'widget_config' => [
                'class' => 'midcom_db_person',
                'titlefield' => 'name',
                'id_field' => 'guid',
                'searchfields' => [
                    'firstname',
                    'lastname',
                ],
                'result_headers' => [
                    ['name' => 'email'],
                ],
                'orders' => [['lastname' => 'ASC']],
            ],
            'readonly' => true,
        ],
        'revision' => [
            'title' => 'revision',
            'storage' => 'metadata',
            'type' => 'number',
            'widget' => 'text',
            'readonly' => true,
        ],
        'size' => [
            'title' => 'size',
            'storage' => 'metadata',
            'type' => 'number',
            'widget' => 'text',
            'readonly' => true,
            'end_fieldset' => '',
        ],

        // Approvals
        'approved' => [
            'start_fieldset' => [
                'title' => 'approvals',
            ],
            'title' => 'approved',
            'storage' => 'metadata',
            'type' => 'date',
            'type_config' => [
                // MidCOM DBA converts dates to/from unixtimes to MgdSchema's ISO format
                'storage_type' => 'UNIXTIME',
            ],
            'widget' => 'jsdate',
            'readonly' => true,
            'hidden' => !midcom::get()->config->get('metadata_approval'),
        ],
        'approver' => [
            'hidden' => !midcom::get()->config->get('metadata_approval'),
            'title' => 'approver',
            'storage' => 'metadata',
            'type' => 'select',
            'type_config' => [
                'require_corresponding_option' => false,
                'options' => [],
                'allow_other' => true,
            ],
            'widget' => 'autocomplete',
            'widget_config' => [
                'class' => 'midcom_db_person',
                'titlefield' => 'name',
                'id_field' => 'guid',
                'searchfields' => [
                    'firstname',
                    'lastname',
                ],
                'result_headers' => [
                    ['name' => 'email'],
                ],
                'orders' => [['lastname' => 'ASC']],
            ],
            'readonly' => true,
            'end_fieldset' => '',
        ],

        // Editing status
        'locked' => [
            'start_fieldset' => [
                'title' => 'editing status',
            ],
            'title' => 'locked',
            'storage' => 'metadata',
            'type' => 'date',
            'type_config' => [
                // MidCOM DBA converts dates to/from unixtimes to MgdSchema's ISO format
                'storage_type' => 'UNIXTIME',
            ],
            'widget' => 'jsdate',
            'readonly' => true,
            'hidden' => true,
        ],
        'locker' => [
            'title' => 'locker',
            'storage' => 'metadata',
            'type' => 'select',
            'type_config' => [
                'require_corresponding_option' => false,
                'options' => [],
                'allow_other' => true,
            ],
            'widget' => 'autocomplete',
            'widget_config' => [
                'class' => 'midcom_db_person',
                'titlefield' => 'name',
                'id_field' => 'guid',
                'searchfields' => [
                    'firstname',
                    'lastname',
                ],
                'orders' => [['lastname' => 'ASC']],
            ],
            'readonly' => true,
            'hidden' => true,
            'end_fieldset' => '',
        ],
    ],
],
