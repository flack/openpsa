// Match /list/<state>
'list_state' => array
(
    'handler' => array('org_openpsa_sales_handler_list', 'list'),
    'fixed_args' => array('list'),
    'variable_args' => 1,
),
// Match /list/customer/<guid>
'list_customer' => array
(
    'handler' => array('org_openpsa_sales_handler_list', 'list'),
    'fixed_args' => array('list', 'customer'),
    'variable_args' => 1,
),

// Match /salesproject/edit/<salesproject>
'salesproject_edit' => array
(
    'handler' => array('org_openpsa_sales_handler_edit', 'edit'),
    'fixed_args' => array('salesproject', 'edit'),
    'variable_args' => 1,
),

// Match /salesproject/delete/<salesproject>
'salesproject_delete' => array
(
    'handler' => array('org_openpsa_sales_handler_edit', 'delete'),
    'fixed_args' => array('salesproject', 'delete'),
    'variable_args' => 1,
),

// Match /salesproject/new
'salesproject_new' => array
(
    'handler' => array('org_openpsa_sales_handler_edit', 'new'),
    'fixed_args' => array('salesproject', 'new'),
),

// Match /salesproject/new/<guid>
'salesproject_new_customer' => array
(
    'handler' => array('org_openpsa_sales_handler_edit', 'new'),
    'fixed_args' => array('salesproject', 'new'),
    'variable_args' => 1,
),

// Match /salesproject/deliverables/sort/
'salesproject_sort_deliveables' => array
(
    'handler' => array('org_openpsa_sales_handler_view', 'sort'),
    'fixed_args' => array('salesproject', 'deliverables', 'sort'),
),

// Match /salesproject/<salesproject>
'salesproject_view' => array
(
    'handler' => array('org_openpsa_sales_handler_view', 'view'),
    'fixed_args' => array('salesproject'),
    'variable_args' => 1,
),

// Match /deliverable/add/<salesproject>/
'deliverable_add' => array
(
    'handler' => array('org_openpsa_sales_handler_deliverable_add', 'add'),
    'fixed_args' => array('deliverable', 'add'),
    'variable_args' => 1,
),

// Match /deliverable/process/<deliverable>/
'deliverable_process' => array
(
    'handler' => array('org_openpsa_sales_handler_deliverable_process', 'process'),
    'fixed_args' => array('deliverable', 'process'),
    'variable_args' => 1,
),

// Match /deliverable/edit/<deliverable>
'deliverable_edit' => array
(
    'handler' => array('org_openpsa_sales_handler_deliverable_admin', 'edit'),
    'fixed_args' => array('deliverable', 'edit'),
    'variable_args' => 1,
),

// Match /deliverable/delete/<deliverable>
'deliverable_delete' => array
(
    'handler' => array('org_openpsa_sales_handler_deliverable_admin', 'delete'),
    'fixed_args' => array('deliverable', 'delete'),
    'variable_args' => 1,
),

// Match /deliverable/list/product/<product>
'deliverable_list_product' => array
(
    'handler' => array('org_openpsa_sales_handler_deliverable_list', 'product'),
    'fixed_args' => array('deliverable', 'list', 'product'),
    'variable_args' => 1,
),

// Match /deliverable/<deliverable>
'deliverable_view' => array
(
    'handler' => array('org_openpsa_sales_handler_deliverable_view', 'view'),
    'fixed_args' => array('deliverable'),
    'variable_args' => 1,
),

'deliverable_rest' => array(
    'handler' => array('org_openpsa_sales_handler_rest_deliverable', 'process'),
    'fixed_args' => array('rest', 'deliverable'),
    'variable_args' => 0,
),

'sales_order_rest' => array(
    'handler' => array('org_openpsa_sales_handler_rest_order', 'process'),
    'fixed_args' => array('rest', 'order'),
    'variable_args' => 0,
),

// Match /
'frontpage' => array
(
    'handler' => array('org_openpsa_sales_handler_list', 'list'),
),

// Match salesproject/render/<guid>/
'create_pdf' => array
(
    'handler' => array('org_openpsa_sales_handler_render', 'create_pdf'),
    'fixed_args' => array('salesproject', 'render'),
    'variable_args' => 1,
),
