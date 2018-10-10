'default' => [
    'description' => 'project',
    'fields'      => [
        'title' => [
            'title'    => 'title',
            'type'     => 'text',
            'widget'   => 'text',
            'storage'  => 'title',
            'required' => true,
            'start_fieldset' => [
                'title' => 'basic information',
                'css_group' => 'area meta',
            ],
        ],
        'description' => [
            'title' => 'description',
            'storage' => 'description',
            'type' => 'text',
            'type_config' => [
                'output_mode' => 'markdown'
            ],
            'widget' => 'markdown',
        ],
        'start' => [
            'title' => 'start time',
            'storage' => 'start',
            'type' => 'date',
            'type_config' => [
                'storage_type' => 'UNIXTIME'
            ],
            'widget' => 'jsdate',
            'widget_config' => [
                'show_time' => false,
            ],
        ],
        'end' => [
            'title' => 'deadline',
            'storage' => 'end',
            'type' => 'date',
            'type_config' => [
                'storage_type' => 'UNIXTIME'
            ],
            'widget' => 'jsdate',
            'widget_config' => [
                'show_time' => false,
            ],
        ],
        'status' => [
            'title' => 'Status',
            'storage' => 'status',
            'type' => 'select',
            'type_config' => [
                'options' => [
                    org_openpsa_projects_task_status_dba::PROPOSED => 'not_started',
                    org_openpsa_projects_task_status_dba::DECLINED => 'declined',
                    org_openpsa_projects_task_status_dba::ACCEPTED => 'accepted',
                    org_openpsa_projects_task_status_dba::STARTED => 'ongoing',
                    org_openpsa_projects_task_status_dba::REOPENED => 'reopened',
                    org_openpsa_projects_task_status_dba::ONHOLD => 'on_hold',
                    org_openpsa_projects_task_status_dba::REJECTED => 'rejected',
                    org_openpsa_projects_task_status_dba::COMPLETED => 'completed',
                    org_openpsa_projects_task_status_dba::APPROVED => 'approved',
                    org_openpsa_projects_task_status_dba::CLOSED => 'closed',
                ],
            ],
            'widget' => 'select',
        ],
        'customer' => [
            'title'   => 'customer',
            'storage' => 'customer',
            'type' => 'select',
            'type_config' => [
                 'require_corresponding_option' => false,
                 'options' => [],
            ],
            'widget' => 'autocomplete',
            'widget_config' => [
                'clever_class' => 'organization',
            ],
        ],
        'contacts' => [
            'title' => 'contacts',
            'storage' => null,
            'type' => 'mnrelation',
            'type_config' => [
                'mapping_class_name' => 'org_openpsa_contacts_role_dba',
                'master_fieldname' => 'objectGuid',
                'member_fieldname' => 'person',
                'additional_fields' => ['role' => org_openpsa_projects_task_resource_dba::CONTACT]
            ],
            'widget' => 'autocomplete',
            'widget_config' => [
                'clever_class' => 'contact',
                'id_field' => 'id',
                'creation_mode_enabled' => true,
                'creation_handler' => midcom_connection::get_url('self') . "__mfa/org.openpsa.helpers/chooser/create/org_openpsa_contacts_person_dba/",
                'creation_default_key' => 'lastname',
            ],
        ],
        'manager' => [
            'title'   => 'manager',
            'storage' => 'manager',
            //'required' => true,
            'type' => 'select',
            'type_config' => [
                 'require_corresponding_option' => false,
                 'options' => [],
            ],
            'widget' => 'autocomplete',
            'widget_config' => [
                'clever_class' => 'contact',
                'id_field'     => 'id',
                'constraints' => [
                    [
                        'field' => 'username',
                        'op'    => '<>',
                        'value' => '',
                    ],
                ],
            ],
        ],
        'resources' => [
            'title' => 'resources',
            'storage' => null,
            'type' => 'mnrelation',
            'type_config' => [
                'mapping_class_name' => 'org_openpsa_contacts_role_dba',
                'master_fieldname' => 'objectGuid',
                'member_fieldname' => 'person',
                'allow_multiple' => true,
                'additional_fields' => ['role' => org_openpsa_projects_task_resource_dba::RESOURCE]
            ],
            'widget' => 'autocomplete',
            'widget_config' => [
                'clever_class' => 'contact',
                'id_field' => 'id',
                'creation_mode_enabled' => true,
                'creation_handler' => midcom_connection::get_url('self') . "__mfa/org.openpsa.helpers/chooser/create/org_openpsa_contacts_person_dba/",
                'creation_default_key' => 'lastname',
            ],
        ],
        'tags' => [
            'title' => 'skills required',
            'storage' => null,
            'type' => 'tags',
            'widget' => 'text',
            'end_fieldset' => '',
        ],

        'orgOpenpsaAccesstype' => [
            'title' => 'access type',
            'storage' => 'orgOpenpsaAccesstype',
            'type' => 'select',
            'type_config' => [
                 'options' => org_openpsa_core_acl::get_options(),
            ],
            'widget' => 'select',
            'start_fieldset' => [
                'title' => 'access control',
                'css_group' => 'area acl',
            ],
        ],
        'orgOpenpsaOwnerWg' => [
            'title' => 'workgroup',
            'storage' => 'orgOpenpsaOwnerWg',
            'type' => 'select',
            'type_config' => [
                'options' => org_openpsa_helpers_list::workgroups(),
            ],
            'widget' => 'select',
            'end_fieldset' => '',
        ],
    ]
],