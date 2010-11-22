<?php
/**
 * @package org.openpsa.contacts
 * @author Nemein Oy http://www.nemein.com/
 * @version $Id: viewer.php 26257 2010-06-01 15:19:12Z gudd $
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * org.openpsa.contacts site interface class.
 *
 * Contact management, address book and user manager
 *
 * @package org.openpsa.contacts
 */
class org_openpsa_contacts_viewer extends midcom_baseclasses_components_request
{
    /**
     * Constructor.
     *
     * OpenPSA Contacts handles its URL space following the convention:
     * - First parameter is the object type (person, group, salesproject, list)
     * - Second parameter is the object identifier (GUID, or some special filter like "all")
     * - Third parameter defines current view/action
     * - Additional parameters are defined by the action concerned
     */
    function __construct($topic, $config)
    {
        parent::__construct($topic, $config);

        // Match /duplicates/person
        $this->_request_switch['person_duplicates'] = array
        (
            'handler' => array('org_openpsa_contacts_handler_duplicates_person', 'sidebyside'),
            'fixed_args' => array('duplicates', 'person'),
        );
        
        // Match /buddylist/
        $this->_request_switch['buddylist'] = array
        (
            'handler' => array('org_openpsa_contacts_handler_buddy_list', 'list'),
            'fixed_args' => 'buddylist',
        );

        // Match /buddylist/xml
        $this->_request_switch['buddylist_xml'] = array
        (
            'handler' => array('org_openpsa_contacts_handler_buddy_list', 'list'),
            'fixed_args' => array('buddylist', 'xml'),
        );

        // Match /buddylist/add/<person guid>
        $this->_request_switch['buddylist_add'] = array
        (
            'handler' => array('org_openpsa_contacts_handler_buddy_list', 'add'),
            'fixed_args' => array('buddylist', 'add'),
            'variable_args' => 1,
        );

        // Match /buddylist/remove/<person guid>
        $this->_request_switch['budyylist_remove'] = array
        (
            'handler' => array('org_openpsa_contacts_handler_buddy_list', 'remove'),
            'fixed_args' => array('buddylist', 'remove'),
            'variable_args' => 1,
        );

        // Match /search/<type>
        $this->_request_switch['search_type'] = array
        (
            'handler' => array('org_openpsa_contacts_handler_search', 'search_type'),
            'fixed_args' => 'search',
            'variable_args' => 1,
        );

        // Match /search/
        $this->_request_switch['search'] = array
        (
            'handler' => array('org_openpsa_contacts_handler_search', 'search'),
            'fixed_args' => 'search',
        );

        // Match /group/create/<GUID>
        $this->_request_switch['group_new_subgroup'] = array
        (
            'handler' => array('org_openpsa_contacts_handler_group_create', 'create'),
            'fixed_args' => array('group', 'create'),
            'variable_args' => 1,
        );

        // Match /group/edit/<GUID>
        $this->_request_switch['group_edit'] = array
        (
            'handler' => array('org_openpsa_contacts_handler_group_edit', 'edit'),
            'fixed_args' => array('group', 'edit'),
            'variable_args' => 1,
        );
                    
        // Match /group/privileges/GUID
        $this->_request_switch['group_privileges'] = array
        (
            'handler' => array('org_openpsa_contacts_handler_group_privileges', 'privileges'),
            'fixed_args' => array('group', 'privileges'),
            'variable_args' => 1,
        );

        $this->_request_switch['group_notifications'] = array
        (
            'handler' => array('org_openpsa_contacts_handler_group_action', 'notifications'),
            'fixed_args' => array('group', 'notifications'),
            'variable_args' => 1,
        );
        
        // Match /group/<GUID>/<action>
        $this->_request_switch['group_action'] = array
        (
            'handler' => array('org_openpsa_contacts_handler_group_action', 'action'),
            'fixed_args' => 'group',
            'variable_args' => 2,
        );
        
        // Match /group/create
        $this->_request_switch['group_new'] = array
        (
            'handler' => array('org_openpsa_contacts_handler_group_create', 'create'),
            'fixed_args' => array('group', 'create'),
        );
        
        // Match /group/<GUID>
        $this->_request_switch['group_view'] = array
        (
            'handler' => array('org_openpsa_contacts_handler_group_view', 'view'),
            'fixed_args' => 'group',
            'variable_args' => 1,
        );

        // Match /person/create/GroupGUID
        $this->_request_switch['person_new_group'] = array
        (
            'handler' => array('org_openpsa_contacts_handler_person_create', 'create'),
            'fixed_args' => array('person', 'create'),
            'variable_args' => 1,
        );

         // Match /person/create
        $this->_request_switch['person_new'] = array
        (
            'handler' => array('org_openpsa_contacts_handler_person_create', 'create'),
            'fixed_args' => array('person', 'create'),
        );

         // Match /person/GUID
        $this->_request_switch['person_view'] = array
        (
            'handler' => array('org_openpsa_contacts_handler_person_view', 'view'),
            'fixed_args' => 'person',
            'variable_args' => 1,
        );

         // Match /person/edit/GUID
        $this->_request_switch['person_edit'] = array
        (
            'handler' => array('org_openpsa_contacts_handler_person_admin', 'edit'),
            'fixed_args' => array('person', 'edit'),
            'variable_args' => 1,
        );

         // Match /person/delete/GUID
        $this->_request_switch['person_delete'] = array
        (
            'handler' => array('org_openpsa_contacts_handler_person_admin', 'delete'),
            'fixed_args' => array('person', 'delete'),
            'variable_args' => 1,
        );

        // Match /person/privileges/GUID
        $this->_request_switch['person_privileges'] = array
        (
            'handler' => array('org_openpsa_contacts_handler_person_privileges', 'privileges'),
            'fixed_args' => array('person', 'privileges'),
            'variable_args' => 1,
        );
        
        // Match /account/create/GUID/
        $this->_request_switch['account_create'] = array
        (
            'handler' => array('org_openpsa_contacts_handler_person_action', 'account_create'),
            'fixed_args' => array('account', 'create'),
            'variable_args' => 1,
        );

        // Match /account/edit/GUID/
        $this->_request_switch['account_edit'] = array
        (
            'handler' => array('org_openpsa_contacts_handler_person_action', 'account_edit'),
            'fixed_args' => array('account', 'edit'),
            'variable_args' => 1,
        );
        // Match /person/memberships/GUID/
        $this->_request_switch['group_memberships'] = array
        (
            'handler' => array('org_openpsa_contacts_handler_person_action', 'group_memberships'),
            'fixed_args' => array('person', 'memberships'),
            'variable_args' => 1,
        );


        // Match /
        $this->_request_switch['frontpage'] = array
        (
            'handler' => array('org_openpsa_contacts_handler_frontpage', 'frontpage'),
        );

         // Match /config/
        $this->_request_switch['config'] = array
        (
            'handler' => array('midcom_core_handler_configdm2', 'config'),
            'fixed_args' => 'config',
        );

        //If you need any custom switches add them here
    }

