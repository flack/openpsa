// Schema settings
'schemadb_account' => 'file:/net/nehmer/account/config/schemadb_account.inc',
'schemadb_account_creation' => 'file:/net/nehmer/account/config/schemadb_account_creation.inc',
'schemadb_socialweb' => 'file:/net/nehmer/account/config/schemadb_socialweb.inc',

// Configuration schema
'schemadb_config' => 'file:/net/nehmer/account/config/schemadb_config.inc',

'list_order' => array
(
    'metadata.score' => 'DESC',
    'lastname' => 'DESC',
),
'list_entries' => 30,

'list_categories' => array
(
    'comments',
    'favourites',
    'blogs',
    'products',
    'discussion',
    'groups',
),

'list_categories_number' => 40,


// Features
'allow_register' => true,
'allow_change_username' => true,
'allow_change_password' => true,
'allow_cancel_membership' => true,
'allow_publish' => true,
'allow_view_by_username' => true, // the paranoid may want to disable this as it allows determining username validity
'allow_socialweb' => true,
'allow_invite' => true,
'allow_view_by_username_only' => false, // this will let you have URLs like /profile/myname. Caution: usernames like 'admin' or 'edit' won't work!
'allow_list' => true,

// Add group id to automatically assign the user to it
'assign_to_group' => null,

'username_is_email' => false,
'register_allow_types' => null,

// Require approval of a new account before activating
'require_activation' => false,
'administrator_email' => '',

// This option is not yet completely implemented.
'fallback_type' => null, 

'password_minlength' => 6,
'on_cancel_membership' => null,
// 'on_cancel_membership' => Array
// (
//     'callback' => 'callback_function_name',
//     'autoload_snippet' => 'snippet_name', // optional
//     'autoload_file' => 'filename', // optional
// ),
'on_activate_account' => null,
// 'on_activate_account' => Array
// (
//     'callback' => 'callback_function_name',
//     'autoload_snippet' => 'snippet_name', // optional
//     'autoload_file' => 'filename', // optional
// ),
'account_activation_timeout' => 7, // days
'publish_all_on_activation' => false,

'relocate_after_activation' => false,

// Avatar Sizes
'avatar_x' => 190,
'avatar_y' => 190,
'avatar_thumbnail_x' => 50,
'avatar_thumbnail_y' => 50,

// Onsite Welcome Mail
'have_net_nehmer_mail' => false,
'welcome_mail_subject' => 'welcome_mail_subject', // Will be localized
'welcome_mail_body' => 'welcome_mail_body', // Will be localized
'welcome_mail_sender' => '', // Sender and receiver is identical, put in a guid otherwise.


/**
 * These configuration keys are linked to sending email to clients. Localization can be
 * done either from locale/ or by changing the configuration values
 */
'pending_mail_subject' => 'registration pending for approval',
'pending_mail_body' => 'pending_mail_body',

// Activaton / Password Emails
'activation_mail_sender' => null, // Means sender == receiver
'activation_mail_subject' => 'activation_mail_subject', // Will be localized
'activation_mail_body' => 'activation_mail_body', // Will be localized

// Approval mail, sent to the administrators
'approval_mail_subject' => 'approval_mail_subject',
'approval_mail_body' => 'approval_mail_body',

// Mail sent after disapproval
'rejected_mail_subject' => 'disapproval_mail_subject',
'rejected_mail_body' => 'disapproval_mail_body',

// Resetting the password
'password_reset_mail_sender' => null, // Means sender == receiver
'password_reset_mail_subject' => 'password_reset_mail_subject', // Will be localized
'password_reset_mail_body' => 'password_reset_mail_body', // Will be localized

'lost_password_reset_mail_subject' => 'lost_password_reset_mail_subject',
'lost_password_reset_mail_body' => 'lost_password_reset_mail_body',

/**
 * Plugins and other miscellaneous configuration keys
 */
// see the docs of the viewer/admin classes for details about the plugin handlers
'plugins' => array
(
    'notifications' => Array
    (
        'class' => 'org_openpsa_notifications_preferences',
        'src' => 'file:/org/openpsa/notifications/preferences.php',
        'name' => 'Notification preferences',
        'config' => null,
    ),
),
'admin_plugins' => null,

/**
 * How many invite email fields should be shown
 */
'email_fields' => 5,

/**
 * Invite email subject
 */
'invitation_mail_sender' => 'noreply',
'invitation_mail_subject' => 'website invitation from %s',

/**
 * Keep sent invites for x days
 */
'keep_sent_invites' => 30,

/**
 * The schema to use for invitation registration
 */
'invreg_schema' => 'account',

'auto_login_on_activation' => false,

'override_registration_steps' => array
(
    'register/activate' => 'invite',
    'invite' => 'register/finish',
),

'net_nehmer_buddylist_integration' => false,

'show_lost_password_in_navi' => true,

'redirect_edit_to' => 'view',

/**
 * Use optional way to handle reset password request (sends email to the user before resetting the password)
 */
'lostpassword_email_reset' => false,

/**
 * Karma calculation properties
 */
'karma_enable' => false,
'karma_comments_enable' => true,
'karma_comments_modifier' => 2,
'karma_favourites_enable' => true,
'karma_favourites_modifier' => 1,
'karma_buries_enable' => true,
'karma_buries_modifier' => 1,
'karma_packagetesting_enable' => true,
'karma_packagetesting_modifier' => 1,
'karma_wikicreates_enable' => true,
'karma_wikicreates_modifier' => 4,
'karma_wikiedits_enable' => false, // Note: This is slow as it uses RCS subsystem
'karma_wikiedits_modifier' => 2,
'karma_blogs_enable' => true,
'karma_blogs_modifier' => 1,
'karma_socialnews_enable' => false,
'karma_products_enable' => true,
'karma_products_modifier' => 4,
'karma_productratings_enable' => true,
'karma_brainstorm_enable' => true,
'karma_brainstorm_modifier' => 1,
'karma_discussion_enable' => true,
'karma_discussion_modifier' => 1,
'karma_groups_enable' => true,
'karma_groups_modifier' => 4,
'karma_calculate_per_hour' => 100,

/**
 * Karma plugins as array containing key, snippet path and function name
 *
 * The functions called will receive a person as parameter and must return an integer
 */
'karma_plugins' => array
(
    //'bugzilla' => array
    //(
    //    'snippet' => '/scripts/bugzilla_karma',
    //    'function' => 'calculate_person_bugs', // Or array('someclass', 'somemethod') for static methods
    //),
),

// compatible only with net.nemein.openidprovider for now
// absolute URL is required
'openidprovider_link' => null,
