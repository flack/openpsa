'schemadb_person' => 'file:/midgard/admin/user/config/schemadb_person.php',
'schemadb_account' => 'file:/midgard/admin/user/config/schemadb_account.php',
'schemadb_group' => 'file:/midgard/admin/user/config/schemadb_group.php',
'list_without_search' => 20,
'list_users_max' => 30,
'allow_manage_accounts' => true, // Turn this off if the accounts are integrated with some external source like AD or LDAP
'search_fields' => [
    'firstname',
    'lastname',
    'username',
    'email',
    'city',
    'postcode',
],
'list_fields' => [
    'firstname',
    'lastname',
    'username',
    'email',
    'city',
],
'passwords_max_amount' => 100,
'passwords_max_length' => 16,

'message_subject' => 'your password has been changed',
'message_body' => "Your password has been changed. Your new password is __PASSWORD__",
'message_footer' => "

--
__FROM__ - __LONGDATE__
",
'message_sender' => 'noreply@openpsa2.org',
