'hour_report' => [
    'description'        => 'hour report',
    'fields'      => [
        'date' => [
            'title' => 'date',
            'storage' => 'date',
            'type' => 'date',
            'type_config' => [
                'storage_type' => 'UNIXTIME'
            ],
            'widget' => 'jsdate',
            'widget_config' => [
                'show_time' => false,
            ],
            'required' => true,
        ],
        'hours' => [
            'title' => 'hours',
            'storage' => 'hours',
            'type' => 'number',
            'type_config' => [
                'precision' => 2,
            ],
            'widget'  => 'text',
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
        'invoiceable' => [
            'title'   => 'invoiceable',
            'storage' => 'invoiceable',
            'type'    => 'boolean',
            'widget'  => 'checkbox',
        ],
        'task' => [
            'title'   => 'task',
            'storage' => 'task',
            'required' => true,
            'type' => 'select',
            'type_config' => [
                 'require_corresponding_option' => false,
                 'options' => [],
            ],
            'widget' => 'autocomplete',
            'widget_config' => [
                'clever_class' => 'task',
            ],
        ],
        'person' => [
            'title'   => 'person',
            'storage' => 'person',
            'required' => true,
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
        'invoice' => [
            'title'   => 'invoice',
            'storage' => 'invoice',
            'type' => 'select',
            'type_config' => [
                 'require_corresponding_option' => false,
            ],
            'widget' => 'autocomplete',
            'widget_config' => [
                'clever_class' => 'invoice',
                'auto_wildcards' => false,
            ],
        ],
    ]
],

