// Match /
'index' => array
(
    'handler' => Array('org_openpsa_interviews_handler_index', 'index'),
),

// Match /campaign/<campaign>
'campaign' => array
(
    'fixed_args' => 'campaign',
    'variable_args' => 1,
    'handler' => Array('org_openpsa_interviews_handler_campaign', 'summary'),
),

// Match /next/<campaign>
'next' => array
(
    'fixed_args' => 'next',
    'variable_args' => 1,
    'handler' => Array('org_openpsa_interviews_handler_campaign', 'next'),
),

// Match /interview/<member>
'interview' => array
(
    'fixed_args' => 'interview',
    'variable_args' => 1,
    'handler' => Array('org_openpsa_interviews_handler_interview', 'interview'),
),

// Match /report/all/<campaign>
'report' => array
(
    'fixed_args' => Array('report', 'all'),
    'variable_args' => 1,
    'handler' => Array('org_openpsa_interviews_handler_report', 'all'),
),
