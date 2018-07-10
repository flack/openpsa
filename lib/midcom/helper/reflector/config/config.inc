/**
 * reflector configuration
 *
 * All classes must be defined as MgdSchema level names
 */
/*
 Used to replace one classname with another

 Keyed by the type we wish to replace, value is the type we wish to replace to
*/
'class_extends' => [
    'org_openpsa_calendar_event_dba' => 'org_openpsa_event',
    'midgard_person' => 'org_openpsa_person',
    'midgard_group' => 'org_openpsa_organization',
    'org_openpsa_salesproject' => 'org_openpsa_project',
],
/**
 * Define the property to use as 'URL-name' per class, use false for skipping heuristics and not using any property as url-name
 *
 * If class is not defined here heuristics will apply
 */
'name_exceptions' => [
    'org_openpsa_event' => 'extra',
    'midgard_person' => false,
    'org_openpsa_person' => false,
    'midgard_parameter' => false,
    'midcom_core_privilege_db' => false,
    'midcom_core_temporary_object_db' => false,
],
/**
 * Define the property to use as 'title' per class, use false for skipping heuristics and not using any property as title
 *
 * If class is not defined here heuristics will apply
 */
'title_exceptions' => [
    'midgard_topic' => 'extra',
    'midgard_group' => 'official',
    'openpsa_person' => 'lastname',
    'midgard_person' => 'lastname',
    'org_openpsa_person' => 'lastname',
    'org_openpsa_organization' => 'official',
],
// Classes that never should be listed as root class
'root_class_exceptions_notroot' => [
    /* These are core special cases */
    'midgard_parameter',
    'midgard_attachment',
    'midgard_activity',
    /* members should not be root classes */
    'org_openpsa_campaign_member',
    'org_openpsa_role',
    /* These use GUID to link to many parents */
    'net_nemein_tag_link',
    'net_nehmer_comments_comment_db',
    'org_openpsa_relatedto',
    'org_openpsa_relatedto_journal_entry',
    'org_openpsa_billing_data',
    /* These we just don't anybody mucking about with */
    'midcom_core_temporary_object_db',
    'midcom_core_privilege_db',
    'midcom_services_at_entry_db',
    /* And then some objects that just don't make sense to show as roots */
    'net_nemein_tag',
    /* Persons and groups as toplevel is mostly harmful as there can be really many entries */
    'midgard_person',
    'openpsa_person',
    'org_openpsa_person',
    'org_openpsa_contacts_list',
    'midgard_group',
    'org_openpsa_organization',
    /* Replicator subscriptions have their own specific admin UI */
    'midcom_helper_replicator_subscription',
    /* Activities are shown in their own Activity Stream view */
    'midcom_helper_activitystream_activity',
],
// Classes that should always be listed as root class (mainly workaround for schema bugs)
'root_class_exceptions_forceroot' => [
],
/* Classes we do not wish to resolve as children event though strictly speaking they are */
'child_class_exceptions_neverchild' => [
    'midgard_parameter',
    'midgard_attachment',
    'midcom_core_privilege_db'
],

// Always search these fields (used by get_search_properties), keyed by MgdSchema class name
'always_search_fields' => [
    'midgard_person' => ['email'],
    'org_openpsa_person' => ['email'],
],
// Never search these fields (used by get_search_properties), keyed by MgdSchema class name
'never_search_fields' => [
],
// keyed by icon, values are arrays of classes (__default__ marking the default icon) names, used to determine icon to use for object
'object_icon_magic' => [
    'file-o' => [
        '__default__',
    ],
    'folder-o' => [
        'midgard_topic',
        'midgard_snippetdir',
        'midgard_style',
        'org_openpsa_products_product_group',
    ],
    'bullhorn' => [
        'org_openpsa_campaign',
    ],
	'newspaper-o' => [
        'org_openpsa_notifications_notification',
        'net_nemein_rss_feed',
    ],
    'comments' => [
        'net_nehmer_comments_comment_db',
    ],
    'cube' => [
        'org_openpsa_products_product',
    ],
],
// This works just like object_icon_magic
'create_type_magic' => [
    'plus' => [
        '__default__',
    ],
    'folder-o' => [
        'midgard_topic',
        'midgard_snippetdir',
        'midgard_style',
    ],
	'user-o' => [
        'org_openpsa_campaign',
    ],
],

