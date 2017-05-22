'hour_report' => array
(
    'description'        => 'hour report',
    'fields'      => array
    (
        'date' => array
        (
            'title' => 'date',
            'storage' => 'date',
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
            'required' => true,
        ),
        'hours' => array
        (
            'title' => 'hours',
            'storage' => 'hours',
            'type' => 'number',
            'type_config' => array
            (
                'precision' => 2,
            ),
            'widget'  => 'text',
            'required' => true,
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
        'invoiceable' => array
        (
            'title'   => 'invoiceable',
            'storage' => 'invoiceable',
            'type'    => 'boolean',
            'widget'  => 'checkbox',
        ),
        'task' => array
        (
            'title'   => 'task',
            'storage' => 'task',
            'required' => true,
            'type' => 'select',
            'type_config' => array
            (
                 'require_corresponding_option' => false,
                 'options' => array(),
            ),
            'widget' => 'autocomplete',
            'widget_config' => array
            (
                'class'       => 'org_openpsa_projects_task_dba',
                'component'   => 'org.openpsa.projects',
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
                'categorize_by_parent_label' => 'title',
            ),
        ),
        'person' => array
        (
            'title'   => 'person',
            'storage' => 'person',
            'required' => true,
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
                'searchfields'  => array
                (
                    'firstname',
                    'lastname',
                    'username',
                ),
                'orders'        => array
                (
                    array('lastname'    => 'ASC'),
                    array('firstname'    => 'ASC'),
                ),
            ),
        ),
        'invoice' => array
        (
            'title'   => 'invoice',
            'storage' => 'invoice',
            'type' => 'select',
            'type_config' => array
            (
                 'require_corresponding_option' => false,
            ),
            'widget' => 'autocomplete',
            'widget_config' => array
            (
                'class'       => 'org_openpsa_invoices_invoice_dba',
                'component'   => 'org.openpsa.invoices',
                'titlefield'  => 'number',
                'id_field'     => 'id',
                'result_headers' => array
                (
                    array
                    (
                        'title' => 'invoice number',
                        'name' => 'number',
                    ),
                ),
                'searchfields'  => array
                (
                    'number',
                ),
                'auto_wildcards' => false,
                'orders'        => array
                (
                    array('number'    => 'ASC'),
                ),
                'get_label_for' => 'number'
            ),
        ),
    )
),

