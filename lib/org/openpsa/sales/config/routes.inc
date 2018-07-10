// Match /list/<state>
'list_state' => [
    'handler' => ['org_openpsa_sales_handler_list', 'list'],
    'fixed_args' => ['list'],
    'variable_args' => 1,
],
// Match /list/customer/<guid>
'list_customer' => [
    'handler' => ['org_openpsa_sales_handler_list', 'list'],
    'fixed_args' => ['list', 'customer'],
    'variable_args' => 1,
],

// Match /salesproject/edit/<salesproject>
'salesproject_edit' => [
    'handler' => ['org_openpsa_sales_handler_edit', 'edit'],
    'fixed_args' => ['salesproject', 'edit'],
    'variable_args' => 1,
],

// Match /salesproject/delete/<salesproject>
'salesproject_delete' => [
    'handler' => ['org_openpsa_sales_handler_edit', 'delete'],
    'fixed_args' => ['salesproject', 'delete'],
    'variable_args' => 1,
],

// Match /salesproject/new
'salesproject_new' => [
    'handler' => ['org_openpsa_sales_handler_edit', 'new'],
    'fixed_args' => ['salesproject', 'new'],
],

// Match /salesproject/new/<guid>
'salesproject_new_customer' => [
    'handler' => ['org_openpsa_sales_handler_edit', 'new'],
    'fixed_args' => ['salesproject', 'new'],
    'variable_args' => 1,
],

// Match /salesproject/deliverables/sort/
'salesproject_sort_deliveables' => [
    'handler' => ['org_openpsa_sales_handler_view', 'sort'],
    'fixed_args' => ['salesproject', 'deliverables', 'sort'],
],

// Match /salesproject/<salesproject>
'salesproject_view' => [
    'handler' => ['org_openpsa_sales_handler_view', 'view'],
    'fixed_args' => ['salesproject'],
    'variable_args' => 1,
],

// Match /deliverable/add/<salesproject>/
'deliverable_add' => [
    'handler' => ['org_openpsa_sales_handler_deliverable_add', 'add'],
    'fixed_args' => ['deliverable', 'add'],
    'variable_args' => 1,
],

// Match /deliverable/process/<deliverable>/
'deliverable_process' => [
    'handler' => ['org_openpsa_sales_handler_deliverable_process', 'process'],
    'fixed_args' => ['deliverable', 'process'],
    'variable_args' => 1,
],

// Match /deliverable/edit/<deliverable>
'deliverable_edit' => [
    'handler' => ['org_openpsa_sales_handler_deliverable_admin', 'edit'],
    'fixed_args' => ['deliverable', 'edit'],
    'variable_args' => 1,
],

// Match /deliverable/delete/<deliverable>
'deliverable_delete' => [
    'handler' => ['org_openpsa_sales_handler_deliverable_admin', 'delete'],
    'fixed_args' => ['deliverable', 'delete'],
    'variable_args' => 1,
],

// Match /deliverable/list/product/<product>
'deliverable_list_product' => [
    'handler' => ['org_openpsa_sales_handler_deliverable_list', 'product'],
    'fixed_args' => ['deliverable', 'list', 'product'],
    'variable_args' => 1,
],

// Match /deliverable/<deliverable>
'deliverable_view' => [
    'handler' => ['org_openpsa_sales_handler_deliverable_view', 'view'],
    'fixed_args' => ['deliverable'],
    'variable_args' => 1,
],

'deliverable_rest' => [
    'handler' => ['org_openpsa_sales_handler_rest_deliverable', 'process'],
    'fixed_args' => ['rest', 'deliverable'],
    'variable_args' => 0,
],

'sales_order_rest' => [
    'handler' => ['org_openpsa_sales_handler_rest_order', 'process'],
    'fixed_args' => ['rest', 'order'],
    'variable_args' => 0,
],

// Match /
'frontpage' => [
    'handler' => ['org_openpsa_sales_handler_list', 'list'],
],

// Match salesproject/offer/<guid>/
'create_offer' => [
    'handler' => ['org_openpsa_sales_handler_offer', 'create'],
    'fixed_args' => ['salesproject', 'offer'],
    'variable_args' => 1,
],

// Match salesproject/offer/edit/<guid>/
'edit_offer' => [
    'handler' => ['org_openpsa_sales_handler_offer', 'edit'],
    'fixed_args' => ['salesproject', 'offer', 'edit'],
    'variable_args' => 1,
],

// Match salesproject/offer/delete/<guid>/
'delete_offer' => [
    'handler' => ['org_openpsa_sales_handler_offer', 'delete'],
    'fixed_args' => ['salesproject', 'offer', 'delete'],
    'variable_args' => 1,
],
