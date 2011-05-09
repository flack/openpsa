'schemadb'             => 'file:/org/openpsa/invoices/config/schemadb_default.inc',
'schemadb_config'      => 'file:/org/openpsa/invoices/config/schemadb_config.inc',
'schemadb_billing_data' => 'file:/org/openpsa/invoices/config/schemadb_default_billing_data.inc',
'default_hourly_price' => 0,
'default_due_days'     => 14,
'vat_percentages'      => '0,17,22',
'invoice_sender'       => '',
'invoice_number_format' => '#%06d',
//contains the class file to create the pdf for the invoice , if set to false pdf-creation is disabled
'invoice_pdfbuilder_class' => 'org_openpsa_invoices_pdfbuilder_default',

'invoice_mail_from_address' => 'info@vioworld.de',

//firstname, lastname, invoice_label
'invoice_mail_title' => 'Rechnung __INVOICE_LABEL__',
'invoice_mail_body' =>
'Hallo __FIRSTNAME__ __LASTNAME__,

hiermit lassen wir Ihnen Rechnung __INVOICE_LABEL__ zukommen!',
