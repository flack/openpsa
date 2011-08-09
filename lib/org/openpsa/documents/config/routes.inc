// Match /document/create/choosefolder
'document-create-choosefolder' => array
(
    'handler' => array('org_openpsa_documents_handler_document_create', 'create'),
    'fixed_args' => array('document', 'create', 'choosefolder'),
),

// Match /document/create
'document-create' => array
(
    'handler' => array('org_openpsa_documents_handler_document_create', 'create'),
    'fixed_args' => array('document', 'create'),
),

// Match /document/delete/<document GUID>
'document-delete' => array
(
    'handler' => array('org_openpsa_documents_handler_document_admin', 'delete'),
    'fixed_args' => array('document', 'delete'),
    'variable_args' => 1,
),
// Match /document/edit/<document GUID>
'document-edit' => array
(
    'handler' => array('org_openpsa_documents_handler_document_admin', 'edit'),
    'fixed_args' => array('document', 'edit'),
    'variable_args' => 1,
),
// Match /document/versions/<document GUID>
'document-versions' => array
(
    'handler' => array('org_openpsa_documents_handler_document_view', 'versions'),
    'fixed_args' => array('document', 'versions'),
    'variable_args' => 1,
),
// Match /document/<document GUID>
'document-view' => array
(
    'handler' => array('org_openpsa_documents_handler_document_view', 'view'),
    'fixed_args' => 'document',
    'variable_args' => 1,
),
// Match /directory/navigation
'navigation-show' => array
(
    'handler' => array('org_openpsa_documents_handler_directory_navigation', 'navigation'),
    'fixed_args' => array('directory', 'navigation'),
),
// Match /directory/<mode>/<directory GUID>
'directory-single-view' => array
(
    'handler' => array('org_openpsa_documents_handler_directory_view', 'view'),
    'fixed_args' => 'directory',
    'variable_args' => 2,
),

// Match /edit
'directory-edit' => array
(
    'handler' => array('org_openpsa_documents_handler_directory_edit', 'edit'),
    'fixed_args' => 'edit',
),

// Match /create
'directory-create' => array
(
    'handler' => array('org_openpsa_documents_handler_directory_create', 'create'),
    'fixed_args' => 'create',
),

// Match /search
'search' => array
(
    'handler' => array('org_openpsa_documents_handler_search', 'search'),
    'fixed_args' => 'search',
),

// Match /
'directory-view' => array
(
    'handler' => array('org_openpsa_documents_handler_directory_view', 'view'),
),
