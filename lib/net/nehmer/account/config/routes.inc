// DM2 configuration screen
'config' => array
(
    'handler' => array('net_nehmer_account_handler_configuration', 'configuration'),
    'fixed_args' => array('config'),
),

// INVITATION
'sent_invites' => Array
(
    'handler' => Array('net_nehmer_account_handler_invitation', 'sent_invites'),
    'fixed_args' => Array('sent_invites'),
),

'invite' => Array
(
    'handler' => Array('net_nehmer_account_handler_invitation', 'invite'),
    'fixed_args' => Array('invite'),
),
'delete_invite' => Array
(
    'handler' => Array('net_nehmer_account_handler_invitation', 'delete_invite'),
    'fixed_args' => Array('delete_invite'),
    'variable_args' => 1,
),
'remind_invite' => Array
(
    'handler' => Array('net_nehmer_account_handler_invitation', 'remind_invite'),
    'fixed_args' => Array('remind_invite'),
    'variable_args' => 1,
),

// VIEW LINKS
'root' => Array
(
    'handler' => Array('net_nehmer_account_handler_view', 'view'),
),
'self' => Array
(
    'handler' => Array('net_nehmer_account_handler_view', 'view'),
    'fixed_args' => Array('me'),
),
'self_quick' => Array
(
    'handler' => Array('net_nehmer_account_handler_view', 'view'),
    'fixed_args' => Array('me', 'quick'),
),
'other' => Array
(
    'handler' => Array('net_nehmer_account_handler_view', 'view'),
    'fixed_args' => 'view',
    'variable_args' => 1,
),
'other_quick' => Array
(
    'handler' => Array('net_nehmer_account_handler_view', 'view'),
    'fixed_args' => Array('view', 'quick'),
    'variable_args' => 1,
),
'list' => Array
(
    'handler' => Array('net_nehmer_account_handler_list', 'list'),
    'fixed_args' => Array('list'),
),
'list_by_category' => Array
(
    'handler' => Array('net_nehmer_account_handler_list', 'list_by_category'),
    'fixed_args' => Array('list', 'category'),
    'variable_args' => 1,
),
'list_by_alpha' => Array
(
    'handler' => Array('net_nehmer_account_handler_list', 'list'),
    'fixed_args' => Array('list', 'alpha'),
    'variable_args' => 1,
),
'list_random' => Array
(
    'handler' => Array('net_nehmer_account_handler_list', 'list_random'),
    'fixed_args' => Array('list', 'random'),
    'variable_args' => 1,
),
// EDIT LINKS
'edit' => Array
(
    'handler' => Array('net_nehmer_account_handler_edit', 'edit'),
    'fixed_args' => Array('edit'),
),

'lostpassword_reset' => Array
(
    'handler' => Array('net_nehmer_account_handler_maintain', 'lostpassword_reset'),
    'fixed_args' => Array('lostpassword', 'reset'),
    'variable_args' => 2,
),
'lostpassword' => Array
(
    'handler' => Array('net_nehmer_account_handler_maintain', 'lostpassword'),
    'fixed_args' => Array('lostpassword'),
),

// ADMIN LINKS
'admin_edit' => Array
(
    'handler' => Array('net_nehmer_account_handler_edit', 'edit'),
    'fixed_args' => Array('admin', 'edit'),
    'variable_args' => 1,
),

// REGISTRATION LINKS
'register_finish' => Array
(
    'handler' => Array('net_nehmer_account_handler_register', 'finish'),
    'fixed_args' => Array('register','finish'),
),
'register_select_type' => Array
(
    'handler' => Array('net_nehmer_account_handler_register', 'select_type'),
    'fixed_args' => Array('register'),
),
'register' => Array
(
    'handler' => Array('net_nehmer_account_handler_register', 'register'),
    'fixed_args' => Array('register'),
    'variable_args' => 1,
),
'register_activate' => Array
(
    'handler' => Array('net_nehmer_account_handler_register', 'activate'),
    'fixed_args' => Array('register', 'activate'),
    'variable_args' => 2,
),
'register_invitation' => Array
(
    'handler' => Array('net_nehmer_account_handler_register', 'register_invitation'),
    'fixed_args' => Array('register_invitation'),
    'variable_args' => 1,
),
