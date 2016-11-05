/**
 * What currency this OpenPSA uses internally, might be needed
 * when copying data between different systems
 * Uses the three-letter ISO 4217 currency code
 */
'currency'  => 'EUR',
/**
 * Currency symbol for display
 * if null, defaults to the ISO 4217 currency code
 */
'currency_symbol' => '�',
/**
 * Task/Project Datamanager schema snippet
 */
'schemadb_task' => 'file:/org/openpsa/projects/config/schemadb_default_task.inc',
'schemadb_project' => 'file:/org/openpsa/projects/config/schemadb_default_project.inc',

'default_minimum_time_slot' => 0.25,