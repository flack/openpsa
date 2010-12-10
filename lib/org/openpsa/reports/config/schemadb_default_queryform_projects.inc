'default' => array
(
    'description' => 'Query settings for Projects module reports',
    'save_text'   => 'Generate report',
    'fields'      => array
    (
            'component' => array
            (
                'title'   => 'Component this report is related to',
                'storage'      => 'relatedcomponent',
                'type'      => 'text',
                'widget'      => 'text',
                'hidden'        => true,
                'default'       => 'org.openpsa.projects',
            ),
            'mimetype' => array
            (
                'title'   => 'Report content-type',
                'storage'      => 'mimetype',
                'type'      => 'text',
                'widget'      => 'text',
                'hidden'        => true,
                'default'       => 'text/html',
            ),
            'extension' => array
            (
                'title'   => 'Report file extension',
                'storage'      => 'extension',
                'type'      => 'text',
                'widget'      => 'text',
                'hidden'        => true,
                'default'       => '.html',
                'end_fieldset'  => '',
            ),
            'style' => array
            (
                'title'   => 'Report style',
                'storage'      => 'style',
                'type'      => 'select',
                'type_config' => array
                (
                    'options' => array
                    (
                        'builtin:basic'     => 'Basic report',
                        'builtin:weekly'    => 'Weekly report',
                    ),
                ),
                'widget'        => 'radiocheckselect',
                'default'       => 'builtin:basic',
                'start_fieldset'  => array
                (
                    'title'     => 'report style',
                    'css_group' => 'area',
                ),
            ),
            'grouping' => array
            (
                'title'   => 'Report grouping',
                'type'      => 'select',
                'storage'      => 'parameter',
                'widget'        => 'radiocheckselect',
                'type_config' => array
                (
                    'options' => array
                    (
                        'date'      => 'date',
                        'person'    => 'person',
                    ),
                ),
                'default'       => 'date',
                'end_fieldset'  => '',
            ),
            'start' => array
            (
                'title'   => 'Start time',
                'storage'      => 'start',
                'type'      => 'date',
                'type_config' => array
                (
                    'storage_type' => 'UNIXTIME'
                ),
                'widget'      => 'jsdate',
                'widget_config' => array
                (
                    'show_time' => false,
                ),
                'default'       => strftime('%Y-%m-%d', mktime(0, 0, 1, date('n'), 1, date('Y'))),
                'start_fieldset'  => array
                (
                    'title'     => 'Timeframe',
                    'css_group' => 'area',
                ),
            ),
            'end' => array
            (
                'title'   => 'End time',
                'storage'      => 'end',
                'type'      => 'date',
                'type_config' => array
                (
                    'storage_type' => 'UNIXTIME'
                ),
                'widget'      => 'jsdate',
                'widget_config' => Array
                (
                    'show_time' => false,
                ),
                'default'       => strftime('%Y-%m-%d', mktime(0, 0, 1, date('n') + 1, 0, date('Y'))),
                'end_fieldset'    => '',
            ),
            'resource' => array
            (
                'title'   => 'Workgroup/Person',
                'storage'      => 'parameter',
                'type'      => 'select',
                'type_config' => array
                (
                     'options' => array_merge(array('all' => 'all'), org_openpsa_helpers_list::workgroups('first', true)),
                ),
                'widget'        => 'select',
                'start_fieldset'  => array
                (
                    'title'     => 'Scope',
                    'css_group' => 'area',
                ),
            ),
            'task' => array
            (
                'title'   => 'Root project/process',
                'storage'      => 'parameter',
                'type'      => 'select',
                'type_config' => array
                (
                     'options' => org_openpsa_helpers_list::projects(true),
                ),
                'widget'        => 'select',
            ),
            'invoiceable_filter' => array
            (
                'title'   => 'show invoiceable',
                'location'      => 'parameter',
                'type'      => 'select',
                'type_config' => array
                (
                     'options' => array
                     (
                         -1    => 'both',
                         0     => 'only uninvoiceable',
                         1     => 'only invoiceable',
                     ),
                ),
                'widget'      => 'radiocheckselect',
                'default'       => -1,
            ),
            'approved_filter' => array
            (
                'title'   => 'show approved',
                'location'      => 'parameter',
                'type'      => 'select',
                'type_config' => array
                (
                     'options' => array
                     (
                         -1    => 'both',
                         0     => 'only not approved',
                         1     => 'only approved',
                     ),
                ),
                'widget'      => 'radiocheckselect',
                'default'       => -1,
            ),
            'invoiced_filter' => array
            (
                'title'   => 'show invoiced',
                'location'      => 'parameter',
                'type'      => 'select',
                'type_config' => array
                (
                     'options' => array
                     (
                         -1    => 'both',
                         0     => 'only not invoiced',
                         1     => 'only invoiced',
                     ),
                ),
                'default'       => -1,
                'widget'      => 'radiocheckselect',
                'end_fieldset'    => '',
            ),
            'type' => array
            (
                'title'   => 'Save query for future',
                'storage'      => 'orgOpenpsaObtype',
                'type'      => 'select',
                'type_config' => array
                (
                    'options' => array
                    (
                        ORG_OPENPSA_OBTYPE_REPORT => 'yes',
                        ORG_OPENPSA_OBTYPE_REPORT_TEMPORARY => 'no',
                    ),
                ),
                'widget'        => 'radiocheckselect',
                'default'       => ORG_OPENPSA_OBTYPE_REPORT,
                'start_fieldset'  => array
                (
                    'title'     => 'Metadata',
                    'css_group' => 'area',
                ),
                'end_fieldset'  => '',
            ),

    ),
),
