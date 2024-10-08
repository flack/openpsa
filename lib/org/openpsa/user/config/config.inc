'schemadb_person' => 'file:/org/openpsa/user/config/schemadb_person.php',
'schemadb_account' => 'file:/org/openpsa/user/config/schemadb_account.php',
'schemadb_account_edit' => 'file:/org/openpsa/user/config/schemadb_account_edit.php',
'schemadb_lostpassword' => 'file:/org/openpsa/user/config/schemadb_lostpassword.php',

'schemadb_group' => 'file:/org/openpsa/user/config/schemadb_group.php',

'schemadb_acl' => 'file:/org/openpsa/user/config/schemadb_default_acl.php',

//Set 'none' to disable lostpassword functionality
'lostpassword_mode' => 'lostpassword_by_username',
'lostpassword_reset_mail_sender' => null, // Means sender == receiver
'lostpassword_reset_mail_subject' => 'Your password has been reset',
'lostpassword_reset_mail_body' => '
Hello,

Your password has been reset.

You can now log in with your normal username and the following password:

__PASSWORD__',

'password_max_age_days' => 30,
'max_old_passwords' => 3,
'min_password_length' => 8,
'min_password_score' => 35,
'unique_character_score' => 4,
'password_block_timeframe_min' => 5,
'max_password_attempts' => 5,

'welcome_mail_title' => 'Welcome!',
'welcome_mail_body' =>
'Hello,

your account has been created successfully.

You can now log in to your account using the following credentials:
Username: __USERNAME__
Password: __PASSWORD__',

'welcome_mail_from_address' => 'noreply@openpsa2.org',

'password_score_rules' => [
    [
        'match' => '/(.*[0-9].*[0-9].*[0-9])/',
        'score' => 10,
    ],
    [
        'match' => '/(.*[!,@,#,$,%,^,&,*,?,_,~].*[!,@,#,$,%,^,&,*,?,_,~])/',
        'score' => 10,
    ],
    [
        'match' => '/(.*[a-z].*[A-Z])|(.*[A-Z].*[a-z])/',
        'score' => 10,
    ],
    [
        'match' => '/(.*[a-zA-Z].*[0-9])|(.*[0-9].*[a-zA-Z])/',
        'score' => 10,
    ],
    [
        'match' => '/(.*[!,@,#,$,%,^,&,*,?,_,~])/',
        'score' => 10,
    ],
    [
        'match' => '/(.*[!,@,#,$,%,^,&,*,?,_,~].*[0-9])|(.*[0-9].*[!,@,#,$,%,^,&,*,?,_,~])/',
        'score' => 10,
    ],
    [
        'match' => '/^\w+$/',
        'score' => -25,
    ],
    [
        'match' => '/^\d+$/',
        'score' => -25,
    ],
],
