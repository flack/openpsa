// Match /campaign/import/<guid>
'import_main' => [
    'fixed_args' => ['campaign', 'import'],
    'variable_args' => 1,
    'handler' => ['org_openpsa_directmarketing_handler_import', 'index'],
],

// Match /campaign/import/simpleemails/<guid>
'import_simpleemails' => [
    'fixed_args' => ['campaign', 'import', 'simpleemails'],
    'variable_args' => 1,
    'handler' => ['org_openpsa_directmarketing_handler_import', 'simpleemails'],
],

// Match /campaign/import/csv/<guid>
'import_csv_file_select' => [
    'fixed_args' => ['campaign', 'import', 'csv'],
    'variable_args' => 1,
    'handler' => ['org_openpsa_directmarketing_handler_import', 'csv_select'],
],

// Match /campaign/import/csv2/<guid>
'import_csv_field_select' => [
    'fixed_args' => ['campaign', 'import', 'csv2'],
    'variable_args' => 1,
    'handler' => ['org_openpsa_directmarketing_handler_import', 'csv'],
],

// Match /campaign/import/vcards/<guid>
'import_vcards' => [
    'fixed_args' => ['campaign', 'import', 'vcards'],
    'variable_args' => 1,
    'handler' => ['org_openpsa_directmarketing_handler_import', 'vcards'],
],

// Match /campaign/export/csv/<guid>/
'export_csv' => [
    'fixed_args' => ['campaign', 'export', 'csv'],
    'variable_args' => 1,
    'handler' => ['org_openpsa_directmarketing_handler_export', 'csv'],
],

// Handle /message/create/<campaign guid>/<schema>
'create_message' => [
    'handler' => ['org_openpsa_directmarketing_handler_message_create', 'create'],
    'fixed_args' => ['message', 'create'],
    'variable_args' => 2,
],

// Match /message/list/<type>/<guid>
'message_list_dynamic_type' => [
    'fixed_args' => ['message','list'],
    'variable_args' => 2,
    'handler' => ['org_openpsa_directmarketing_handler_message_list', 'list'],
],

// Match /message/compose/<message_guid>/<person_guid>
'compose4person' => [
    'fixed_args' => ['message', 'compose'],
    'variable_args' => 2,
    'handler' => ['org_openpsa_directmarketing_handler_message_compose', 'compose'],
],

// Match /message/compose/<message_guid>
'compose' => [
    'fixed_args' => ['message', 'compose'],
    'variable_args' => 1,
    'handler' => ['org_openpsa_directmarketing_handler_message_compose', 'compose'],
],

// Match /message/send_bg/<message GUID>/<batch number>/<job guid>
'background_send_message' => [
    'fixed_args' => ['message', 'send_bg'],
    'variable_args' => 3,
    'handler' => ['org_openpsa_directmarketing_handler_message_send', 'send_bg'],
],

// Match /message/send/<message GUID>
'send_message' => [
    'fixed_args' => ['message', 'send'],
    'variable_args' => 1,
    'handler' => ['org_openpsa_directmarketing_handler_message_send', 'send'],
],

// Match /message/send_test/<message GUID>
'test_send_message' => [
    'fixed_args' => ['message', 'send_test'],
    'variable_args' => 1,
    'handler' => ['org_openpsa_directmarketing_handler_message_send', 'send'],
],

// Match /message/send_status/<message GUID>
'message_send_status' => [
    'fixed_args' => ['message', 'send_status'],
    'variable_args' => 1,
    'handler' => ['org_openpsa_directmarketing_handler_message_report', 'status'],
],

// Match /message/report/<message GUID>
'message_report' => [
    'fixed_args' => ['message', 'report'],
    'variable_args' => 1,
    'handler' => ['org_openpsa_directmarketing_handler_message_report', 'report'],
],

// Match /message/<message GUID>
'message_view' => [
    'fixed_args' => 'message',
    'variable_args' => 1,
    'handler' => ['org_openpsa_directmarketing_handler_message_message', 'view'],
],

// Match /message/edit/<message GUID>
'message_edit' => [
    'fixed_args' => ['message', 'edit'],
    'variable_args' => 1,
    'handler' => ['org_openpsa_directmarketing_handler_message_admin', 'edit'],
],

// Match /message/copy/<message GUID>
'message_copy' => [
    'fixed_args' => ['message', 'copy'],
    'variable_args' => 1,
    'handler' => ['org_openpsa_directmarketing_handler_message_admin', 'copy'],
],

// Match /message/delete/<message GUID>
'message_delete' => [
    'fixed_args' => ['message', 'delete'],
    'variable_args' => 1,
    'handler' => ['org_openpsa_directmarketing_handler_message_admin', 'delete'],
],

