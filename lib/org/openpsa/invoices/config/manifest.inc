'name' => 'org.openpsa.invoices',
'icon' => 'stock-icons/16x16/stock_mail-open.png',
'purecode' => false,
'version' => '9.1.0',
'state' => 'stable',
'privileges' => array(),
'class_mapping' => array
(
    'org_openpsa_invoice' => 'org_openpsa_invoices_invoice_dba',
    'org_openpsa_invoice_item' => 'org_openpsa_invoices_invoice_item_dba',
    'org_openpsa_billing_data' => 'org_openpsa_invoices_billing_data_dba',
),
'watches' => array
(
    array
    (
        'classes' => array(
            'org_openpsa_contacts_person_dba',
            'org_openpsa_contacts_group_dba',
            ),
        'operations' => MIDCOM_OPERATION_DBA_DELETE,
    ),
),
'description' => 'Simple invoicing status tracker',