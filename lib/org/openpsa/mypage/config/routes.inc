// Match /updates
'updates' => [
    'handler' => ['org_openpsa_mypage_handler_today', 'updates'],
    'fixed_args' => 'updates',
],

// Match /
'today' => [
    'handler' => ['org_openpsa_mypage_handler_today', 'today'],
],

// Match /day/<date>
'day' => [
    'handler' => ['org_openpsa_mypage_handler_today', 'today'],
    'fixed_args' => ['day'],
    'variable_args' => 1,
],

// Match /weekreview/<date>
'weekreview' => [
    'handler' => ['org_openpsa_mypage_handler_weekreview', 'review'],
    'fixed_args' => ['weekreview'],
    'variable_args' => 1,
],

// Match /weekreview/
'weekreview_redirect' => [
    'handler' => ['org_openpsa_mypage_handler_weekreview', 'redirect'],
    'fixed_args' => ['weekreview'],
],

// Match /workingon/
'workingon' => [
    'handler' => ['org_openpsa_mypage_handler_workingon', 'view'],
    'fixed_args' => ['workingon'],
],
// Match /workingon/set
'workingon_set' => [
    'handler' => ['org_openpsa_mypage_handler_workingon', 'set'],
    'fixed_args' => ['workingon', 'set' ],
],
