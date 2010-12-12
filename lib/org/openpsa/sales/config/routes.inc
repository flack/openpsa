// Match /list/<status>
'list_status' => array
(
    'handler' => array('org_openpsa_sales_handler_list', 'list'),
    'fixed_args' => array('list'),
    'variable_args' => 1,
),

// Match /salesproject/edit/<salesproject>
'salesproject_edit' => array
(
    'handler' => array('org_openpsa_sales_handler_edit', 'edit'),
    'fixed_args' => array('salesproject', 'edit'),
    'variable_args' => 1,
),

// Match /salesproject/new
'salesproject_new' => array
(
    'handler' => array('org_openpsa_sales_handler_edit', 'new'),
    'fixed_args' => array('salesproject', 'new'),
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

// Match /deliverable/<deliverable>
'deliverable_view' => array
(
    'handler' => array('org_openpsa_sales_handler_deliverable_view', 'view'),
    'fixed_args' => array('deliverable'),
    'variable_args' => 1,
),

// Match /
'frontpage' => array
(
    'handler' => array('org_openpsa_sales_handler_frontpage', 'frontpage'),
),
