// Match /campaign/import/<guid>
'import_main' => array
(
    'fixed_args' => array('campaign', 'import'),
    'variable_args' => 1,
    'handler' => array('org_openpsa_directmarketing_handler_import', 'index'),
),

// Match /campaign/import/simpleemails/<guid>
'import_simpleemails' => array
(
    'fixed_args' => array('campaign', 'import', 'simpleemails'),
    'variable_args' => 1,
    'handler' => array('org_openpsa_directmarketing_handler_import', 'simpleemails'),
),

// Match /campaign/import/csv/<guid>
'import_csv_file_select' => array
(
    'fixed_args' => array('campaign', 'import', 'csv'),
    'variable_args' => 1,
    'handler' => array('org_openpsa_directmarketing_handler_import', 'csv_select'),
),

// Match /campaign/import/csv2/<guid>
'import_csv_field_select' => array
(
    'fixed_args' => array('campaign', 'import', 'csv2'),
    'variable_args' => 1,
    'handler' => array('org_openpsa_directmarketing_handler_import', 'csv'),
),

// Match /campaign/import/vcards/<guid>
'import_vcards' => array
(
    'fixed_args' => array('campaign', 'import', 'vcards'),
    'variable_args' => 1,
    'handler' => array('org_openpsa_directmarketing_handler_import', 'vcards'),
),

// Match /campaign/export/csv/<guid>/
'export_csv' => array
(
    'fixed_args' => array('campaign', 'export', 'csv'),
    'variable_args' => 1,
    'handler' => array('org_openpsa_directmarketing_handler_export', 'csv'),
),

// Handle /message/create/<campaign guid>/<schema>
'create_message' => array
(
    'handler' => array('org_openpsa_directmarketing_handler_message_create', 'create'),
    'fixed_args' => array('message', 'create'),
    'variable_args' => 2,
),

// Match /message/list/<type>/<guid>
'message_list_dynamic_type' => array
(
    'fixed_args' => array('message','list'),
    'variable_args' => 2,
    'handler' => array('org_openpsa_directmarketing_handler_message_list', 'list'),
),

// Match /message/compose/<message_guid>/<person_guid>
'compose4person' => array
(
    'fixed_args' => array('message', 'compose'),
    'variable_args' => 2,
    'handler' => array('org_openpsa_directmarketing_handler_message_compose', 'compose'),
),

// Match /message/compose/<message_guid>
'compose' => array
(
    'fixed_args' => array('message', 'compose'),
    'variable_args' => 1,
    'handler' => array('org_openpsa_directmarketing_handler_message_compose', 'compose'),
),

// Match /message/send_bg/<message GUID>/<batch number>/<job guid>
'background_send_message' => array
(
    'fixed_args' => array('message', 'send_bg'),
    'variable_args' => 3,
    'handler' => array('org_openpsa_directmarketing_handler_message_send', 'send_bg'),
),

// Match /message/send/<message GUID>
'send_message' => array
(
    'fixed_args' => array('message', 'send'),
    'variable_args' => 1,
    'handler' => array('org_openpsa_directmarketing_handler_message_send', 'send'),
),

// Match /message/send_test/<message GUID>
'test_send_message' => array
(
    'fixed_args' => array('message', 'send_test'),
    'variable_args' => 1,
    'handler' => array('org_openpsa_directmarketing_handler_message_send', 'send'),
),

// Match /message/send_status/<message GUID>
'message_send_status' => array
(
    'fixed_args' => array('message', 'send_status'),
    'variable_args' => 1,
    'handler' => array('org_openpsa_directmarketing_handler_message_report', 'status'),
),

// Match /message/report/<message GUID>
'message_report' => array
(
    'fixed_args' => array('message', 'report'),
    'variable_args' => 1,
    'handler' => array('org_openpsa_directmarketing_handler_message_report', 'report'),
),

// Match /message/<message GUID>
'message_view' => array
(
    'fixed_args' => 'message',
    'variable_args' => 1,
    'handler' => array('org_openpsa_directmarketing_handler_message_message', 'view'),
),

// Match /message/edit/<message GUID>
'message_edit' => array
(
    'fixed_args' => array('message', 'edit'),
    'variable_args' => 1,
    'handler' => array('org_openpsa_directmarketing_handler_message_admin', 'edit'),
),

// Match /message/copy/<message GUID>
'message_copy' => array
(
    'fixed_args' => array('message', 'copy'),
    'variable_args' => 1,
    'handler' => array('org_openpsa_directmarketing_handler_message_admin', 'copy'),
),

// Match /message/delete/<message GUID>
'message_delete' => array
(
    'fixed_args' => array('message', 'delete'),
    'variable_args' => 1,
    'handler' => array('org_openpsa_directmarketing_handler_message_admin', 'delete'),
),

