// Match /
'user_list' => [
    'handler' => ['org_openpsa_user_handler_list', 'list'],
],
// Match /members/<group>
'user_list_members' => [
    'handler' => ['org_openpsa_user_handler_list', 'list'],
    'fixed_args' => 'members',
    'variable_args' => 1,
],
// Match /json/
'user_list_json' => [
    'handler' => ['org_openpsa_user_handler_list', 'json'],
    'fixed_args' => 'json',
],
// Match /json/
'user_list_json_members' => [
    'handler' => ['org_openpsa_user_handler_list', 'json'],
    'fixed_args' => ['json', 'members'],
    'variable_args' => 1,
],
// Match /view/<guid>/
'user_view' => [
    'handler' => ['org_openpsa_user_handler_person_view', 'view'],
    'fixed_args' => 'view',
    'variable_args' => 1,
],
// Match /edit/<guid>/
'user_edit' => [
    'handler' => ['org_openpsa_user_handler_person_edit', 'edit'],
    'fixed_args' => 'edit',
    'variable_args' => 1,
],
// Match /delete/<guid>/
'user_delete' => [
    'handler' => ['org_openpsa_user_handler_person_delete', 'delete'],
    'fixed_args' => 'delete',
    'variable_args' => 1,
],
// Match /person/notifications/<guid>/
'person_notifications' => [
    'handler' => ['org_openpsa_user_handler_person_notifications', 'notifications'],
    'fixed_args' => ['person', 'notifications'],
    'variable_args' => 1,
],
// Match /privileges/<guid>/
'user_privileges' => [
    'handler' => ['org_openpsa_user_handler_person_privileges', 'privileges'],
    'fixed_args' => 'privileges',
    'variable_args' => 1,
],
// Match /create/
'user_create' => [
    'handler' => ['org_openpsa_user_handler_person_create', 'create'],
    'fixed_args' => 'create',
],
// Match /create/<group guid>
'user_create_group' => [
    'handler' => ['org_openpsa_user_handler_person_create', 'create'],
    'fixed_args' => 'create',
    'variable_args' => 1
],
// Match /account/create/<guid>/
'account_create' => [
    'handler' => ['org_openpsa_user_handler_person_account', 'create'],
    'fixed_args' => ['account', 'create'],
    'variable_args' => 1,
],
// Match /account/edit/<guid>/
'account_edit' => [
    'handler' => ['org_openpsa_user_handler_person_account', 'edit'],
    'fixed_args' => ['account', 'edit'],
    'variable_args' => 1,
],
// Match /account/delete/<guid>/
'account_delete' => [
    'handler' => ['org_openpsa_user_handler_person_account', 'delete'],
    'fixed_args' => ['account', 'delete'],
    'variable_args' => 1,
],
// Match /account/su/<guid>/
'account_su' => [
    'handler' => ['org_openpsa_user_handler_person_account', 'su'],
    'fixed_args' => ['account', 'su'],
    'variable_args' => 1,
],
// Match /lostpassword/
'lostpassword' => [
    'handler' => ['org_openpsa_user_handler_lostpassword', 'lostpassword'],
    'fixed_args' => 'lostpassword',
],

// Match /groups/
'group_list' => [
    'handler' => ['org_openpsa_user_handler_group_list', 'list'],
    'fixed_args' => 'groups',
],
// Match /group/create/
'group_create' => [
    'handler' => ['org_openpsa_user_handler_group_create', 'create'],
    'fixed_args' => ['group', 'create'],
],
// Match /group/<guid>/
'group_view' => [
    'handler' => ['org_openpsa_user_handler_group_view', 'view'],
    'fixed_args' => ['group'],
    'variable_args' => 1,
],
// Match /group/edit/<guid>/
'group_edit' => [
    'handler' => ['org_openpsa_user_handler_group_edit', 'edit'],
    'fixed_args' => ['group', 'edit'],
    'variable_args' => 1,
],
// Match /group/delete/<guid>
'group_delete' => [
    'handler' => ['org_openpsa_user_handler_group_delete', 'delete'],
    'fixed_args' => ['group', 'delete'],
    'variable_args' => 1,
],
// Match /group/notifications/<guid>/
'group_notifications' => [
    'handler' => ['org_openpsa_user_handler_group_notifications', 'notifications'],
    'fixed_args' => ['group', 'notifications'],
    'variable_args' => 1,
],
// Match /group/privileges/<guid>/
'group_privileges' => [
    'handler' => ['org_openpsa_user_handler_group_privileges', 'privileges'],
    'fixed_args' => ['group', 'privileges'],
    'variable_args' => 1,
],
