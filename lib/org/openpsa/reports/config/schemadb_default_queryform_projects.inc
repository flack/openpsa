'default' => [
    'description' => 'Query settings for Projects module reports',
    'fields'      => [
        'component' => [
            'title'   => 'Component this report is related to',
            'storage'      => 'relatedcomponent',
            'type'      => 'text',
            'widget'      => 'hidden',
            'default'       => 'org.openpsa.projects',
        ],
        'mimetype' => [
            'title'   => 'Report content-type',
            'storage'      => 'mimetype',
            'type'      => 'text',
            'widget'      => 'hidden',
            'default'       => 'text/html',
        ],
        'extension' => [
            'title'   => 'Report file extension',
            'storage'      => 'extension',
            'type'      => 'text',
            'widget'      => 'hidden',
            'default'       => '.html',
            'end_fieldset'  => '',
        ],
        'style' => [
            'title'   => 'Report style',
            'storage'      => 'style',
            'type'      => 'text',
            'default' => 'builtin:basic',
            'widget'        => 'hidden',
        ],
        'grouping' => [
            'title'   => 'Report grouping',
            'type'      => 'select',
            'storage'      => 'parameter',
            'widget'        => 'radiocheckselect',
            'type_config' => [
                'options' => [
                    'date'      => 'date',
                    'person'    => 'person',
                ],
            ],
            'default'       => 'date',
            'start_fieldset'  => [
                'title'     => 'report style',
                'css_group' => 'area',
            ],
            'end_fieldset'  => '',
        ],
        'start' => [
            'title'   => 'Start time',
            'storage'      => 'start',
            'type'      => 'date',
            'type_config' => [
                'storage_type' => 'UNIXTIME'
            ],
            'widget'      => 'jsdate',
            'widget_config' => [
                'show_time' => false,
            ],
            'default'       => mktime(0, 0, 1, date('n'), 1, date('Y')),
            'start_fieldset'  => [
                'title'     => 'Timeframe',
                'css_group' => 'area',
            ],
        ],
        'end' => [
            'title'   => 'End time',
            'storage'      => 'end',
            'type'      => 'date',
            'type_config' => [
                'storage_type' => 'UNIXTIME'
            ],
            'widget'      => 'jsdate',
            'widget_config' => [
                'show_time' => false,
            ],
            'default'       => mktime(0, 0, 1, date('n') + 1, 0, date('Y')),
            'end_fieldset'    => '',
        ],
        'resource' => [
            'title'   => 'Workgroup/Person',
            'storage'      => 'parameter',
            'type'      => 'select',
            'type_config' => [
                 'options' => array_merge(['all' => 'all'], org_openpsa_helpers_list::workgroups('first', true)),
            ],
            'widget'        => 'select',
            'start_fieldset'  => [
                'title'     => 'Scope',
                'css_group' => 'area',
            ],
        ],
        'task' => [
            'title'   => 'Root project/process',
            'storage'      => 'parameter',
            'type'      => 'select',
            'type_config' => [
                 'options' => org_openpsa_helpers_list::projects(),
            ],
            'widget'        => 'select',
        ],
        'invoiceable_filter' => [
            'title'   => 'show invoiceable',
            'storage'      => 'parameter',
            'type'      => 'select',
            'type_config' => [
                 'options' => [
                     -1    => 'both',
                     0     => 'only uninvoiceable',
                     1     => 'only invoiceable',
                 ],
            ],
            'widget'      => 'radiocheckselect',
            'default'       => -1,
        ],
        'invoiced_filter' => [
            'title'   => 'show invoiced',
            'storage'      => 'parameter',
            'type'      => 'select',
            'type_config' => [
                 'options' => [
                     -1    => 'both',
                     0     => 'only not invoiced',
                     1     => 'only invoiced',
                 ],
            ],
            'default'       => -1,
            'widget'      => 'radiocheckselect',
            'end_fieldset'    => '',
        ],
        'type' => [
            'title'   => 'Save query for future',
            'storage'      => 'orgOpenpsaObtype',
            'type'      => 'select',
            'type_config' => [
                'options' => [
                    org_openpsa_reports_query_dba::OBTYPE_REPORT => 'yes',
                    org_openpsa_reports_query_dba::OBTYPE_REPORT_TEMPORARY => 'no',
                ],
            ],
            'widget'        => 'radiocheckselect',
            'default'       => org_openpsa_reports_query_dba::OBTYPE_REPORT_TEMPORARY,
            'start_fieldset'  => [
                'title'     => 'Metadata',
                'css_group' => 'area',
            ],
        ],
        'title' => [
            'title'   => 'title',
            'storage'      => 'title',
            'type'      => 'text',
            'widget'      => 'text',
            'end_fieldset'  => '',
        ],
    ],
],
