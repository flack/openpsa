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
// Match /report/abuse/<comment guid>
'report_abuse' => array
(
    'handler' => array('net_nehmer_comments_handler_moderate', 'abuse'),
    'fixed_args' => array('report', 'abuse'),
    'variable_args' => 1,
),
// Match /report/confirm_abuse/<comment guid>
'report_confirm_abuse' => array
(
    'handler' => array('net_nehmer_comments_handler_moderate', 'confirm_abuse'),
    'fixed_args' => array('report', 'confirm_abuse'),
    'variable_args' => 1,
),
// Match /report/not_abuse/<comment guid>
'report_not_abuse' => array
(
    'handler' => array('net_nehmer_comments_handler_moderate', 'not_abuse'),
    'fixed_args' => array('report', 'not_abuse'),
    'variable_args' => 1,
),
// Match /report/confirm_junk/<comment guid>
'report_confirm_junk' => array
(
    'handler' => array('net_nehmer_comments_handler_moderate', 'confirm_junk'),
    'fixed_args' => array('report', 'confirm_junk'),
    'variable_args' => 1,
),
