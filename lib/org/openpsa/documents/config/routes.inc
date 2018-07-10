// Match /document/create
'document-create' => [
    'handler' => ['org_openpsa_documents_handler_document_admin', 'create'],
    'fixed_args' => ['document', 'create'],
],

// Match /document/delete/<document GUID>
'document-delete' => [
    'handler' => ['org_openpsa_documents_handler_document_admin', 'delete'],
    'fixed_args' => ['document', 'delete'],
    'variable_args' => 1,
],
// Match /document/edit/<document GUID>
'document-edit' => [
    'handler' => ['org_openpsa_documents_handler_document_admin', 'edit'],
    'fixed_args' => ['document', 'edit'],
    'variable_args' => 1,
],
// Match /document/versions/<document GUID>
'document-versions' => [
    'handler' => ['org_openpsa_documents_handler_document_view', 'versions'],
    'fixed_args' => ['document', 'versions'],
    'variable_args' => 1,
],
// Match /document/<document GUID>
'document-view' => [
    'handler' => ['org_openpsa_documents_handler_document_view', 'view'],
    'fixed_args' => 'document',
    'variable_args' => 1,
],
// Match /directory/navigation
'navigation-show' => [
    'handler' => ['org_openpsa_documents_handler_directory_navigation', 'navigation'],
    'fixed_args' => ['directory', 'navigation'],
],

// Match /edit
'directory-edit' => [
    'handler' => ['org_openpsa_documents_handler_directory_edit', 'edit'],
    'fixed_args' => 'edit',
],

// Match /create
'directory-create' => [
    'handler' => ['org_openpsa_documents_handler_directory_create', 'create'],
    'fixed_args' => 'create',
],

// Match /search
'search' => [
    'handler' => ['org_openpsa_documents_handler_search', 'search'],
    'fixed_args' => 'search',
],

// Match /connector/goto
'finder-goto' => [
 'handler' => ['org_openpsa_documents_handler_finder', 'goto'],
 'variable_args' => 1,
 'fixed_args' => ['connector', 'goto'],
],

// Match /connector
'finder-connector' => [
 'handler' => ['org_openpsa_documents_handler_finder', 'connector'],
 'fixed_args' => 'connector',
],

// Match /
'finder-view' => [
    'handler' => ['org_openpsa_documents_handler_finder', 'view'],
],