// Handle /campaign/create/<schema>
'create_campaign' => array
(
    'handler' => array('org_openpsa_directmarketing_handler_campaign_create', 'create'),
    'fixed_args' => array('campaign', 'create'),
    'variable_args' => 1,
),

// Match /campaign/list/<person GUID>
'list_campaign_person' => array
(
    'fixed_args' => array('campaign','list'),
    'handler' => array('org_openpsa_directmarketing_handler_subscriber', 'list'),
    'variable_args' => 1,
),

// Match /campaign/unsubscribe/<member GUID>
'subscriber_unsubscribe' => array
(
    'fixed_args' => array('campaign','unsubscribe'),
    'handler' => array('org_openpsa_directmarketing_handler_subscriber', 'unsubscribe'),
    'variable_args' => 1,
),

// Match /campaign/unsubscribe/ajax/<membership GUID>
'subscriber_unsubscribe_ajax' => array
(
    'fixed_args' => array('campaign','unsubscribe', 'ajax'),
    'handler' => array('org_openpsa_directmarketing_handler_subscriber', 'unsubscribe_ajax'),
    'variable_args' => 1,
),

// Match /campaign/unsubscribe_all/<person GUID>
'subscriber_unsubscribe_all' => array
(
    'fixed_args' => array('campaign','unsubscribe_all'),
    'handler' => array('org_openpsa_directmarketing_handler_subscriber', 'unsubscribe_all'),
    'variable_args' => 1,
),

// Match /campaign/unsubscribe_all_future/<person GUID>/type
'subscriber_unsubscribe_all_future' => array
(
    'fixed_args' => array('campaign','unsubscribe_all_future'),
    'handler' => array('org_openpsa_directmarketing_handler_subscriber', 'unsubscribe_all'),
    'variable_args' => 2,
),

// Match /campaign/edit_query/<campaign GUID>
'edit_campaign_query' => array
(
    'handler' => array('org_openpsa_directmarketing_handler_campaign_rules', 'edit_query'),
    'fixed_args' => array('campaign', 'edit_query'),
    'variable_args' => 1,
),

// Match /campaign/query/<campaign GUID>
'campaign_query' => array
(
    'handler' => array('org_openpsa_directmarketing_handler_campaign_rules', 'query'),
    'fixed_args' => array('campaign', 'query'),
    'variable_args' => 1,
),

// Match /campaign/edit/<campaign GUID>
'edit_campaign' => array
(
    'handler' => array('org_openpsa_directmarketing_handler_campaign_admin', 'edit'),
    'fixed_args' => array('campaign', 'edit'),
    'variable_args' => 1,
),

// Match /campaign/delete/<campaign GUID>
'delete_campaign' => array
(
    'handler' => array('org_openpsa_directmarketing_handler_campaign_admin', 'delete'),
    'fixed_args' => array('campaign', 'delete'),
    'variable_args' => 1,
),

// Match /campaign/members/<campaign GUID>
'campaign_list_members' => array
(
    'handler' => array('org_openpsa_directmarketing_handler_campaign_campaign', 'members'),
    'fixed_args' => array('campaign', 'members'),
    'variable_args' => 1,
),

// Match /campaign/<campaign GUID>
'view_campaign' => array
(
    'handler' => array('org_openpsa_directmarketing_handler_campaign_campaign', 'view'),
    'fixed_args' => array('campaign'),
    'variable_args' => 1,
),

// Match /logger/bounce
'log_bounce' => array
(
    'fixed_args' => array ('logger', 'bounce'),
    'handler' => array('org_openpsa_directmarketing_handler_logger', 'bounce'),
),

// Match /logger/link
'log_link' => array
(
    'fixed_args' => array ('logger', 'link'),
    'handler' => array('org_openpsa_directmarketing_handler_logger', 'link'),
),

// Match /logger/redirect/<TOKEN>/<URL>
'log_redirect_byurl' => array
(
    'fixed_args' => array ('logger', 'redirect'),
    'variable_args' => 2,
    'handler' => array('org_openpsa_directmarketing_handler_logger', 'redirect'),
),

// Match /logger/redirect/<TOKEN>
'log_redirect' => array
(
    'fixed_args' => array ('logger', 'redirect'),
    'variable_args' => 1,
    'handler' => array('org_openpsa_directmarketing_handler_logger', 'redirect'),
),

'config' => array
(
    'handler' => Array('midcom_baseclasses_components_handler_configuration', 'config'),
    'fixed_args' => array('config'),
),

// Match /
'frontpage' => array
(
    'handler' => array('org_openpsa_directmarketing_handler_list', 'frontpage'),
),
