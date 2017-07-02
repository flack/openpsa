// Match /
'dashboard' => array
(
    'handler' => Array('org_openpsa_invoices_handler_list', 'dashboard'),
),

// Match /projects/
'list_projects_uninvoiced' => array
(
    'fixed_args' => array('projects'),
    'handler' => Array('org_openpsa_invoices_handler_projects', 'uninvoiced'),
),

// Match /list/json/<type>
'list_json_type' => array
(
    'handler' => Array('org_openpsa_invoices_handler_list', 'json'),
    'fixed_args' => array('list', 'json'),
    'variable_args' => 1,
),

// Match /list/customer/all/<company guid>
'list_customer_all' => array
(
    'handler' => Array('org_openpsa_invoices_handler_list', 'customer'),
    'fixed_args' => array('list', 'customer', 'all'),
    'variable_args' => 1,
),

// Match /list/deliverable/<deliverable guid>
'list_deliverable_all' => array
(
    'handler' => Array('org_openpsa_invoices_handler_list', 'deliverable'),
    'fixed_args' => array('list', 'deliverable'),
    'variable_args' => 1,
),

// Match /scheduled/
'list_scheduled' => array
(
    'handler' => Array('org_openpsa_invoices_handler_scheduled', 'list'),
    'fixed_args' => array('scheduled'),
),

// Match /invoice/new/
'invoice_new_nocustomer' => array
(
    'handler' => Array('org_openpsa_invoices_handler_invoice_crud', 'create'),
    'fixed_args' => array('invoice', 'new'),
),

// Match /invoice/new/<company guid>
'invoice_new' => array
(
    'handler' => Array('org_openpsa_invoices_handler_invoice_crud', 'create'),
    'fixed_args' => array('invoice', 'new'),
    'variable_args' => 1,
),

// Match /invoice/edit/<guid>
'invoice_edit' => array
(
    'handler' => Array('org_openpsa_invoices_handler_invoice_crud', 'update'),
    'fixed_args' => array('invoice', 'edit'),
    'variable_args' => 1,
),

// Match /invoice/delete/<guid>
'invoice_delete' => array
(
    'handler' => Array('org_openpsa_invoices_handler_invoice_crud', 'delete'),
    'fixed_args' => array('invoice', 'delete'),
    'variable_args' => 1,
),
// Match /invoice/action/mark_sent/
'invoice_mark_sent' => array
(
    'handler' => Array('org_openpsa_invoices_handler_invoice_action', 'mark_sent'),
    'fixed_args' => array('invoice', 'action', 'mark_sent'),
),
// Match /invoice/action/send_by_mail/
'invoice_send_by_mail' => array
(
    'handler' => Array('org_openpsa_invoices_handler_invoice_action', 'send_by_mail'),
    'fixed_args' => array('invoice', 'action', 'send_by_mail'),
),
// Match /invoice/action/mark_paid/
'invoice_mark_paid' => array
(
    'handler' => Array('org_openpsa_invoices_handler_invoice_action', 'mark_paid'),
    'fixed_args' => array('invoice', 'action', 'mark_paid'),
),
// Match /invoice/action/create_cancelation/
'invoice_create_cancelation' => array
(
    'handler' => Array('org_openpsa_invoices_handler_invoice_action', 'create_cancelation'),
    'fixed_args' => array('invoice', 'action', 'create_cancelation'),
),
// Match /invoice/action/create_pdf/
'create_pdf' => array
(
    'handler' => Array('org_openpsa_invoices_handler_invoice_action', 'create_pdf'),
    'fixed_args' => array('invoice', 'action', 'create_pdf'),
),

// Match /invoice/recalculation/<guid>
'recalc_invoice' => array
(
    'handler' => Array('org_openpsa_invoices_handler_invoice_items', 'recalculation'),
    'fixed_args' => array('invoice', 'recalculation'),
    'variable_args' => 1,
),

// Match /invoice/items/<guid>
'invoice_items' => array
(
    'handler' => Array('org_openpsa_invoices_handler_invoice_items', 'items'),
    'fixed_args' => array('invoice', 'items'),
    'variable_args' => 1,
),

// Match /invoice/itemedit/<guid>
'invoice_item_edit' => array
(
    'handler' => Array('org_openpsa_invoices_handler_invoice_items', 'itemedit'),
    'fixed_args' => array('invoice', 'itemedit'),
    'variable_args' => 1,
),

// Match /invoice/itemposition/
'invoice_item_position' => array
(
    'handler' => Array('org_openpsa_invoices_handler_invoice_items', 'itemposition'),
    'fixed_args' => array('invoice', 'itemposition'),
),

// Match /invoice/<guid>
'invoice' => array
(
    'handler' => Array('org_openpsa_invoices_handler_invoice_view', 'read'),
    'fixed_args' => array('invoice'),
    'variable_args' => 1,
),
// Match /billingdata/delete/<guid>
'billing_data_delete' => array
(
    'handler' => array('org_openpsa_invoices_handler_billingdata', 'delete'),
    'fixed_args' => array('billingdata', 'delete'),
    'variable_args' => 1,
),
// Match /billingdata/<customer guid>
'billing_data' => array
(
    'handler' => array('org_openpsa_invoices_handler_billingdata', 'edit'),
    'fixed_args' => array('billingdata'),
    'variable_args' => 1,
),

'invoice_rest' => array(
    'handler' => array('org_openpsa_invoices_handler_rest_invoice', 'process'),
    'fixed_args' => array('rest', 'invoice'),
),

'billingdata_rest' => array(
    'handler' => array('org_openpsa_invoices_handler_rest_billingdata', 'process'),
    'fixed_args' => array('rest', 'billingdata'),
),

// Match /config/
'config' => array
(
    'handler' => array ('midcom_baseclasses_components_handler_configuration', 'config'),
    'fixed_args' => array ('config'),
),

// Match /goto
'goto' => array
(
    'fixed_args' => array('goto'),
    'handler' => Array('org_openpsa_invoices_handler_goto', 'goto'),
),
