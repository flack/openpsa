'default' => array
(
    'description' => 'Query settings for Invoice module reports',
    'fields'      => array
    (
        'component' => array
        (
            'title'   => 'Component this report is related to',
            'storage'      => 'relatedcomponent',
            'type'      => 'text',
            'widget'      => 'hidden',
            'default'       => 'org.openpsa.invoices',
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
        ),
        'date_field' => array
        (
            'title'   => 'Query by',
            'storage'      => 'parameter',
            'type'      => 'select',
            'type_config' => array
            (
                'options' =>  array
                (
                    'date'     => midcom::get()->i18n->get_string('invoice date', 'org.openpsa.invoices'),
                    'sent'     => midcom::get()->i18n->get_string('sent date', 'org.openpsa.invoices'),
                    'due'      => midcom::get()->i18n->get_string('due date', 'org.openpsa.invoices'),
                    'paid'     => midcom::get()->i18n->get_string('paid date', 'org.openpsa.invoices'),
                ),
            ),
            'widget'        => 'radiocheckselect',
            'default'       => 'due',
            'end_fieldset'    => '',
        ),
        'invoice_status' => array
        (
            'title'   => 'invoice status',
            'storage'      => 'parameter',
            'type'      => 'select',
            'type_config' => array
            (
                'options' =>  array
                (
                    'unsent'     => midcom::get()->i18n->get_string('unsent', 'org.openpsa.invoices'),
                    'open'     => midcom::get()->i18n->get_string('open', 'org.openpsa.invoices'),
                    'overdue'      => midcom::get()->i18n->get_string('overdue', 'org.openpsa.invoices'),
                    'paid'     => midcom::get()->i18n->get_string('paid', 'org.openpsa.invoices'),
                    'scheduled'     => midcom::get()->i18n->get_string('scheduled', 'org.openpsa.invoices'),
                ),
                'allow_multiple' => true
            ),
            'widget'        => 'radiocheckselect',
            'start_fieldset'  => array
            (
                'title'     => 'Scope',
                'css_group' => 'area',
            ),
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
            'end_fieldset'    => '',
        ),
        'type' => array
        (
            'title'   => 'save query for future',
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