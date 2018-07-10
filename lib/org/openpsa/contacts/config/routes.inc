// Match /duplicates/person
'person_duplicates' => [
    'handler' => ['org_openpsa_contacts_handler_duplicates_person', 'sidebyside'],
    'fixed_args' => ['duplicates', 'person'],
],

// Match /mycontacts/
'mycontacts' => [
    'handler' => ['org_openpsa_contacts_handler_mycontacts', 'list'],
    'fixed_args' => 'mycontacts',
],

// Match /mycontacts/add/<person guid>
'mycontacts_add' => [
    'handler' => ['org_openpsa_contacts_handler_mycontacts', 'add'],
    'fixed_args' => ['mycontacts', 'add'],
    'variable_args' => 1,
],

// Match /mycontacts/remove/<person guid>
'mycontacts_remove' => [
    'handler' => ['org_openpsa_contacts_handler_mycontacts', 'remove'],
    'fixed_args' => ['mycontacts', 'remove'],
    'variable_args' => 1,
],

// Match /search/autocomplete
'search_autocomplete' => [
    'handler' => ['org_openpsa_contacts_handler_search', 'search'],
    'fixed_args' => ['search', 'autocomplete']
],
// Match /search/<type>
'search_type' => [
    'handler' => ['org_openpsa_contacts_handler_search', 'search_type'],
    'fixed_args' => 'search',
    'variable_args' => 1,
],

// Match /search/
'search' => [
    'handler' => ['org_openpsa_contacts_handler_search', 'search'],
    'fixed_args' => 'search',
],

// Match /group/create/<type>
'group_new' => [
    'handler' => ['org_openpsa_contacts_handler_group_create', 'create'],
    'fixed_args' => ['group', 'create'],
    'variable_args' => 1
],

// Match /group/create/<type>/<GUID>
'group_new_subgroup' => [
    'handler' => ['org_openpsa_contacts_handler_group_create', 'create'],
    'fixed_args' => ['group', 'create'],
    'variable_args' => 2,
],

// Match /group/edit/<GUID>
'group_edit' => [
    'handler' => ['org_openpsa_contacts_handler_group_edit', 'edit'],
    'fixed_args' => ['group', 'edit'],
    'variable_args' => 1,
],

// Match /group/<GUID>
'group_view' => [
    'handler' => ['org_openpsa_contacts_handler_group_view', 'view'],
    'fixed_args' => 'group',
    'variable_args' => 1,
],
// Match /group/json/<GUID>
'group_view_json' => [
    'handler' => ['org_openpsa_contacts_handler_group_view', 'json'],
    'fixed_args' => ['group', 'json'],
    'variable_args' => 1,
],

// Match /group/update_member_title/<GUID>
'group_update_member_title' => [
    'handler' => ['org_openpsa_contacts_handler_group_action', 'update_member_title'],
    'fixed_args' => ['group', 'update_member_title'],
    'variable_args' => 1,
],

// Match /group/members/<GUID>
'group_members' => [
    'handler' => ['org_openpsa_contacts_handler_group_action', 'members'],
    'fixed_args' => ['group', 'members'],
    'variable_args' => 1,
],

// Match /group/subgroups/<GUID>
'group_subgroups' => [
    'handler' => ['org_openpsa_contacts_handler_group_action', 'subgroups'],
    'fixed_args' => ['group', 'subgroups'],
    'variable_args' => 1,
],

// Match /person/create/GroupGUID
'person_new_group' => [
    'handler' => ['org_openpsa_contacts_handler_person_create', 'create'],
    'fixed_args' => ['person', 'create'],
    'variable_args' => 1,
],

// Match /person/create
'person_new' => [
    'handler' => ['org_openpsa_contacts_handler_person_create', 'create'],
    'fixed_args' => ['person', 'create'],
],

// Match /person/GUID
'person_view' => [
    'handler' => ['org_openpsa_contacts_handler_person_view', 'view'],
    'fixed_args' => 'person',
    'variable_args' => 1,
],

// Match /person/edit/GUID
'person_edit' => [
    'handler' => ['org_openpsa_contacts_handler_person_admin', 'edit'],
    'fixed_args' => ['person', 'edit'],
    'variable_args' => 1,
],

// Match /person/delete/GUID
'person_delete' => [
    'handler' => ['org_openpsa_contacts_handler_person_admin', 'delete'],
    'fixed_args' => ['person', 'delete'],
    'variable_args' => 1,
],

// Match /person/memberships/GUID/
'group_memberships' => [
    'handler' => ['org_openpsa_contacts_handler_person_view', 'group_memberships'],
    'fixed_args' => ['person', 'memberships'],
    'variable_args' => 1,
],

'person_rest' => [
    'handler' => ['org_openpsa_contacts_handler_rest_person', 'process'],
    'fixed_args' => ['rest', 'person'],
    'variable_args' => 0,
],

// Match /
'frontpage' => [
    'handler' => ['org_openpsa_contacts_handler_frontpage', 'frontpage'],
],

// Match /config/
'config' => [
    'handler' => ['midcom_baseclasses_components_handler_configuration', 'config'],
    'fixed_args' => 'config',
],
