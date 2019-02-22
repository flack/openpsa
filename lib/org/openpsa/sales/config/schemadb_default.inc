'default' => [
    'description' => 'salesproject',
    'fields'      => [
        'code' => [
            // COMPONENT-REQUIRED
            'title' => 'code',
            'storage' => 'code',
            'required' => true,
            'type' => 'text',
            'widget'  => 'text',
        ],
        'title' => [
            // COMPONENT-REQUIRED
            'title' => 'title',
            'storage' => 'title',
            'required' => true,
            'type' => 'text',
            'widget'  => 'text',
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
        'state' => [
            'title' => 'state',
            'storage' => 'state',
            'type' => 'select',
            'type_config' => [
                'options' => [
                    org_openpsa_sales_salesproject_dba::STATE_ACTIVE    => 'active',
                    org_openpsa_sales_salesproject_dba::STATE_LOST      => 'lost',
                    org_openpsa_sales_salesproject_dba::STATE_WON       => 'won',
                    org_openpsa_sales_salesproject_dba::STATE_DELIVERED => 'delivered',
                    org_openpsa_sales_salesproject_dba::STATE_INVOICED  => 'invoiced',
                ],
            ],
            'widget' => 'select',
        ],
        'close_est' => [
            'title' => 'estimated closing date',
            'storage' => 'closeEst',
            'type' => 'date',
            'type_config' => [
                'storage_type' => 'UNIXTIME'
            ],
            'widget' => 'jsdate',
            'widget_config' => [
                'show_time' => false,
            ],
        ],
        'probability' => [
            'title' => 'probability',
            'storage' => 'probability',
            'type' => 'select',
            'type_config' => [
                'options' => [
                    25 => '25%',
                    50 => '50%',
                    75 => '75%',
                    100 => '100%',
                ],
            ],
            'widget' => 'select',
        ],
        'value' => [
            'title' => 'value',
            'storage' => 'value',
            'type' => 'number',
            'widget'  => 'text',
        ],
        'profit' => [
            'title' => 'profit',
            'storage' => 'profit',
            'type' => 'number',
            'widget'  => 'text',
         ],
        'owner' => [
            'title'   => 'owner',
            'storage' => 'owner',
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
        'customerContact' => [
            'title'   => 'customer contact',
            'storage' => 'customerContact',
            'type' => 'select',
            'type_config' => [
                 'require_corresponding_option' => false,
                 'options' => [],
            ],
            'widget' => 'autocomplete',
            'widget_config' => [
                'clever_class' => 'contact',
                'id_field'     => 'id',
                'creation_mode_enabled' => true,
                'creation_handler' => midcom_connection::get_url('self') . "__mfa/org.openpsa.helpers/chooser/create/org_openpsa_contacts_person_dba/",
                'creation_default_key' => 'lastname',
            ],
            'required' => true
        ],
        'customer' => [
            'title' => 'customer',
            'storage' => 'customer',
            'type' => 'select',
            'type_config' => [
                'options' => [],
            ],
            'widget' => 'select',
        ],
        'contacts' => [
            'title' => 'contacts',
            'storage' => null,
            'type' => 'mnrelation',
            'type_config' => [
                'mapping_class_name' => 'org_openpsa_contacts_role_dba',
                'master_fieldname' => 'objectGuid',
                'member_fieldname' => 'person',
                'additional_fields' => ['role' => org_openpsa_sales_salesproject_dba::ROLE_MEMBER],
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
    ]
],