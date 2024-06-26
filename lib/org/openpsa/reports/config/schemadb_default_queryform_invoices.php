<?php
return [
    'default' => [
        'description' => 'Query settings for Invoice module reports',
        'fields'      => [
            'component' => [
                'title'   => 'Component this report is related to',
                'storage'      => 'relatedcomponent',
                'type'      => 'text',
                'widget'      => 'hidden',
                'default'       => 'org.openpsa.invoices',
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
            ],
            'date_field' => [
                'title'   => 'Query by',
                'storage'      => 'parameter',
                'type'      => 'select',
                'type_config' => [
                    'options' => [
                        'date'     => midcom::get()->i18n->get_string('invoice date', 'org.openpsa.invoices'),
                        'sent'     => midcom::get()->i18n->get_string('sent date', 'org.openpsa.invoices'),
                        'due'      => midcom::get()->i18n->get_string('due date', 'org.openpsa.invoices'),
                        'paid'     => midcom::get()->i18n->get_string('paid date', 'org.openpsa.invoices'),
                    ],
                ],
                'widget'        => 'radiocheckselect',
                'default'       => 'due',
                'end_fieldset'    => '',
            ],
            'invoice_status' => [
                'title'   => 'invoice status',
                'storage'      => 'parameter',
                'type'      => 'select',
                'type_config' => [
                    'options' => [
                        'unsent'     => midcom::get()->i18n->get_string('unsent', 'org.openpsa.invoices'),
                        'open'     => midcom::get()->i18n->get_string('open', 'org.openpsa.invoices'),
                        'overdue'      => midcom::get()->i18n->get_string('overdue', 'org.openpsa.invoices'),
                        'paid'     => midcom::get()->i18n->get_string('paid', 'org.openpsa.invoices'),
                        'scheduled'     => midcom::get()->i18n->get_string('scheduled', 'org.openpsa.invoices'),
                    ],
                    'allow_multiple' => true
                ],
                'widget'        => 'radiocheckselect',
                'start_fieldset'  => [
                    'title'     => 'Scope',
                    'css_group' => 'area',
                ],
            ],
            'resource' => [
                'title'   => 'Workgroup/Person',
                'storage'      => 'parameter',
                'type'      => 'select',
                'type_config' => [
                     'options' => array_merge(['all' => 'all'], org_openpsa_helpers_list::workgroups('first', true)),
                ],
                'widget'        => 'select',
                'end_fieldset'    => '',
            ],
            'type' => [
                'title'   => 'save query for future',
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
    ]
];