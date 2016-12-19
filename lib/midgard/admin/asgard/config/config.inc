'object_preferred_fields' => array
(
    'name',
    'title',
    'official',
    'customerId',
    'firstname',
    'lastname',
    'prefix',
    'port',
    'domain',
),
'object_timerange_fields' => array
(
    'start',
    'end',
    'calstart',
    'caldays',
    'openregistration',
    'closeregistration',
),
'object_address_fields' => array
(
    'street',
    'postcode',
    'city',
    'country',
    'postalStreet',
    'postalPostcode',
    'postalCity',
    'postalCountry',
    'invoiceStreet',
    'invoicePostcode',
    'invoiceCity',
    'invoiceCountry',
),
'object_phone_fields' => array
(
    'phone',
    'handphone',
    'workphone',
    'homephone',
    'fax',
),
'object_location_fields' => array
(
    'latitude',
    'longitude',
    'altitude',
    'bearing',
    'accuracy',
),
'object_skip_fields' => array
(
    'guid',
    'id',
    'password',
),
'schemadb_permissions' => 'file:/midgard/admin/asgard/config/schemadb_permissions.inc',
'schemadb_shell' => 'file:/midgard/admin/asgard/config/schemadb_shell.inc',

// User preferences
'schemadb_preferences' => 'file:/midgard/admin/asgard/config/schemadb_preferences.inc',

// User interface
'tinymce_enabled' => true,
'codemirror_enabled' => true,
'escape_frameset' => false,
'enable_quicklinks' => true,
'edit_mode' => false,               // Enable to enter straight to the edit mode and to skip viewing mmode
'midgard_types_model' => 'exclude', // Either 'exclude' or 'include'
'midgard_types' => '',              // List of types, imploded and wrapped (e.g. |midgard_article|,|midgard_topic|)
'midgard_types_regexp' => '',       // Perl-styled regular expression
'last_visited_size' => 20,
'max_navigation_entries' => 100,

// Welcome page
// List of types we don't list on latest objects
'skip_in_filter' => array
(
    'midgard_parameter',
    'midcom_helper_activitystream_activity',
    'midcom_core_privilege_db',
    'midcom_services_at_entry_db',
),
'attachment_text_types' => array
(
    'text/css'                 => 'cascading style sheet',
    'application/x-javascript' => 'javascript',
    'text/plain'               => 'plain text',
    'text/xml'                 => 'xml',
    'text/html'                => 'html',
),
'allow_unclean_names_for' => array
(
    'midgard_style',
    'midgard_element',
    'midgard_attachment',
    'midgard_parameter',
    'midgard_snippetdir',
    'midgard_snippet',
    'midgard_group',
),

'enable_review_dates' => false,