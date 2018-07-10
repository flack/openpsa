// schema to use, must have an entry 'comment', which is used.
'schemadb' => 'file:/net/nehmer/comments/config/schemadb_default.inc',
'schemadb_config' => 'file:/net/nehmer/comments/config/schemadb_config.inc',

// Always protect submission with a captcha
'use_captcha' => false,

// Protect submission with a captcha if anonymous only.
'use_captcha_if_anonymous' => true,

// The captcha config to use
'captcha_config' => [],

// Allow anonymous posting.
'allow_anonymous' => true,

'enable_spam_check' => false,

// Notify authors on new comments
'enable_notify' => true,

// Atom/RSS Comments import
'atom_comments_import_enable' => false,
'atom_comments_topic' => '', // GUID of topic to check Atom comments for (used by cron-script)
'atom_comments_initial_status' => 4, // net_nehmer_comments_comment::NEW_ANONYMOUS

// Rating mode
'ratings_enable' => false,
'ratings_cache_to_object' => false,
'ratings_cache_to_object_property' => 'score',
'ratings_cache_to_object_property_metadata' => false,
'ratings_cache_to_object_use_rcs' => true,

'ratings_cache_total' => false,
'comment_count_cache_to_object' => false,
'comment_count_cache_to_object_property' => '',
'comment_count_cache_to_object_use_rcs' => true,

'items_to_show' => 6, //false == no limit
'item_ordering' => 'ASC', //DESC OR ASC

'paging' => false, // false == disabled or integer == number of results/page

// GUIDs of moderators to notify
'moderators' => '',

'show_reported_abuse_as_normal' => true,
