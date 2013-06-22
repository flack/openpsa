'admin-welcome' => array
(
    'handler' => array('net_nehmer_comments_handler_admin', 'welcome'),
),
'view-comments' => array
(
    'handler' => array('net_nehmer_comments_handler_view', 'comments'),
    'fixed_args' => array('comment'),
    'variable_args' => 1,
),
'view-comments-nonempty' => array
(
    'handler' => array('net_nehmer_comments_handler_view', 'comments'),
    'fixed_args' => array('comment-nonempty'),
    'variable_args' => 1,
),
'view-comments-custom' => array
(
    'handler' => array('net_nehmer_comments_handler_view', 'comments'),
    'fixed_args' => array('comment'),
    'variable_args' => 2,
),
'config' => array
(
    'handler' => array ('midcom_baseclasses_components_handler_configuration', 'config'),
    'fixed_args' => array ('config'),
),
// Match /moderate/<comment_status_to_moderate>
'moderate' => array
(
    'handler' => array('net_nehmer_comments_handler_admin', 'moderate'),
    'fixed_args' => array('moderate'),
    'variable_args' => 1,
),
// Match /moderate/ajax<comment_status_to_moderate>
'moderate_ajax' => array
(
    'handler' => array('net_nehmer_comments_handler_admin', 'moderate_ajax'),
    'fixed_args' => array('moderate', 'ajax'),
    'variable_args' => 1,
),
// Match /report/<comment guid>
'report' => array
(
    'handler' => array('net_nehmer_comments_handler_moderate', 'report'),
    'fixed_args' => array('report'),
    'variable_args' => 1,
),
