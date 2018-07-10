// Administrative stuff
'edit' => [
    'handler' => ['net_nehmer_static_handler_admin', 'edit'],
    'fixed_args' => 'edit',
    'variable_args' => 1,
],
'delete' => [
    'handler' => ['net_nehmer_static_handler_admin', 'delete'],
    'fixed_args' => 'delete',
    'variable_args' => 1,
],
'create' => [
    'handler' => ['net_nehmer_static_handler_create', 'create'],
    'fixed_args' => 'create',
    'variable_args' => 1,
],
'createindex' => [
    'handler' => ['net_nehmer_static_handler_create', 'create'],
    'fixed_args' => 'createindex',
    'variable_args' => 1,
],
'config' => [
    'handler' => ['midcom_baseclasses_components_handler_configuration', 'config'],
    'fixed_args' => 'config',
],
// AJAX version of view, which skips style.
'view_raw' => [
    'handler' => ['net_nehmer_static_handler_view', 'view'],
    'fixed_args' => 'raw',
    'variable_args' => 1,
],

'view' => [
    'handler' => ['net_nehmer_static_handler_view', 'view'],
    'variable_args' => 1,
],
