// Match /duplicates/person
'person_duplicates' => array
(
    'handler' => array('org_openpsa_contacts_handler_duplicates_person', 'sidebyside'),
    'fixed_args' => array('duplicates', 'person'),
),

// Match /mycontacts/
'mycontacts' => array
(
    'handler' => array('org_openpsa_contacts_handler_mycontacts', 'list'),
    'fixed_args' => 'mycontacts',
),

// Match /mycontacts/xml
'mycontacts_xml' => array
(
    'handler' => array('org_openpsa_contacts_handler_mycontacts', 'list'),
    'fixed_args' => array('mycontacts', 'xml'),
),

// Match /mycontacts/add/<person guid>
'mycontacts_add' => array
(
    'handler' => array('org_openpsa_contacts_handler_mycontacts', 'add'),
    'fixed_args' => array('mycontacts', 'add'),
    'variable_args' => 1,
),

// Match /mycontacts/remove/<person guid>
'mycontacts_remove' => array
(
    'handler' => array('org_openpsa_contacts_handler_mycontacts', 'remove'),
    'fixed_args' => array('mycontacts', 'remove'),
    'variable_args' => 1,
),

// Match /search/<type>
'search_type' => array
(
    'handler' => array('org_openpsa_contacts_handler_search', 'search_type'),
    'fixed_args' => 'search',
    'variable_args' => 1,
),

// Match /search/
'search' => array
(
    'handler' => array('org_openpsa_contacts_handler_search', 'search'),
    'fixed_args' => 'search',
),

// Match /group/create/<type>
'group_new' => array
(
    'handler' => array('org_openpsa_contacts_handler_group_create', 'create'),
    'fixed_args' => array('group', 'create'),
    'variable_args' => 1
),

// Match /group/create/<type>/<GUID>
'group_new_subgroup' => array
(
    'handler' => array('org_openpsa_contacts_handler_group_create', 'create'),
    'fixed_args' => array('group', 'create'),
    'variable_args' => 2,
),

// Match /group/edit/<GUID>
'group_edit' => array
(
    'handler' => array('org_openpsa_contacts_handler_group_edit', 'edit'),
    'fixed_args' => array('group', 'edit'),
    'variable_args' => 1,
),

// Match /group/<GUID>/<action>
'group_action' => array
(
    'handler' => array('org_openpsa_contacts_handler_group_action', 'action'),
    'fixed_args' => 'group',
    'variable_args' => 2,
),

// Match /group/<GUID>
'group_view' => array
(
    'handler' => array('org_openpsa_contacts_handler_group_view', 'view'),
    'fixed_args' => 'group',
    'variable_args' => 1,
),

// Match /person/create/GroupGUID
'person_new_group' => array
(
    'handler' => array('org_openpsa_contacts_handler_person_create', 'create'),
    'fixed_args' => array('person', 'create'),
    'variable_args' => 1,
),

// Match /person/create
'person_new' => array
(
    'handler' => array('org_openpsa_contacts_handler_person_create', 'create'),
    'fixed_args' => array('person', 'create'),
),

// Match /person/GUID
'person_view' => array
(
    'handler' => array('org_openpsa_contacts_handler_person_view', 'view'),
    'fixed_args' => 'person',
    'variable_args' => 1,
),

// Match /person/edit/GUID
'person_edit' => array
(
    'handler' => array('org_openpsa_contacts_handler_person_admin', 'edit'),
    'fixed_args' => array('person', 'edit'),
    'variable_args' => 1,
),

// Match /person/delete/GUID
'person_delete' => array
(
    'handler' => array('org_openpsa_contacts_handler_person_admin', 'delete'),
    'fixed_args' => array('person', 'delete'),
    'variable_args' => 1,
),

// Match /person/memberships/GUID/
'group_memberships' => array
(
    'handler' => array('org_openpsa_contacts_handler_person_view', 'group_memberships'),
    'fixed_args' => array('person', 'memberships'),
    'variable_args' => 1,
),

// Match /
'frontpage' => array
(
    'handler' => array('org_openpsa_contacts_handler_frontpage', 'frontpage'),
),

// Match /config/
'config' => array
(
    'handler' => array('midcom_baseclasses_components_handler_configuration', 'config'),
    'fixed_args' => 'config',
),
