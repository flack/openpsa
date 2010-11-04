'default' => array
(
    'description' => 'filters',
    'fields' => array
    (
        'task' => array
        (
            'title'   => 'task',
            'storage' => null,
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
                'class'       => 'org_openpsa_projects_task_dba',
                'component'   => 'org.openpsa.projects',
                'titlefield'  => 'title',
                'id_field'     => 'id',
                'constraints' => array
                (
                    array
                    (
                        'field' => 'orgOpenpsaObtype',
                        'op'    => '=',
                        'value' => ORG_OPENPSA_OBTYPE_TASK,
                    ),
                ),
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
                'generate_path_for' => 'title',
            ),
            'required' => true,
        ),
        'invoice' => array
        (
            'title'   => 'invoice',
            'storage' => null,
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
                'class' => 'org_openpsa_invoices_invoice_dba',
                'component' => 'org.openpsa.invoices',
                'titlefield' => 'number',
                'id_field' => 'id',

                'result_headers' => array
                (
                    array
                    (
                        'title' => 'number',
                        'name' => 'number',
                    ),
                ),

                'searchfields' => array
                (
                    'number',
                    'invoiceNumber',
                    'description',
                ),
                'orders' => array
                (
                    array('number' => 'ASC'),
                ),

            ),
            'required' => true,
        ),
    ),
),
