'name' => 'org.openpsa.expenses',
'purecode' => false,
'privileges' => [],
'class_mapping' => [
    'org_openpsa_hour_report' => 'org_openpsa_expenses_hour_report_dba',
],
'description' => 'Tracking of work hours, expenses and mileages',
'watches' => [
    [
        'classes' => [
            'org_openpsa_invoices_invoice_dba',
        ],
        'operations' => MIDCOM_OPERATION_DBA_DELETE,
    ],
],
