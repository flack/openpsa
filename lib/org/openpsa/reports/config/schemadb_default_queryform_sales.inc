'default' => array
(

    'description' => 'Query settings for Projects module reports',
    'fields'      => array
    (
            'component' => array
            (
                'title'   => 'Component this report is related to',
                'storage'      => 'relatedcomponent',
                'type'      => 'text',
                'widget'      => 'text',
                'hidden'        => true,
                'default'       => 'org.openpsa.sales',
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
                        'builtin:deliverable'     => 'deliverable report',
                    ),
                ),
                'widget'        => 'radiocheckselect',
                'default'       => 'builtin:deliverable',
                'start_fieldset'  => array
                (
                    'title'     => 'report style',
                    'css_group' => 'area',
                ),
                'hidden' => true,
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
                'default'       => array('start_date' =>strftime('%Y-%m-%d', mktime(0, 0, 1, date('n'), 1, date('Y')))),
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
                'default'       => array('end_date' => strftime('%Y-%m-%d', mktime(0, 0, 1, date('n') + 1, 0, date('Y')))),
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