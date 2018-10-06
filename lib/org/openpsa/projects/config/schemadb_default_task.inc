'default' => [
    'description' => 'task',
    'fields'      => [
        'project' => [
            'title'   => 'project',
            'storage' => 'project',
            'required' => true,
            'type' => 'select',
            'type_config' => [
                 'require_corresponding_option' => false,
            ],
            'widget' => 'autocomplete',
            'widget_config' => [
                'class'       => 'org_openpsa_projects_project',
                'titlefield'  => 'title',
                'id_field'     => 'id',
                'searchfields'  => [
                    'title'
                ],
                'orders'        => [
                    ['title'    => 'ASC']
                ],
            ],
        ],
        'up' => [
            'title'   => 'task',
            'storage' => 'up',
            'type' => 'select',
            'type_config' => [
                 'require_corresponding_option' => false,
            ],
            'widget' => 'autocomplete',
            'widget_config' => [
                'clever_class' => 'task',
            ],
        ],
        'title' => [
            'title'    => 'title',
            'type'     => 'text',
            'widget'   => 'text',
            'storage'  => 'title',
            'required' => true,
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
                'storage_type' => 'UNIXTIME',
                'later_than' => 'start'
            ],
            'widget' => 'jsdate',
            'widget_config' => [
                'show_time' => false,
            ],
        ],
        'priority' => [
            'title' => 'Priority',
            'storage' => 'priority',
            'type' => 'select',
            'type_config' => [
                'options' => [
                    '1' => 'very high',
                    '2' => 'high',
                    '3' => 'average',
                    '4' => 'low',
                    '5' => 'very low',
                ],
            ],
            'default' => '3',
            'widget' => 'select',
        ],
        'status' => [
            'title' => 'Status',
            'storage' => 'status',
            'type' => 'select',
            'type_config' => [
                'options' => [
                    org_openpsa_projects_task_status_dba::PROPOSED => 'not_started',
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
        'contacts' => [
            'title' => 'contacts',
            'storage' => null,
            'type' => 'mnrelation',
            'type_config' => [
                'mapping_class_name' => 'org_openpsa_projects_task_resource_dba',
                'master_fieldname' => 'task',
                'member_fieldname' => 'person',
                'master_is_id' => true,
                'additional_fields' => ['orgOpenpsaObtype' => org_openpsa_projects_task_resource_dba::CONTACT]
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
                'class'       => 'org_openpsa_contacts_person_dba',
                'component'   => 'org.openpsa.contacts',
                'titlefield'  => 'name',
                'id_field'     => 'id',
                'constraints' => [
                	[
                        'field' => 'username',
                        'op'    => '<>',
                        'value' => '',
                    ],
                ],
                'searchfields'  => [
                    'firstname',
                    'lastname',
                    'username',
                ],
                'result_headers' => [
                	[
                        'title' => 'email',
                        'name' => 'email',
                    ],
                ],
                'orders'        => [
                    ['lastname'    => 'ASC'],
                    ['firstname'    => 'ASC'],

                ],
            ],
        ],
        'resources' => [
            'title' => 'resources',
            'storage' => null,
            'type' => 'mnrelation',
            'type_config' => [
                'mapping_class_name' => 'org_openpsa_projects_task_resource_dba',
                'master_fieldname' => 'task',
                'member_fieldname' => 'person',
                'master_is_id' => true,
                'additional_fields' => ['orgOpenpsaObtype' => org_openpsa_projects_task_resource_dba::RESOURCE]
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
        ],
        'agreement' => [
            'title'   => 'agreement',
            'storage' => 'agreement',
            'type' => 'select',
            'type_config' => [
                 'require_corresponding_option' => false,
                 'options' => [],
            ],
            'widget' => 'autocomplete',
            'widget_config' => [
                'class'       => 'org_openpsa_sales_salesproject_deliverable_dba',
                'component'   => 'org.openpsa.sales',
                'titlefield'  => 'title',
                'categorize_by_parent_label' => 'salesproject',
                'id_field'     => 'id',
                'searchfields'  => [
                    'title',
                    'salesproject.title'
                ],
                'orders'        => [
                    ['title'    => 'ASC'],
                ],
            ],
        ],
        'plannedHours' => [
            'title'    => 'planned hours',
            'type'     => 'number',
            'widget'   => 'text',
            'storage'  => 'plannedHours',
        ],
        'invoiceable_default' => [
            'title' => 'hours are invoiceable by default',
            'type' => 'boolean',
            'storage' => 'hoursInvoiceableDefault',
            'widget' => 'checkbox',
            'end_fieldset' => '',
        ],
        'minimum_slot' => [
            'title' => 'minimum time slot needed for task bookings',
            'storage' => [
                'location' => 'configuration',
                'domain'   => 'org.openpsa.projects.projectbroker',
                'name'     => 'minimum_slot',
            ],
            'type' => 'number',
            'widget'  => 'text',
        ],
    ]
],