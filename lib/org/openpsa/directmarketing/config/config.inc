'schemadb_config' => 'file:/org/openpsa/directmarketing/config/schemadb_config.inc',
'schemadb_campaign'  => 'file:/org/openpsa/directmarketing/config/schemadb_default_campaign.php',
'schemadb_message'   => 'file:/org/openpsa/directmarketing/config/schemadb_default_message.php',
'schemadb_message_copy'   => 'file:/org/openpsa/directmarketing/config/schemadb_default_message_copy.php',
'schemadb_campaign_member' => 'file:/org/openpsa/directmarketing/config/schemadb_default_campaign_member.php',
'schemadb_person'    => 'file:/org/openpsa/contacts/config/schemadb_default_person.php',
'schemadb_organization' => 'file:/org/openpsa/contacts/config/schemadb_default_organization.php',
'schemadb_organization_member' => 'file:/org/openpsa/contacts/config/schemadb_default_member.php',
'send_token_max_age' => 45, //Tokens older than this many days are cleared from database, use zero for infinite (not recommended)
'csv_export_memberships' => 'first', // Valid values 'first', 'last' and 'all', controls what membership/group data is exported, experiment.
'csv_import_check_duplicates' => true,
'navi_order' => 'ASC',
'campaign_list_order' => 'DESC',

'delete_older_than_days' => 30,
'delete_older' => false,
'delete_older_per_run' => 1000,

'chunk_size' => 50,
'token_size' => 15