'default' => array
(
    'description' => 'task',
    'fields'      => array
    (
        'title' => array
        (
            'title'    => 'title',
            'type'     => 'text',
            'widget'   => 'text',
            'storage'  => 'title',
            'required' => true,
        ),
        'priority' => array
        (
            'title' => 'Priority',
            'storage' => 'priority',
            'type' => 'select',
            'type_config' => array
            (
                'options' => Array
                (
                    '1' => 'very high',
                    '2' => 'high',
                    '3' => 'average',
                    '4' => 'low',
                    '5' => 'very low',
                ),
            ),
            'default' => '3',
            'widget' => 'select',
        ),
        'status' => array
        (
            'title' => 'Status',
            'storage' => 'status',
            'type' => 'select',
            'type_config' => array
            (
                'options' => Array
                (
                    org_openpsa_projects_task_status_dba::PROPOSED => 'not_started',
                    org_openpsa_projects_task_status_dba::STARTED => 'ongoing',
                    org_openpsa_projects_task_status_dba::ONHOLD => 'on_hold',
                    org_openpsa_projects_task_status_dba::REJECTED => 'rejected',
                    org_openpsa_projects_task_status_dba::COMPLETED => 'closed',
                ),
            ),
            'widget' => 'select',
        ),
        'tags' => Array
        (
            'title' => 'skills required',
            'storage' => null,
            'type' => 'tags',
            'widget' => 'text',
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
        'plannedHours' => array
        (
            'title'    => 'planned hours',
            'type'     => 'number',
            'widget'   => 'text',
            'storage'  => 'plannedHours',
        ),
        'minimum_slot' => Array
        (
            'title' => 'minimum time slot needed for task bookings',
            'storage' => Array(
                'location' => 'configuration',
                'domain'   => 'org.openpsa.projects.projectbroker',
                'name'     => 'minimum_slot',
            ),
            'type' => 'number',
            'widget'  => 'text',
        ),
        'agreement' => array
        (
            'title'   => 'agreement',
            'storage' => 'agreement',
            'type' => 'select',
            'type_config' => array
            (
                 'require_corresponding_option' => false,
                 'options' => array(),
            ),
            'widget' => 'chooser',
            'widget_config' => array
            (
                'class'       => 'org_openpsa_sales_salesproject_deliverable_dba',
                'component'   => 'org.openpsa.sales',
                'titlefield'  => 'deliverable_html',
                'id_field'     => 'id',
                'constraints' => array(),
                'searchfields'  => array
                (
                    'title',
                    'salesproject.title'
                ),
                'result_headers' => array
                (
                    array
                    (
                        'title' => 'title',
                        'name' => 'title',
                    ),
                ),
                'orders'        => array
                (
                    array('title'    => 'ASC'),
                ),
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
                'mapping_class_name' => 'org_openpsa_projects_task_resource_dba',
                'master_fieldname' => 'task',
                'member_fieldname' => 'person',
                'master_is_id' => true,
                'allow_multiple' => true,
                'additional_fields' => array('orgOpenpsaObtype' => ORG_OPENPSA_OBTYPE_PROJECTRESOURCE)
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

        'invoiceable_default' => array
        (
            'title' => 'hours are invoiceable by default',
            'type' => 'boolean',
            'storage' => 'hoursInvoiceableDefault',
            'widget' => 'checkbox',
            'end_fieldset' => '',
        ),

        'contacts' => array
        (
            'title' => 'contacts',
            'storage' => null,
            'type' => 'mnrelation',
            'type_config' => array
            (
                'mapping_class_name' => 'org_openpsa_projects_task_resource_dba',
                'master_fieldname' => 'task',
                'member_fieldname' => 'person',
                'master_is_id' => true,
                'allow_multiple' => true,
                'additional_fields' => array('orgOpenpsaObtype' => ORG_OPENPSA_OBTYPE_PROJECTCONTACT)
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
    )
),