'default' => array
(
    'description' => 'project',
    'fields'      => array
    (
        'title' => array
        (
            'title'    => 'title',
            'type'     => 'text',
            'widget'   => 'text',
            'storage'  => 'title',
            'required' => true,
            'start_fieldset' => array (
                'title' => 'basic information',
                'css_group' => 'area meta',
            ),
        ),
        'description' => Array
        (
            'title' => 'description',
            'storage' => 'description',
            'type' => 'text',
            'type_config' => array
            (
                'output_mode' => 'markdown'
            ),
            'widget' => 'textarea',
        ),
        'start' => Array
        (
            'title' => 'start time',
            'storage' => 'start',
            'type' => 'date',
            'type_config' => Array
            (
                'storage_type' => 'UNIXTIME'
            ),
            'widget' => 'jsdate',
            'widget_config' => Array
            (
                'show_time' => false,
            ),
        ),
        'end' => Array
        (
            'title' => 'deadline',
            'storage' => 'end',
            'type' => 'date',
            'type_config' => Array
            (
                'storage_type' => 'UNIXTIME'
            ),
            'widget' => 'jsdate',
            'widget_config' => Array
            (
                'show_time' => false,
            ),
        ),
        'customer' => array
        (
            'title'   => 'customer',
            'storage' => 'customer',
            'type' => 'select',
            'type_config' => array
            (
                 'require_corresponding_option' => false,
                 'options' => array(),
            ),
            'widget' => 'chooser',
            'widget_config' => array
            (
                'class'       => 'org_openpsa_contacts_group_dba',
                'component'   => 'org.openpsa.contacts',
                'titlefield'  => 'official',
                'id_field'     => 'id',
                'constraints' => array(),
                'searchfields'  => array
                (
                    'name',
                    'official',
                ),
                'result_headers' => array
                (
                    array
                    (
                        'title' => 'name',
                        'name' => 'official',
                    ),
                ),
                'orders'        => array
                (
                    array('official'    => 'ASC'),
                    array('name'    => 'ASC'),
                ),
            ),
        ),
        'contacts' => array
        (
            'title' => 'contacts',
            'storage' => null,
            'type' => 'mnrelation',
            'type_config' => array
            (
                'mapping_class_name' => 'org_openpsa_contacts_role_dba',
                'master_fieldname' => 'objectGuid',
                'member_fieldname' => 'person',
                'allow_multiple' => true,
                'additional_fields' => array('role' => ORG_OPENPSA_OBTYPE_PROJECTCONTACT)
            ),
            'widget' => 'chooser',
            'widget_config' => array
            (
                'class' => 'org_openpsa_contacts_person_dba',
                'component' => 'org.openpsa.contacts',
                'titlefield' => 'name',

                'result_headers' => array
                (
                    array
                    (
                        'title' => 'name',
                        'name' => 'name',
                    ),
                    array
                    (
                        'title' => 'email',
                        'name' => 'email',
                    ),
                ),

                'searchfields' => array
                (
                    'firstname',
                    'lastname',
                    'username',
                ),
                'orders' => array
                (
                    array('lastname' => 'ASC'),
                    array('firstname' => 'ASC'),
                ),
                'id_field' => 'id',
                'creation_mode_enabled' => true,
                'creation_handler' => midcom_connection::get_url('self') . "__mfa/org.openpsa.helpers/chooser/create/org_openpsa_contacts_person_dba/",
                'creation_default_key' => 'openpsa',
            ),
        ),
        'manager' => array
        (
            'title'   => 'manager',
            'storage' => 'manager',
            //'required' => true,
            'type' => 'select',
            'type_config' => array
            (
                 'require_corresponding_option' => false,
                 'options' => array(),
            ),
            'widget' => 'chooser',
            'widget_config' => array
            (
                'class'       => 'org_openpsa_contacts_person_dba',
                'component'   => 'org.openpsa.contacts',
                'titlefield'  => 'name',
                'id_field'     => 'id',
                'constraints' => array
                (
                    array
                    (
                        'field' => 'username',
                        'op'    => '<>',
                        'value' => '',
                    ),
                ),
                'searchfields'  => array
                (
                    'firstname',
                    'lastname',
                    'username',
                ),
                'result_headers' => array
                (
                    array
                    (
                        'title' => 'name',
                        'name' => 'name',
                    ),
                    array
                    (
                        'title' => 'email',
                        'name' => 'email',
                    ),
                ),
                'orders'        => array
                (
                    array('lastname'    => 'ASC'),
                    array('firstname'    => 'ASC'),

                ),
            ),
        ),
        'resources' => array
        (
            'title' => 'resources',
            'storage' => null,
            'type' => 'mnrelation',
            'type_config' => array
            (
                'mapping_class_name' => 'org_openpsa_contacts_role_dba',
                'master_fieldname' => 'objectGuid',
                'member_fieldname' => 'person',
                'allow_multiple' => true,
                'additional_fields' => array('role' => ORG_OPENPSA_OBTYPE_PROJECTRESOURCE)
            ),
            'widget' => 'chooser',
            'widget_config' => array
            (
                'class' => 'org_openpsa_contacts_person_dba',
                'component' => 'org.openpsa.contacts',
                'titlefield' => 'name',

                'result_headers' => array
                (
                    array
                    (
                        'title' => 'name',
                        'name' => 'name',
                    ),
                    array
                    (
                        'title' => 'email',
                        'name' => 'email',
                    ),
                ),

                'searchfields' => array
                (
                    'firstname',
                    'lastname',
                    'username',
                ),
                'orders' => array
                (
                    array('lastname' => 'ASC'),
                    array('firstname' => 'ASC'),
                ),
                'id_field' => 'id',
                'creation_mode_enabled' => true,
                'creation_handler' => midcom_connection::get_url('self') . "__mfa/org.openpsa.helpers/chooser/create/org_openpsa_contacts_person_dba/",
                'creation_default_key' => 'openpsa',
            ),
        ),
        'tags' => Array
        (
            'title' => 'skills required',
            'storage' => null,
            'type' => 'tags',
            'widget' => 'text',
            'end_fieldset' => '',
        ),

        'orgOpenpsaWgtype' => array
        (
            'title' => 'create workgroups for this project',
            'storage' => 'orgOpenpsaWgtype',
            'type' => 'boolean',
            'widget' => 'checkbox',
            'start_fieldset' => array
            (
                'title' => 'access control',
                'css_group' => 'area acl',
            ),
        ),
        'orgOpenpsaAccesstype' => array
        (
            'title' => 'access type',
            'storage' => 'orgOpenpsaAccesstype',
            'type' => 'select',
            'type_config' => array
            (
                 'options' => $GLOBALS['org_openpsa_core_acl_options'],
            ),
            'widget' => 'select',
        ),
        'orgOpenpsaOwnerWg' => array
        (
            'title' => 'workgroup',
            'storage' => 'orgOpenpsaOwnerWg',
            'type' => 'select',
            'type_config' => array
            (
                'options' => org_openpsa_helpers_list::workgroups(),
            ),
            'widget' => 'select',
            'end_fieldset' => '',
        ),
    )
),