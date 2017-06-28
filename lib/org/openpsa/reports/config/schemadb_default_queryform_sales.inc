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
            'widget'      => 'hidden',
            'default'       => 'org.openpsa.sales',
        ),
        'mimetype' => array
        (
            'title'   => 'Report content-type',
            'storage'      => 'mimetype',
            'type'      => 'text',
            'widget'      => 'hidden',
            'default'       => 'text/html',
        ),
        'extension' => array
        (
            'title'   => 'Report file extension',
            'storage'      => 'extension',
            'type'      => 'text',
            'widget'      => 'hidden',
            'default'       => '.html',
            'end_fieldset'  => '',
        ),
        'style' => array
        (
            'title'   => 'Report style',
            'storage'      => 'style',
            'type'      => 'text',
            'default' => 'builtin:basic',
            'widget'        => 'hidden',
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
            'default'       => mktime(0, 0, 1, date('n'), 1, date('Y')),
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
            'default'       => mktime(0, 0, 1, date('n') + 1, 0, date('Y')),
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
                    org_openpsa_reports_query_dba::OBTYPE_REPORT => 'yes',
                    org_openpsa_reports_query_dba::OBTYPE_REPORT_TEMPORARY => 'no',
                ),
            ),
            'widget'        => 'radiocheckselect',
            'default'       => org_openpsa_reports_query_dba::OBTYPE_REPORT_TEMPORARY,
            'start_fieldset'  => array
            (
                'title'     => 'Metadata',
                'css_group' => 'area',
            ),
        ),
        'title' => array
        (
            'title'   => 'title',
            'storage'      => 'title',
            'type'      => 'text',
            'widget'      => 'text',
            'end_fieldset'  => '',
        ),
    ),
),