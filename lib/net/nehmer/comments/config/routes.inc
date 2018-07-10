'admin-welcome' => [
    'handler' => ['net_nehmer_comments_handler_admin', 'welcome'],
],
'view-comments' => [
    'handler' => ['net_nehmer_comments_handler_view', 'comments'],
    'fixed_args' => ['comment'],
    'variable_args' => 1,
],
'view-comments-nonempty' => [
    'handler' => ['net_nehmer_comments_handler_view', 'comments'],
    'fixed_args' => ['comment-nonempty'],
    'variable_args' => 1,
],
'view-comments-custom' => [
    'handler' => ['net_nehmer_comments_handler_view', 'comments'],
    'fixed_args' => ['comment'],
    'variable_args' => 2,
],
'config' => [
    'handler' => ['midcom_baseclasses_components_handler_configuration', 'config'],
    'fixed_args' => ['config'],
],
// Match /moderate/<comment_status_to_moderate>
'moderate' => [
    'handler' => ['net_nehmer_comments_handler_admin', 'moderate'],
    'fixed_args' => ['moderate'],
    'variable_args' => 1,
],
// Match /moderate/ajax<comment_status_to_moderate>
'moderate_ajax' => [
    'handler' => ['net_nehmer_comments_handler_admin', 'moderate_ajax'],
    'fixed_args' => ['moderate', 'ajax'],
    'variable_args' => 1,
],
// Match /report/abuse/<comment guid>
'report_abuse' => [
    'handler' => ['net_nehmer_comments_handler_moderate', 'abuse'],
    'fixed_args' => ['report', 'abuse'],
    'variable_args' => 1,
],
// Match /report/confirm_abuse/<comment guid>
'report_confirm_abuse' => [
    'handler' => ['net_nehmer_comments_handler_moderate', 'confirm_abuse'],
    'fixed_args' => ['report', 'confirm_abuse'],
    'variable_args' => 1,
],
// Match /report/not_abuse/<comment guid>
'report_not_abuse' => [
    'handler' => ['net_nehmer_comments_handler_moderate', 'not_abuse'],
    'fixed_args' => ['report', 'not_abuse'],
    'variable_args' => 1,
],
// Match /report/confirm_junk/<comment guid>
'report_confirm_junk' => [
    'handler' => ['net_nehmer_comments_handler_moderate', 'confirm_junk'],
    'fixed_args' => ['report', 'confirm_junk'],
    'variable_args' => 1,
],
