/* Attention to all developers:
 * Changes to this schema must be documented in midcom_helper_metadata's
 * class introduction
 */
'metadata' => array
(
    'description' => 'metadata',
    'l10n_db'     => 'midcom.admin.folder',
    'fields' => array
    (
        'opengraph_type' => array
        (
            'start_fieldset' => array
            (
                'title' => 'opengraph information',
            ),
            'end_fieldset' => '',
            'title' => 'opengraph type',
            'type' => 'select',
            'type_config' => array
            (
                'options' => midcom::get()->metadata->get_opengraph_types(),
            ),
            'widget' => 'select',
            'storage' => array
            (
                'location' => 'configuration',
                'domain'   => 'midcom.helper.metadata',
                'name'     => 'opengraph_type',
            ),
            'default' => midcom::get()->metadata->get_opengraph_type_default(),
            'hidden' => !midcom::get()->config->get('metadata_opengraph'),
        ),
        'navnoentry' => array
        (
            'start_fieldset' => array
            (
                'title' => 'visibility settings',
            ),
            'title' => 'hide from navigation',
            'helptext' => 'objects with no navigation entry are still accessible on-site if the url is known',
            'type' => 'boolean',
            'storage' => 'metadata',
            'widget' => 'checkbox',
        ),
        'do_not_index' => array
        (
            'title' => "do not index (midcom's indexer)",
            'helptext' => "hides object from midcom indexer'",
            'type' => 'boolean',
            'storage' => array
            (
                'location' => 'parameter',
                'domain' => 'midcom.services.indexer',
                'name' => "do_not_index",
            ),
            'widget' => 'checkbox',
            'hidden' => !midcom::get()->config->get('indexer_backend'),
        ),
        'schedulestart' => array
        (
            'hidden' => !midcom::get()->config->get('metadata_scheduling'),
            'title' => 'schedule start',
            'type' => 'date',
            'type_config' => array
            (
                // MidCOM DBA converts dates to/from unixtimes to MgdSchema's ISO format
                'storage_type' => 'UNIXTIME',
            ),
            'storage' => 'metadata',
            'widget' => 'jsdate',
        ),
        'scheduleend' => array
        (
            'hidden' => !midcom::get()->config->get('metadata_scheduling'),
            'title' => 'schedule end',
            'type' => 'date',
            'type_config' => array
            (
                // MidCOM DBA converts dates to/from unixtimes to MgdSchema's ISO format
                'storage_type' => 'UNIXTIME',
            ),
            'storage' => 'metadata',
            'widget' => 'jsdate',
        ),
        'score' => array
        (
            'title' => 'arrangement score',
            'storage' => 'metadata',
            'type' => 'number',
            'widget' => 'text',
            'write_privilege' => array
            (
                'privilege' => 'midcom.admin.folder:topic_management',
            ),
        ),
        'hidden' => array
        (
            'title' => 'prevent entering',
            'type' => 'boolean',
            'storage' => 'metadata',
            'widget' => 'checkbox',
            'end_fieldset' => '',
        ),
        'description' => array
        (
            'title' => 'description',
            'storage' => array
            (
                'location' => 'configuration',
                'domain'   => 'midcom.helper.metadata',
                'name'     => 'description',
            ),
            'type' => 'text',
            'widget' => 'textarea',
            'start_fieldset' => array
            (
                'title' => 'metadata',
            ),
        ),
        'keywords' => array
        (
            'title' => 'keywords',
            'storage' => array
            (
                'location' => 'configuration',
                'domain'   => 'midcom.helper.metadata',
                'name'     => 'keywords',
            ),
            'type' => 'text',
            'widget' => 'text',
        ),
        'tags' => Array
        (
            'title' => 'tags',
            'type' => 'tags',
            'widget' => 'text',
        ),
        'published' => array
        (
            'title' => 'published',
            'storage' => 'metadata',
            'type' => 'date',
            'type_config' => array
            (
                // MidCOM DBA converts dates to/from unixtimes to MgdSchema's ISO format
                'storage_type' => 'UNIXTIME',
            ),
            'widget' => 'jsdate',
        ),
        'authors' => array
        (
            'title' => 'authors',
            'storage' => 'metadata',
            'type' => 'select',
            'type_config' => array
            (
                'require_corresponding_option' => false,
                'options' => array(),
                'allow_other' => true,
                'allow_multiple' => true,
                'multiple_storagemode' => 'imploded_wrapped',
            ),
            'widget' => 'autocomplete',
            'widget_config' => array
            (
                'class' => 'midcom_db_person',
                'component' => 'midgard.admin.asgard',
                'titlefield' => 'name',
                'id_field' => 'guid',
                'searchfields' => array
                (
                    'firstname',
                    'lastname',
                    'username',
                ),
                'result_headers' => array
                (
                    array('name' => 'firstname', 'title' => 'firstname'),
                    array('name' => 'lastname', 'title' => 'lastname'),
                    array('name' => 'email', 'title' => 'email'),
                ),
                'orders' => array(),
                'creation_mode_enabled' => true,
                'creation_handler' => midcom_connection::get_url('self') . "__mfa/asgard/object/create/chooser/midgard_person/",
                'creation_default_key' => 'lastname',
            ),
        ),
        'owner' => array
        (
            'title' => 'owner',
            'storage' => 'metadata',
            'type' => 'select',
            'type_config' => array
            (
                'require_corresponding_option' => false,
                'options' => array(),
                'allow_other' => true,
                'allow_multiple' => false,
            ),
            'widget' => 'autocomplete',
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
                    array('name' => 'official', 'title' => 'title'),
                ),
                'categorize_by_parent_label' => true,
                'orders' => array(),
                'creation_mode_enabled' => true,
                'creation_handler' => midcom_connection::get_url('self') . "__mfa/asgard/object/create/chooser/midgard_group/",
                'creation_default_key' => 'official',
            ),
            'end_fieldset' => '',
        ),
        'created' => array
        (
            'title' => 'created',
            'storage' => 'metadata',
            'type' => 'date',
            'type_config' => array
            (
                // MidCOM DBA converts dates to/from unixtimes to MgdSchema's ISO format
                'storage_type' => 'UNIXTIME',
            ),
            'start_fieldset' => array
            (
                'title' => 'internal metadata',
            ),
            'widget' => 'jsdate',
            'widget_config' => array
            (
                'hide_seconds' => false
            ),
            'readonly' => true,
        ),
        'creator' => array
        (
            'title' => 'creator',
            'storage' => 'metadata',
            'type' => 'select',
            'type_config' => array
            (
                'require_corresponding_option' => false,
                'options' => array (),
                'allow_other' => true,
            ),
            'widget' => 'autocomplete',
            'widget_config' => array
            (
                'class' => 'midcom_db_person',
                'component' => 'midgard.admin.asgard',
                'titlefield' => 'name',
                'id_field' => 'guid',
                'searchfields' => array
                (
                    'firstname',
                    'lastname',
                ),
                'result_headers' => array
                (
                    array('name' => 'firstname', 'title' => 'firstname'),
                    array('name' => 'lastname', 'title' => 'lastname'),
                    array('name' => 'email', 'title' => 'email'),
                ),
                'orders' => array(array('lastname', 'ASC')),
            ),
            'readonly' => true,
        ),
        'revised' => array
        (
            'title' => 'revised',
            'storage' => 'metadata',
            'type' => 'date',
            'type_config' => array
            (
                // MidCOM DBA converts dates to/from unixtimes to MgdSchema's ISO format
                'storage_type' => 'UNIXTIME',
            ),
            'widget' => 'jsdate',
            'widget_config' => array
            (
                'hide_seconds' => false
            ),
            'readonly' => true,
        ),
        'revisor' => array
        (
            'title' => 'revisor',
            'storage' => 'metadata',
            'type' => 'select',
            'type_config' => array
            (
                'require_corresponding_option' => false,
                'options' => array (),
                'allow_other' => true,
            ),
            'widget' => 'autocomplete',
            'widget_config' => array
            (
                'class' => 'midcom_db_person',
                'component' => 'midgard.admin.asgard',
                'titlefield' => 'name',
                'id_field' => 'guid',
                'searchfields' => array
                (
                    'firstname',
                    'lastname',
                ),
                'result_headers' => array
                (
                    array('name' => 'firstname', 'title' => 'firstname'),
                    array('name' => 'lastname', 'title' => 'lastname'),
                    array('name' => 'email', 'title' => 'email'),
                ),
                'orders' => array(array('lastname', 'ASC')),
            ),
            'readonly' => true,
        ),
        'revision' => array
        (
            'title' => 'revision',
            'storage' => 'metadata',
            'type' => 'number',
            'widget' => 'text',
            'readonly' => true,
        ),
        'size' => array
        (
            'title' => 'size',
            'storage' => 'metadata',
            'type' => 'number',
            'widget' => 'text',
            'readonly' => true,
            'end_fieldset' => '',
        ),

        // Approvals
        'approved' => array
        (
            'start_fieldset' => array
            (
                'title' => 'approvals',
            ),
            'title' => 'approved',
            'storage' => 'metadata',
            'type' => 'date',
            'type_config' => array
            (
                // MidCOM DBA converts dates to/from unixtimes to MgdSchema's ISO format
                'storage_type' => 'UNIXTIME',
            ),
            'widget' => 'jsdate',
            'readonly' => true,
            'hidden' => !midcom::get()->config->get('metadata_approval'),
        ),
        'approver' => array
        (
            'hidden' => !midcom::get()->config->get('metadata_approval'),
            'title' => 'approver',
            'storage' => 'metadata',
            'type' => 'select',
            'type_config' => array
            (
                'require_corresponding_option' => false,
                'options' => array (),
                'allow_other' => true,
            ),
            'widget' => 'autocomplete',
            'widget_config' => array
            (
                'class' => 'midcom_db_person',
                'component' => 'midgard.admin.asgard',
                'titlefield' => 'name',
                'id_field' => 'guid',
                'searchfields' => array
                (
                    'firstname',
                    'lastname',
                ),
                'result_headers' => array
                (
                    array('name' => 'firstname', 'title' => 'firstname'),
                    array('name' => 'lastname', 'title' => 'lastname'),
                    array('name' => 'email', 'title' => 'email'),
                ),
                'orders' => array(array('lastname', 'ASC')),
            ),
            'readonly' => true,
            'end_fieldset' => '',
        ),

        // Editing status
        'locked' => array
        (
            'start_fieldset' => array
            (
                'title' => 'editing status',
            ),
            'title' => 'locked',
            'storage' => 'metadata',
            'type' => 'date',
            'type_config' => array
            (
                // MidCOM DBA converts dates to/from unixtimes to MgdSchema's ISO format
                'storage_type' => 'UNIXTIME',
            ),
            'widget' => 'jsdate',
            'readonly' => true,
            'hidden' => true,
        ),
        'locker' => array
        (
            'title' => 'locker',
            'storage' => 'metadata',
            'type' => 'select',
            'type_config' => array
            (
                'require_corresponding_option' => false,
                'options' => array (),
                'allow_other' => true,
            ),
            'widget' => 'autocomplete',
            'widget_config' => array
            (
                'class' => 'midcom_db_person',
                'component' => 'midgard.admin.asgard',
                'titlefield' => 'name',
                'id_field' => 'guid',
                'searchfields' => array
                (
                    'firstname',
                    'lastname',
                ),
                'orders' => array(array('lastname', 'ASC')),
            ),
            'readonly' => true,
            'hidden' => true,
            'end_fieldset' => '',
        ),
    ),
),
