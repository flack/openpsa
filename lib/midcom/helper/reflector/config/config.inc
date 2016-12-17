/**
 * reflector configuration
 *
 * All classes must be defined as MgdSchema level names
 */
/*
 Used to replace one classname with another, for example org_openpsa_event
 extends midgard_event, so we wish to display only the more complete type.

 Keyed by the type we wish to replace, value is the type we wish to replace to
*/
'class_extends' => array
(
    'midgard_event' => 'org_openpsa_event',
    'org_openpsa_calendar_event_dba' => 'org_openpsa_event',
    'midgard_eventmember' => 'org_openpsa_eventmember',
    'midgard_person' => 'org_openpsa_person',
    'midgard_group' => 'org_openpsa_organization',
),
/**
 * Define the property to use as 'URL-name' per class, use false for skipping heuristics and not using any property as url-name
 *
 * If class is not defined here heuristics will apply
 */
'name_exceptions' => array
(
    'midgard_event' => 'extra',
    'org_openpsa_calendar_event' => 'extra',
    'midgard_person' => false,
    'org_openpsa_person' => false,
    'midgard_parameter' => false,
    'midgard_host' => false,
    'midcom_core_privilege_db' => false,
    'midcom_core_temporary_object_db' => false,
    'org_routamc_positioning_country' => false,
),
/**
 * Define the property to use as 'title' per class, use false for skipping heuristics and not using any property as title
 *
 * If class is not defined here heuristics will apply
 */
'title_exceptions' => array
(
    'midgard_topic' => 'extra',
    'midgard_group' => 'official',
    'openpsa_person' => 'lastname',
    'midgard_person' => 'lastname',
    'org_openpsa_person' => 'lastname',
    'org_openpsa_organization' => 'official',
    'midgard_host' => 'name',
),
// Classes that never should be listed as root class
'root_class_exceptions_notroot' => array
(
    /* These are core special cases */
    'midgard_parameter',
    'midgard_attachment',
    'midgard_language',
    'midgard_quota',
    'midgard_activity',
    /* members should not be root classes by MgSchema, workaround a bug */
    'midgard_member',
    'org_openpsa_campaign_member',
    'org_openpsa_role',
    /* These use GUID to link to many parents */
    'net_nemein_tag_link',
    'net_nehmer_comments_comment_db',
    'org_openpsa_relatedto',
    'org_openpsa_relatedto_journal_entry',
    'org_openpsa_billing_data',
    'org_routamc_positioning_location',
    /* These we just don't anybody mucking about with */
    'midcom_core_login_session_db',
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
),
// Classes that should always be listed as root class (mainly workaround for schema bugs)
'root_class_exceptions_forceroot' => array
(
),
/* Classes we do not wish to resolve as children event though strictly speaking they are */
'child_class_exceptions_neverchild' => array
(
    'midgard_parameter',
    'midgard_attachment',
    'midcom_core_privilege_db'
),

// Always search these fields (used by get_search_properties), keyed by MgdSchema class name
'always_search_fields' => array
(
    'midgard_person' => array('email'),
    'org_openpsa_person' => array('email'),
),
// Never search these fields (used by get_search_properties), keyed by MgdSchema class name
'never_search_fields' => array
(
),
// keyed by icon, values are arrays of classes (__default__ marking the default icon) names, used to determine icon to use for object
'object_icon_magic' => array
(
    'document.png' => array
    (
        '__default__',
    ),
    'stock_folder.png' => array
    (
        'midgard_topic',
        'midgard_snippetdir',
        'midgard_style',
        'midgard_page',
        'org_openpsa_products_product_group',
    ),
    'stock_people.png' => array
    (
        'org_openpsa_campaign',
    ),
    'stock_news.png' => array
    (
        'org_openpsa_notifications_notification',
        'net_nemein_rss_feed',
    ),
    'locale.png' => array
    (
        'org_routamc_positioning_country',
    ),
    'geoclue.png' => array
    (
        'org_routamc_positioning_location',
    ),
    'stock-discussion.png' => array
    (
        'net_nehmer_comments_comment_db',
    ),
    'package.png' => array
    (
        'org_openpsa_products_product',
    ),
),
// This works just like object_icon_magic
'create_type_magic' => array
(
    'new-text.png' => array
    (
        '__default__',
    ),
    'new-dir.png' => array
    (
        'midgard_topic',
        'midgard_page',
        'midgard_snippetdir',
        'midgard_style',
    ),
    'stock_people-new.png' => array
    (
        'org_openpsa_campaign',
    ),
),

