'name' => 'org.openpsa.invoices',
'icon' => 'file-text-o',
'purecode' => false,
'privileges' => [],
'class_mapping' => [
    'org_openpsa_invoice' => 'org_openpsa_invoices_invoice_dba',
    'org_openpsa_invoice_item' => 'org_openpsa_invoices_invoice_item_dba',
    'org_openpsa_billing_data' => 'org_openpsa_invoices_billing_data_dba',
],
'watches' => [
    [
        'classes' => [
            'org_openpsa_contacts_person_dba',
            'org_openpsa_contacts_group_dba',
        ],
        'operations' => MIDCOM_OPERATION_DBA_DELETE,
    ],
],
'description' => 'Simple invoicing status tracker',