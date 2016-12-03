// Match render/<objguid>/<mode>/<sort>
'render_sort' => array
(
    'handler' => array('org_openpsa_relatedto_handler_relatedto', 'render'),
    'fixed_args' => array('render'),
    'variable_args' => 3,
),

// Match render/<objguid>/<mode>
'render' => array
(
    'handler' => array('org_openpsa_relatedto_handler_relatedto', 'render'),
    'fixed_args' => array('render'),
    'variable_args' => 2,
),

// Match delete/<guid>
'delete' => array
(
    'handler' => array('org_openpsa_relatedto_handler_relatedto', 'delete'),
    'fixed_args' => array('delete'),
    'variable_args' => 1,
),

// Match ajax/<mode>/<objguid>
'ajax_object' => array
(
    'handler' => array('org_openpsa_relatedto_handler_relatedto', 'ajax'),
    'fixed_args' => array('ajax'),
    'variable_args' => 2,
),

// Match ajax/<mode>
'ajax' => array
(
    'handler' => array('org_openpsa_relatedto_handler_relatedto', 'ajax'),
    'fixed_args' => array('ajax'),
    'variable_args' => 1,
),
// Match journalentry/list/
'journal_entry_list' => array
(
    'handler' => array('org_openpsa_relatedto_handler_journal_list', 'xml'),
    'fixed_args' => array('journalentry', 'list')
),
// Match journalentry/create/<guid>
'journal_entry_create' => array
(
    'handler' => array('org_openpsa_relatedto_handler_journal_entry', 'create'),
    'fixed_args' => array('journalentry', 'create'),
    'variable_args' => 1,
),
// Match journalentry/edit/<guid>/
'journal_entry_edit' => array
(
    'handler' => array('org_openpsa_relatedto_handler_journal_entry', 'edit'),
    'fixed_args' => array('journalentry', 'edit'),
    'variable_args' => 1,
),
// Match journalentry/delete/<guid>/
'journal_entry_delete' => array
(
    'handler' => array('org_openpsa_relatedto_handler_journal_entry', 'delete'),
    'fixed_args' => array('journalentry', 'delete'),
    'variable_args' => 1,
),
// Match journalentry/<guid>/
'journal_entry' => array
(
    'handler' => array('org_openpsa_relatedto_handler_journal_list', 'object'),
    'fixed_args' => array('journalentry'),
    'variable_args' => 1,
),

// Match rest/journalentry/
'journal_entry_rest' => array(
    'handler' => array('org_openpsa_relatedto_handler_journal_rest', 'process'),
    'fixed_args' => array('rest', 'journalentry'),
),
