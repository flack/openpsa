'default' => array
(
    'description' => 'task',
    'fields'      => array
    (
        'project' => array
        (
            'title'   => 'project',
            'storage' => 'project',
            'required' => true,
            'type' => 'select',
            'type_config' => array
            (
                 'require_corresponding_option' => false,
            ),
            'widget' => 'autocomplete',
            'widget_config' => array
            (
                'class'       => 'org_openpsa_projects_project',
                'titlefield'  => 'title',
                'id_field'     => 'id',
                'result_headers' => array
                (
                    array
                    (
                        'title' => 'title',
                        'name' => 'title',
                    ),
                ),
                'searchfields'  => array
                (
                    'title'
                ),
                'orders'        => array
                (
                    array('title'    => 'ASC')
                ),
            ),
        ),
        'title' => array
        (
            'title'    => 'title',
            'type'     => 'text',
            'widget'   => 'text',
            'storage'  => 'title',
            'required' => true,
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
            'widget' => 'markdown',
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
                'storage_type' => 'UNIXTIME',
                'later_than' => 'start'
            ),
            'widget' => 'jsdate',
            'widget_config' => Array
            (
                'show_time' => false,
            ),
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
                    org_openpsa_projects_task_status_dba::REOPENED => 'reopened',
                    org_openpsa_projects_task_status_dba::ONHOLD => 'on_hold',
                    org_openpsa_projects_task_status_dba::REJECTED => 'rejected',
                    org_openpsa_projects_task_status_dba::COMPLETED => 'completed',
                    org_openpsa_projects_task_status_dba::APPROVED => 'approved',
                    org_openpsa_projects_task_status_dba::CLOSED => 'closed',
                ),
            ),
            'widget' => 'select',
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
                'additional_fields' => array('orgOpenpsaObtype' => org_openpsa_projects_task_resource_dba::CONTACT)
            ),
            'widget' => 'autocomplete',
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
                'creation_default_key' => 'lastname',
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
            'widget' => 'autocomplete',
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
                'additional_fields' => array('orgOpenpsaObtype' => org_openpsa_projects_task_resource_dba::RESOURCE)
            ),
            'widget' => 'autocomplete',
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
                'creation_default_key' => 'lastname',
            ),
        ),
        'tags' => Array
        (
            'title' => 'skills required',
            'storage' => null,
            'type' => 'tags',
            'widget' => 'text',
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
            'widget' => 'autocomplete',
            'widget_config' => array
            (
                'class'       => 'org_openpsa_sales_salesproject_deliverable_dba',
                'component'   => 'org.openpsa.sales',
                'titlefield'  => 'deliverable_html',
                'id_field'     => 'id',
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
        'plannedHours' => array
        (
            'title'    => 'planned hours',
            'type'     => 'number',
            'widget'   => 'text',
            'storage'  => 'plannedHours',
        ),
        'invoiceable_default' => array
        (
            'title' => 'hours are invoiceable by default',
            'type' => 'boolean',
            'storage' => 'hoursInvoiceableDefault',
            'widget' => 'checkbox',
            'end_fieldset' => '',
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
    )
),