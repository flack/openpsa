// Match /
'user_list' => array
(
    'handler' => array('org_openpsa_user_handler_list', 'list'),
),
// Match /members/<group>
'user_list_members' => array
(
    'handler' => array('org_openpsa_user_handler_list', 'list'),
    'fixed_args' => 'members',
    'variable_args' => 1,
),
// Match /json/
'user_list_json' => array
(
    'handler' => array('org_openpsa_user_handler_list', 'json'),
    'fixed_args' => 'json',
),
// Match /json/
'user_list_json_members' => array
(
    'handler' => array('org_openpsa_user_handler_list', 'json'),
    'fixed_args' => array('json', 'members'),
    'variable_args' => 1,
),
// Match /view/<guid>/
'user_view' => array
(
    'handler' => array('org_openpsa_user_handler_person_view', 'view'),
    'fixed_args' => 'view',
    'variable_args' => 1,
),
// Match /edit/<guid>/
'user_edit' => array
(
    'handler' => array('org_openpsa_user_handler_person_edit', 'edit'),
    'fixed_args' => 'edit',
    'variable_args' => 1,
),
// Match /delete/<guid>/
'user_delete' => array
(
    'handler' => array('org_openpsa_user_handler_person_delete', 'delete'),
    'fixed_args' => 'delete',
    'variable_args' => 1,
),
// Match /person/notifications/<guid>/
'person_notifications' => array
(
    'handler' => array('org_openpsa_user_handler_person_notifications', 'notifications'),
    'fixed_args' => array('person', 'notifications'),
    'variable_args' => 1,
),
// Match /privileges/<guid>/
'user_privileges' => array
(
    'handler' => array('org_openpsa_user_handler_person_privileges', 'privileges'),
    'fixed_args' => 'privileges',
    'variable_args' => 1,
),
// Match /create/
'user_create' => array
(
    'handler' => array('org_openpsa_user_handler_person_create', 'create'),
    'fixed_args' => 'create',
),
// Match /create/<group guid>
'user_create_group' => array
(
    'handler' => array('org_openpsa_user_handler_person_create', 'create'),
    'fixed_args' => 'create',
    'variable_args' => 1
),
// Match /account/create/<guid>/
'account_create' => array
(
    'handler' => array('org_openpsa_user_handler_person_account', 'create'),
    'fixed_args' => array('account', 'create'),
    'variable_args' => 1,
),
// Match /account/edit/<guid>/
'account_edit' => array
(
    'handler' => array('org_openpsa_user_handler_person_account', 'edit'),
    'fixed_args' => array('account', 'edit'),
    'variable_args' => 1,
),
// Match /account/delete/<guid>/
'account_delete' => array
(
    'handler' => array('org_openpsa_user_handler_person_account', 'delete'),
    'fixed_args' => array('account', 'delete'),
    'variable_args' => 1,
),
// Match /account/su/<guid>/
'account_su' => array
(
    'handler' => array('org_openpsa_user_handler_person_account', 'su'),
    'fixed_args' => array('account', 'su'),
    'variable_args' => 1,
),
// Match /lostpassword/
'lostpassword' => array
(
    'handler' => array('org_openpsa_user_handler_lostpassword', 'lostpassword'),
    'fixed_args' => 'lostpassword',
),

// Match /groups/
'group_list' => array
(
    'handler' => array('org_openpsa_user_handler_group_list', 'list'),
    'fixed_args' => 'groups',
),
// Match /group/create/
'group_create' => array
(
    'handler' => array('org_openpsa_user_handler_group_create', 'create'),
    'fixed_args' => array('group', 'create'),
),
// Match /group/<guid>/
'group_view' => array
(
    'handler' => array('org_openpsa_user_handler_group_view', 'view'),
    'fixed_args' => array('group'),
    'variable_args' => 1,
),
// Match /group/edit/<guid>/
'group_edit' => array
(
    'handler' => array('org_openpsa_user_handler_group_edit', 'edit'),
    'fixed_args' => array('group', 'edit'),
    'variable_args' => 1,
),
// Match /group/delete/<guid>
'group_delete' => array
(
    'handler' => array('org_openpsa_user_handler_group_delete', 'delete'),
    'fixed_args' => array('group', 'delete'),
    'variable_args' => 1,
),
// Match /group/notifications/<guid>/
'group_notifications' => array
(
    'handler' => array('org_openpsa_user_handler_group_notifications', 'notifications'),
    'fixed_args' => array('group', 'notifications'),
    'variable_args' => 1,
),
// Match /group/privileges/<guid>/
'group_privileges' => array
(
    'handler' => array('org_openpsa_user_handler_group_privileges', 'privileges'),
    'fixed_args' => array('group', 'privileges'),
    'variable_args' => 1,
),
