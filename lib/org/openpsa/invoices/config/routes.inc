// Match /
'dashboard' => [
    'handler' => ['org_openpsa_invoices_handler_list', 'dashboard'],
],

// Match /projects/
'list_projects_uninvoiced' => [
    'fixed_args' => ['projects'],
    'handler' => ['org_openpsa_invoices_handler_projects', 'uninvoiced'],
],

// Match /list/json/<type>
'list_json_type' => [
    'handler' => ['org_openpsa_invoices_handler_list', 'json'],
    'fixed_args' => ['list', 'json'],
    'variable_args' => 1,
],

// Match /list/customer/all/<company guid>
'list_customer_all' => [
    'handler' => ['org_openpsa_invoices_handler_list', 'customer'],
    'fixed_args' => ['list', 'customer', 'all'],
    'variable_args' => 1,
],

// Match /list/deliverable/<deliverable guid>
'list_deliverable_all' => [
    'handler' => ['org_openpsa_invoices_handler_list', 'deliverable'],
    'fixed_args' => ['list', 'deliverable'],
    'variable_args' => 1,
],

// Match /scheduled/
'list_scheduled' => [
    'handler' => ['org_openpsa_invoices_handler_scheduled', 'list'],
    'fixed_args' => ['scheduled'],
],

// Match /invoice/new/
'invoice_new_nocustomer' => [
    'handler' => ['org_openpsa_invoices_handler_invoice_crud', 'create'],
    'fixed_args' => ['invoice', 'new'],
],

// Match /invoice/new/<company guid>
'invoice_new' => [
    'handler' => ['org_openpsa_invoices_handler_invoice_crud', 'create'],
    'fixed_args' => ['invoice', 'new'],
    'variable_args' => 1,
],

// Match /invoice/edit/<guid>
'invoice_edit' => [
    'handler' => ['org_openpsa_invoices_handler_invoice_crud', 'update'],
    'fixed_args' => ['invoice', 'edit'],
    'variable_args' => 1,
],

// Match /invoice/delete/<guid>
'invoice_delete' => [
    'handler' => ['org_openpsa_invoices_handler_invoice_crud', 'delete'],
    'fixed_args' => ['invoice', 'delete'],
    'variable_args' => 1,
],
// Match /invoice/action/mark_sent/
'invoice_mark_sent' => [
    'handler' => ['org_openpsa_invoices_handler_invoice_action', 'mark_sent'],
    'fixed_args' => ['invoice', 'action', 'mark_sent'],
],
// Match /invoice/action/send_by_mail/
'invoice_send_by_mail' => [
    'handler' => ['org_openpsa_invoices_handler_invoice_action', 'send_by_mail'],
    'fixed_args' => ['invoice', 'action', 'send_by_mail'],
],
// Match /invoice/action/mark_paid/
'invoice_mark_paid' => [
    'handler' => ['org_openpsa_invoices_handler_invoice_action', 'mark_paid'],
    'fixed_args' => ['invoice', 'action', 'mark_paid'],
],
// Match /invoice/action/create_cancelation/
'invoice_create_cancelation' => [
    'handler' => ['org_openpsa_invoices_handler_invoice_action', 'create_cancelation'],
    'fixed_args' => ['invoice', 'action', 'create_cancelation'],
],
// Match /invoice/action/create_pdf/
'create_pdf' => [
    'handler' => ['org_openpsa_invoices_handler_invoice_action', 'create_pdf'],
    'fixed_args' => ['invoice', 'action', 'create_pdf'],
],

// Match /invoice/recalculation/<guid>
'recalc_invoice' => [
    'handler' => ['org_openpsa_invoices_handler_invoice_items', 'recalculation'],
    'fixed_args' => ['invoice', 'recalculation'],
    'variable_args' => 1,
],

// Match /invoice/items/<guid>
'invoice_items' => [
    'handler' => ['org_openpsa_invoices_handler_invoice_items', 'items'],
    'fixed_args' => ['invoice', 'items'],
    'variable_args' => 1,
],

// Match /invoice/itemedit/<guid>
'invoice_item_edit' => [
    'handler' => ['org_openpsa_invoices_handler_invoice_items', 'itemedit'],
    'fixed_args' => ['invoice', 'itemedit'],
    'variable_args' => 1,
],

// Match /invoice/itemposition/
'invoice_item_position' => [
    'handler' => ['org_openpsa_invoices_handler_invoice_items', 'itemposition'],
    'fixed_args' => ['invoice', 'itemposition'],
],

// Match /invoice/<guid>
'invoice' => [
    'handler' => ['org_openpsa_invoices_handler_invoice_view', 'read'],
    'fixed_args' => ['invoice'],
    'variable_args' => 1,
],
// Match /billingdata/delete/<guid>
'billing_data_delete' => [
    'handler' => ['org_openpsa_invoices_handler_billingdata', 'delete'],
    'fixed_args' => ['billingdata', 'delete'],
    'variable_args' => 1,
],
// Match /billingdata/<customer guid>
'billing_data' => [
    'handler' => ['org_openpsa_invoices_handler_billingdata', 'edit'],
    'fixed_args' => ['billingdata'],
    'variable_args' => 1,
],

'invoice_rest' => [
    'handler' => ['org_openpsa_invoices_handler_rest_invoice', 'process'],
    'fixed_args' => ['rest', 'invoice'],
],

'billingdata_rest' => [
    'handler' => ['org_openpsa_invoices_handler_rest_billingdata', 'process'],
    'fixed_args' => ['rest', 'billingdata'],
],

// Match /config/
'config' => [
    'handler' => ['midcom_baseclasses_components_handler_configuration', 'config'],
    'fixed_args' => ['config'],
],

// Match /goto
'goto' => [
    'fixed_args' => ['goto'],
    'handler' => ['org_openpsa_invoices_handler_goto', 'goto'],
],
