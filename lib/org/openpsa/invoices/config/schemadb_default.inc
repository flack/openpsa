'default' => array
(
    'name' => 'default',
    'description' => 'invoice',
    'fields'      => array
    (
        'date' => array
        (
            'title' => 'invoice date',
            'storage'    => 'date',
            'type'    => 'date',
            'type_config' => array
            (
                'storage_type' => 'UNIXTIME',
            ),
            'widget'    => 'jsdate',
            'widget_config' => array
            (
                'show_time' => false
            ),
            'start_fieldset' => array
            (
                'title' => 'basic information',
                'css_group' => 'area meta',
            ),
        ),
        'number' => array
        (
            'title' => 'invoice number',
            'storage'  => 'number',
            'type'  => 'number',
            'widget'  => 'text',
            'required' => true,
            'index_method' => 'title',
        ),
        'owner' => array
        (
            'title' => 'owner',
            'storage'  => 'owner',
            'type'  => 'select',
            'type_config' => array
            (
                 'require_corresponding_option' => false,
                 'options' => array(),
            ),
            'widget'    => 'chooser',
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
            'title' => 'customer contact',
            'storage'  => 'customerContact',
            'type'  => 'select',
            'type_config' => array
            (
                 'require_corresponding_option' => false,
                 'options' => array(),
            ),
            'widget'    => 'chooser',
            'widget_config' => array
            (
                'class'       => 'org_openpsa_contacts_person_dba',
                'component'   => 'org.openpsa.contacts',
                'titlefield'  => 'name',
                'id_field'     => 'id',
                'constraints' => array(),
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
                'creation_default_key' => 'openpsa',
             ),
        ),
        'customer' => array
        (
            'title' => 'customer',
            'storage'    => 'customer',
            'type'    => 'select',
            'type_config' => array
            (
                 'require_corresponding_option' => true,
                 'options' => array(),
            ),
            'widget' => 'select',
        ),
        'description' => array
        (
            'title' => 'description',
            'storage'  => 'description',
            'type'  => 'text',
            'widget' => 'textarea',
            'end_fieldset' => '',
        ),
        'sum' => array
        (
            'title' => 'sum',
            'storage'  => 'sum',
            'type'  => 'number',
            'widget'    => 'text',
            'default'   => 0,
            'start_fieldset' => array
            (
                'title' => 'invoicing information',
                'css_group' => 'area meta',
            ),
        ),
        'vat' => array
        (
            'title' => 'vat',
            'storage'  => 'vat',
            'type'  => 'select',
            'type_config' => array
            (
                 'require_corresponding_option' => true,
                 'options' => array(),
            ),
            'widget'    => 'select',
        ),
        'due' => array
        (
            'title' => 'due',
            'storage'    => 'due',
            'type'    => 'date',
            'type_config' => array
            (
                'storage_type' => 'UNIXTIME',
            ),
            'widget'    => 'jsdate',
            'widget_config' => array
            (
                'show_time' => false
            ),
            'hidden' => true
        ),
        'sent' => array
        (
            'title' => 'sent',
            'storage'    => 'sent',
            'type'    => 'date',
            'type_config' => array
            (
                'storage_type' => 'UNIXTIME',
            ),
            'widget'    => 'jsdate',
            'widget_config' => array
            (
                'show_time' => false
            ),
            'readonly'    => true,
            'hidden'    => true,
        ),
        'paid' => array
        (
            'title' => 'paid date',
            'storage'    => 'paid',
            'type'    => 'date',
            'type_config' => array
            (
                'storage_type' => 'UNIXTIME',
            ),
            'widget'    => 'jsdate',
            'widget_config' => array
            (
                'show_time' => false
            ),
            'readonly'    => true,
            'hidden'    => true,
        ),
        'pdf_file' => array
        (
            'title' => 'pdf file',
            'type'    => 'blobs',
            'widget' => 'downloads',
            'type_config' => array
            (
                'sortable' => false,
                'max_count' => 1,
            ),
            'index_method' => 'attachment',
            'hidden' => true,
        ),
        'files' => array
        (
            'title' => 'Files',
            'type'    => 'blobs',
            'widget' => 'downloads',
            'type_config' => array
            (
                'sortable' => false,
            ),
            'end_fieldset' => '',
            'index_method' => 'attachment',
        ),
    ),
),