    /**
     * The handle callback populates the toolbars.
     */
    function _on_handle($handler, $args)
    {
        // Always run in uncached mode
        $_MIDCOM->cache->content->no_cache();
        
        if ($handler != 'buddylist_xml')
        {
            $_MIDCOM->auth->require_valid_user();
        }

        $_MIDCOM->load_library('org.openpsa.contactwidget');

        $_MIDCOM->add_link_head
        (
            array
            (
                'rel' => 'stylesheet',
                'type' => 'text/css',
                'href' => MIDCOM_STATIC_URL . "/org.openpsa.core/ui-elements.css",
            )
        );

        return true;
    }

    static function get_breadcrumb_path_for_group($group, &$tmp)
    {
        if (!is_object($group))
        {
            return;
        }
        $root_group = org_openpsa_contacts_interface::find_root_group();
        $root_id = $root_group->id;

        $tmp[] = array
        (
            MIDCOM_NAV_URL => "group/{$group->guid}/",
            MIDCOM_NAV_NAME => $group->official,
        );

        $parent = $group->get_parent();
        while ($parent && $parent->id != $root_id)
        {
            $group = $parent;
            $tmp[] = array
            (
                MIDCOM_NAV_URL => "group/{$group->guid}/",
                MIDCOM_NAV_NAME => $group->official,
            );
            $parent = $group->get_parent();
        }

        $tmp = array_reverse($tmp);
    }

    /**
     * Indexes a group.
     *
     * This function is usually called statically from various handlers.
     *
     * @param midcom_helper_datamanager2_datamanager &$dm The Datamanager encapsulating the group.
     * @param midcom_services_indexer &$indexer The indexer instance to use.
     * @param midcom_db_topic The topic which we are bound to. If this is not an object, the code
     *     tries to load a new topic instance from the database identified by this parameter.
     */
    public static function index_group(&$dm, &$indexer, $topic)
    {
        if (!is_object($topic))
        {
            $tmp = midcom_db_topic::get_cached($topic);
            if (! $tmp)
            {
                $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                    "Failed to load the topic referenced by {$topic} for indexing, this is fatal.");
                // This will exit.
            }
            $topic = $tmp;
        }

        $nav = new midcom_helper_nav();
        $node = $nav->get_node($topic->id);

        $document = $indexer->new_document($dm);

        $document->topic_guid = $topic->guid;
        $document->component = $topic->component;
        $document->topic_url = $node[MIDCOM_NAV_FULLURL];
        $document->read_metadata_from_object($dm->storage->object);
        $indexer->index($document);
    }

    /**
     * Indexes a person.
     *
     * This function is usually called statically from various handlers.
     *
     * @param midcom_helper_datamanager2_datamanager &$dm The Datamanager encapsulating the person.
     * @param midcom_services_indexer &$indexer The indexer instance to use.
     * @param midcom_db_topic The topic which we are bound to. If this is not an object, the code
     *     tries to load a new topic instance from the database identified by this parameter.
     */
    public static function index_person(&$dm, &$indexer, $topic)
    {
        if (!is_object($topic))
        {
            $tmp = midcom_db_topic::get_cached($topic);
            if (! $tmp)
            {
                $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                    "Failed to load the topic referenced by {$topic} for indexing, this is fatal.");
                // This will exit.
            }
            $topic = $tmp;
        }

        $nav = new midcom_helper_nav();
        $node = $nav->get_node($topic->id);

        $document = $indexer->new_document($dm);
        $document->title = $dm->storage->object->name;
        $document->topic_guid = $topic->guid;
        $document->component = $topic->component;
        $document->topic_url = $node[MIDCOM_NAV_FULLURL];
        $document->read_metadata_from_object($dm->storage->object);
        $indexer->index($document);
    }
}

?>