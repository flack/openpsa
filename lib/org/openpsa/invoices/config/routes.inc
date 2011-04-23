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

// Match /invoice/new/
'invoice_new_nocustomer' => array
(
    'handler' => Array('org_openpsa_invoices_handler_crud', 'create'),
    'fixed_args' => array('invoice', 'new'),
),

// Match /invoice/new/<company guid>
'invoice_new' => array
(
    'handler' => Array('org_openpsa_invoices_handler_crud', 'create'),
    'fixed_args' => array('invoice', 'new'),
    'variable_args' => 1,
),

// Match /invoice/edit/<guid>
'invoice_edit' => array
(
    'handler' => Array('org_openpsa_invoices_handler_crud', 'update'),
    'fixed_args' => array('invoice', 'edit'),
    'variable_args' => 1,
),

// Match /invoice/delete/<guid>
'invoice_delete' => array
(
    'handler' => Array('org_openpsa_invoices_handler_crud', 'delete'),
    'fixed_args' => array('invoice', 'delete'),
    'variable_args' => 1,
),

// Match /invoice/mark_sent/<guid>
'invoice_mark_sent' => array
(
    'handler' => Array('org_openpsa_invoices_handler_action', 'mark_sent'),
    'fixed_args' => array('invoice', 'mark_sent'),
    'variable_args' => 1,
),

// Match /invoice/mark_paid/<guid>
'invoice_mark_paid' => array
(
    'handler' => Array('org_openpsa_invoices_handler_action', 'mark_paid'),
    'fixed_args' => array('invoice', 'mark_paid'),
    'variable_args' => 1,
),

// Match /invoice/recalculation/<guid>
'recalc_invoice' => array
(
    'handler' => Array('org_openpsa_invoices_handler_action', 'recalculation'),
    'fixed_args' => array('invoice', 'recalculation'),
    'variable_args' => 1,
),

// Match /invoice/items/<guid>
'invoice_items' => array
(
    'handler' => Array('org_openpsa_invoices_handler_action', 'items'),
    'fixed_args' => array('invoice', 'items'),
    'variable_args' => 1,
),


// Match /invoice/itemedit/<guid>
'invoice_item_edit' => array
(
    'handler' => Array('org_openpsa_invoices_handler_action', 'itemedit'),
    'fixed_args' => array('invoice', 'itemedit'),
    'variable_args' => 1,
),

// Match /invoice/pdf/<guid>
'create_pdf' => array
(
    'fixed_args' => array('invoice' , 'pdf'),
    'handler' => Array('org_openpsa_invoices_handler_crud', 'pdf'),
    'variable_args' => 1,
),
// Match /invoice/<guid>
'invoice' => array
(
    'handler' => Array('org_openpsa_invoices_handler_crud', 'read'),
    'fixed_args' => array('invoice'),
    'variable_args' => 1,
),
// Match /billingdata/create/<guid>
'billing_data_create' => array
(
    'handler' => array('org_openpsa_invoices_handler_billingdata', 'create'),
    'fixed_args' => array('billingdata' , 'create'),
    'variable_args' => 1,
),
// Match /billingdata/<guid>
'billing_data' => array
(
    'handler' => array('org_openpsa_invoices_handler_billingdata', 'billingdata'),
    'fixed_args' => array('billingdata'),
    'variable_args' => 1,
),

// Match /config/
'config' => array
(
    'handler' => array ('midcom_core_handler_configdm2', 'config'),
    'fixed_args' => array ('config'),
),

// Match /goto
'goto' => array
(
    'fixed_args' => array('goto'),
    'handler' => Array('org_openpsa_invoices_handler_goto', 'goto'),
),
