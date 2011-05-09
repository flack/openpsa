// Match /savefilter
'savefilter' => array
(
    'fixed_args' => 'savefilter',
    'handler' => 'savefilter'
),

// Match /userinfo
'userinfo' => array
(
    'fixed_args' => 'userinfo',
    'handler' => 'userinfo'
),

// Match /updates
'updates' => array
(
    'fixed_args' => 'updates',
    'handler' => 'updates'
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

// Match /config/
'config' => array
(
    'handler' => array('midcom_core_handler_configdm2', 'config'),
    'schemadb' => 'file:/org/openpsa/mypage/config/schemadb_config.inc',
    'schema' => 'config',
    'fixed_args' => array('config'),
),