// Handle /campaign/create/<schema>
'create_campaign' => [
    'handler' => ['org_openpsa_directmarketing_handler_campaign_create', 'create'],
    'fixed_args' => ['campaign', 'create'],
    'variable_args' => 1,
],

// Match /campaign/list/<person GUID>
'list_campaign_person' => [
    'fixed_args' => ['campaign','list'],
    'handler' => ['org_openpsa_directmarketing_handler_subscriber', 'list'],
    'variable_args' => 1,
],

// Match /campaign/unsubscribe/<member GUID>
'subscriber_unsubscribe' => [
    'fixed_args' => ['campaign','unsubscribe'],
    'handler' => ['org_openpsa_directmarketing_handler_subscriber', 'unsubscribe'],
    'variable_args' => 1,
],

// Match /campaign/unsubscribe/ajax/<membership GUID>
'subscriber_unsubscribe_ajax' => [
    'fixed_args' => ['campaign','unsubscribe', 'ajax'],
    'handler' => ['org_openpsa_directmarketing_handler_subscriber', 'unsubscribe_ajax'],
    'variable_args' => 1,
],

// Match /campaign/unsubscribe_all/<person GUID>
'subscriber_unsubscribe_all' => [
    'fixed_args' => ['campaign','unsubscribe_all'],
    'handler' => ['org_openpsa_directmarketing_handler_subscriber', 'unsubscribe_all'],
    'variable_args' => 1,
],

// Match /campaign/unsubscribe_all_future/<person GUID>/type
'subscriber_unsubscribe_all_future' => [
    'fixed_args' => ['campaign','unsubscribe_all_future'],
    'handler' => ['org_openpsa_directmarketing_handler_subscriber', 'unsubscribe_all'],
    'variable_args' => 2,
],

// Match /campaign/edit_query/<campaign GUID>
'edit_campaign_query' => [
    'handler' => ['org_openpsa_directmarketing_handler_campaign_rules', 'edit_query'],
    'fixed_args' => ['campaign', 'edit_query'],
    'variable_args' => 1,
],

// Match /campaign/query/<campaign GUID>
'campaign_query' => [
    'handler' => ['org_openpsa_directmarketing_handler_campaign_rules', 'query'],
    'fixed_args' => ['campaign', 'query'],
    'variable_args' => 1,
],

// Match /campaign/edit/<campaign GUID>
'edit_campaign' => [
    'handler' => ['org_openpsa_directmarketing_handler_campaign_admin', 'edit'],
    'fixed_args' => ['campaign', 'edit'],
    'variable_args' => 1,
],

// Match /campaign/delete/<campaign GUID>
'delete_campaign' => [
    'handler' => ['org_openpsa_directmarketing_handler_campaign_admin', 'delete'],
    'fixed_args' => ['campaign', 'delete'],
    'variable_args' => 1,
],

// Match /campaign/members/<campaign GUID>
'campaign_list_members' => [
    'handler' => ['org_openpsa_directmarketing_handler_campaign_campaign', 'members'],
    'fixed_args' => ['campaign', 'members'],
    'variable_args' => 1,
],

// Match /campaign/<campaign GUID>
'view_campaign' => [
    'handler' => ['org_openpsa_directmarketing_handler_campaign_campaign', 'view'],
    'fixed_args' => ['campaign'],
    'variable_args' => 1,
],

// Match /logger/bounce
'log_bounce' => [
    'fixed_args' => ['logger', 'bounce'],
    'handler' => ['org_openpsa_directmarketing_handler_logger', 'bounce'],
],

// Match /logger/link
'log_link' => [
    'fixed_args' => ['logger', 'link'],
    'handler' => ['org_openpsa_directmarketing_handler_logger', 'link'],
],

// Match /logger/redirect/<TOKEN>/<URL>
'log_redirect_byurl' => [
    'fixed_args' => ['logger', 'redirect'],
    'variable_args' => 2,
    'handler' => ['org_openpsa_directmarketing_handler_logger', 'redirect'],
],

// Match /logger/redirect/<TOKEN>
'log_redirect' => [
    'fixed_args' => ['logger', 'redirect'],
    'variable_args' => 1,
    'handler' => ['org_openpsa_directmarketing_handler_logger', 'redirect'],
],

'config' => [
    'handler' => ['midcom_baseclasses_components_handler_configuration', 'config'],
    'fixed_args' => ['config'],
],

// Match /
'frontpage' => [
    'handler' => ['org_openpsa_directmarketing_handler_list', 'frontpage'],
],
