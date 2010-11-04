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
                'precision' => 1,
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
            'widget' => 'textarea',
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
            'type' => 'number',
            'widget' => 'text',
        ),
        'person' => array
        (
            'title'   => 'person',
            'storage' => 'person',
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
            'required' => true,
        ),
    )
),

