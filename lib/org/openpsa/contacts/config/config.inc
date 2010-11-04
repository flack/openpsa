'schemadb_group' => 'file:/org/openpsa/contacts/config/schemadb_default_organization.inc',
'schemadb_person'    => 'file:/org/openpsa/contacts/config/schemadb_default_person.inc',
'schemadb_acl' => 'file:/org/openpsa/contacts/config/schemadb_default_acl.inc',
'schemadb_notifications' => 'file:/org/openpsa/contacts/config/schemadb_default_notifications.inc',

'schemadb_config' => 'file:/org/openpsa/contacts/config/schemadb_config.inc',

'password_match_score' => 'file:/org/openpsa/contacts/config/password_match_score.inc',

'person_search_fields' => 'username,firstname,lastname,email',
'organization_search_fields' => 'official,email,name,customerId',
'default_password_lenght' => 8,
'enable_duplicate_search' => false, /* NOTE: These are *very* heavy cron jobs run daily */
'auto_wildcards' => 'end',
'password_max_age_days' => 30,
'max_old_passwords' => 0,
'min_password_length' => 8,
'max_password_length' => 20,
'min_password_score' => 50,
'password_block_timeframe_min' => 5,
'max_password_attempts' => 5,




