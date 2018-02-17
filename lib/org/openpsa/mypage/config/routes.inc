// Match /updates
'updates' => array
(
    'handler' => array('org_openpsa_mypage_handler_today', 'updates'),
    'fixed_args' => 'updates',
),

// Match /
'today' => array
(
    'handler' => array('org_openpsa_mypage_handler_today', 'today'),
),

// Match /day/<date>
'day' => array
(
    'handler' => array('org_openpsa_mypage_handler_today', 'today'),
    'fixed_args' => array('day'),
    'variable_args' => 1,
),

// Match /weekreview/<date>
'weekreview' => array
(
    'handler' => array('org_openpsa_mypage_handler_weekreview', 'review'),
    'fixed_args' => array('weekreview'),
    'variable_args' => 1,
),

// Match /weekreview/
'weekreview_redirect' => array
(
    'handler' => array('org_openpsa_mypage_handler_weekreview', 'redirect'),
    'fixed_args' => array('weekreview'),
),

// Match /workingon/
'workingon' => array
(
    'handler' => array('org_openpsa_mypage_handler_workingon', 'view'),
    'fixed_args' => array('workingon'),
),
// Match /workingon/set
'workingon_set' => array
(
    'handler' => array('org_openpsa_mypage_handler_workingon', 'set'),
    'fixed_args' => array('workingon', 'set' ),
),
