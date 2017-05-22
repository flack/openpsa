'default' => array
(
    'description' => 'salesproject',
    'fields'      => array
    (
        'code' => array
        (
            // COMPONENT-REQUIRED
            'title' => 'code',
            'storage' => 'code',
            'required' => true,
            'type' => 'text',
            'widget'  => 'text',
        ),
        'title' => array
        (
            // COMPONENT-REQUIRED
            'title' => 'title',
            'storage' => 'title',
            'required' => true,
            'type' => 'text',
            'widget'  => 'text',
        ),
        'description' => array
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
        'state' => array
        (
            'title' => 'state',
            'storage' => 'state',
            'type' => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    org_openpsa_sales_salesproject_dba::STATE_ACTIVE    => 'active',
                    org_openpsa_sales_salesproject_dba::STATE_CANCELED  => 'canceled',
                    org_openpsa_sales_salesproject_dba::STATE_LOST      => 'lost',
                    org_openpsa_sales_salesproject_dba::STATE_WON       => 'won',
                    org_openpsa_sales_salesproject_dba::STATE_DELIVERED => 'delivered',
                    org_openpsa_sales_salesproject_dba::STATE_INVOICED  => 'invoiced',
                ),
            ),
            'widget' => 'select',
        ),
        'close_est' => array
        (
            'title' => 'estimated closing date',
            'storage' => 'closeEst',
            'type' => 'date',
            'type_config' => array
            (
                'storage_type' => 'UNIXTIME'
            ),
            'widget' => 'jsdate',
            'widget_config' => array
            (
                'show_time' => false,
            ),
        ),
        'probability' => array
        (
            'title' => 'probability',
            'storage' => 'probability',
            'type' => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    25 => '25%',
                    50 => '50%',
                    75 => '75%',
                    100 => '100%',
                ),
            ),
            'widget' => 'select',
        ),
        'value' => array
        (
            'title' => 'value',
            'storage' => 'value',
            'type' => 'number',
            'widget'  => 'text',
        ),
        'profit' => array
        (
            'title' => 'profit',
            'storage' => 'profit',
            'type' => 'number',
            'widget'  => 'text',
         ),
        'owner' => array
        (
            'title'   => 'owner',
            'storage' => 'owner',
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
        'customerContact' => array
        (
            'title'   => 'customer contact',
            'storage' => 'customerContact',
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
                'creation_mode_enabled' => true,
                'creation_handler' => midcom_connection::get_url('self') . "__mfa/org.openpsa.helpers/chooser/create/org_openpsa_contacts_person_dba/",
                'creation_default_key' => 'lastname',
            ),
            'required' => true
        ),
        'customer' => array
        (
            'title' => 'customer',
            'storage' => 'customer',
            'type' => 'select',
            'type_config' => array
            (
                'options' => array(),
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
                'mapping_class_name' => 'org_openpsa_contacts_role_dba',
                'master_fieldname' => 'objectGuid',
                'member_fieldname' => 'person',
                'additional_fields' => array('role' => org_openpsa_sales_salesproject_dba::ROLE_MEMBER),
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
    )
),