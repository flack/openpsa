// Match /document/create
'document-create' => array
(
    'handler' => array('org_openpsa_documents_handler_document_admin', 'create'),
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

// Match /connector/goto
'finder-goto' => array
(
 'handler' => array('org_openpsa_documents_handler_finder', 'goto'),
 'variable_args' => 1,
 'fixed_args' => array('connector', 'goto'),
),

// Match /connector
'finder-connector' => array
(
 'handler' => array('org_openpsa_documents_handler_finder', 'connector'),
 'fixed_args' => 'connector',
),

// Match /
'finder-view' => array
(
    'handler' => array('org_openpsa_documents_handler_finder', 'view'),
),
