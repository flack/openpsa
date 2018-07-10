// Match render/<objguid>/<mode>/<sort>
'render_sort' => [
    'handler' => ['org_openpsa_relatedto_handler_relatedto', 'render'],
    'fixed_args' => ['render'],
    'variable_args' => 3,
],

// Match render/<objguid>/<mode>
'render' => [
    'handler' => ['org_openpsa_relatedto_handler_relatedto', 'render'],
    'fixed_args' => ['render'],
    'variable_args' => 2,
],

// Match delete/<guid>
'delete' => [
    'handler' => ['org_openpsa_relatedto_handler_relatedto', 'delete'],
    'fixed_args' => ['delete'],
    'variable_args' => 1,
],

// Match ajax/<mode>/<objguid>
'ajax_object' => [
    'handler' => ['org_openpsa_relatedto_handler_relatedto', 'ajax'],
    'fixed_args' => ['ajax'],
    'variable_args' => 2,
],

// Match ajax/<mode>
'ajax' => [
    'handler' => ['org_openpsa_relatedto_handler_relatedto', 'ajax'],
    'fixed_args' => ['ajax'],
    'variable_args' => 1,
],
// Match journalentry/list/<time>
'journal_entry_list' => [
    'handler' => ['org_openpsa_relatedto_handler_journal_list', 'list'],
    'fixed_args' => ['journalentry', 'list'],
    'variable_args' => 1
],
// Match journalentry/xml/
'journal_entry_xml' => [
    'handler' => ['org_openpsa_relatedto_handler_journal_list', 'xml'],
    'fixed_args' => ['journalentry', 'xml']
],
// Match journalentry/create/<guid>
'journal_entry_create' => [
    'handler' => ['org_openpsa_relatedto_handler_journal_entry', 'create'],
    'fixed_args' => ['journalentry', 'create'],
    'variable_args' => 1,
],
// Match journalentry/edit/<guid>/
'journal_entry_edit' => [
    'handler' => ['org_openpsa_relatedto_handler_journal_entry', 'edit'],
    'fixed_args' => ['journalentry', 'edit'],
    'variable_args' => 1,
],
// Match journalentry/delete/<guid>/
'journal_entry_delete' => [
    'handler' => ['org_openpsa_relatedto_handler_journal_entry', 'delete'],
    'fixed_args' => ['journalentry', 'delete'],
    'variable_args' => 1,
],
// Match journalentry/<guid>/
'journal_entry' => [
    'handler' => ['org_openpsa_relatedto_handler_journal_list', 'object'],
    'fixed_args' => ['journalentry'],
    'variable_args' => 1,
],

// Match rest/journalentry/
'journal_entry_rest' => [
    'handler' => ['org_openpsa_relatedto_handler_journal_rest', 'process'],
    'fixed_args' => ['rest', 'journalentry'],
],
