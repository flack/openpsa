<?php
/**
 * @package org.openpsa.contacts
 * @author Nemein Oy http://www.nemein.com/
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
     * The handle callback populates the toolbars.
     */
    public function _on_handle($handler, $args)
    {
        // Always run in uncached mode
        $_MIDCOM->cache->content->no_cache();

        if ($handler != 'buddylist_xml')
        {
            $_MIDCOM->auth->require_valid_user();
        }

        org_openpsa_widgets_contact::add_head_elements();
    }

    public function get_group_tree()
    {
        $root_group = org_openpsa_contacts_interface::find_root_group();

        $tree = new org_openpsa_widgets_tree('org_openpsa_contacts_group_dba', 'owner');
        $tree->constraints[] = array('orgOpenpsaObtype', '=', ORG_OPENPSA_OBTYPE_OTHERGROUP);
        $tree->root_node = $root_group->id;
        $tree->title_fields = array('official', 'name');
        return $tree;
    }

    public static function add_breadcrumb_path_for_group($group, &$handler)
    {
        if (!is_object($group))
        {
            return;
        }
        $tmp = array();

        $root_group = org_openpsa_contacts_interface::find_root_group();
        $root_id = $root_group->id;

        $tmp[$group->guid] = $group->official;

        $parent = $group->get_parent();
        while ($parent && $parent->id != $root_id)
        {
            $group = $parent;
            $tmp[$group->guid] = $group->official;
            $parent = $group->get_parent();
        }

        $tmp = array_reverse($tmp, true);

        foreach ($tmp as $guid => $title)
        {
            $handler->add_breadcrumb('group/' . $guid . '/', $title);
        }
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
            $topic = midcom_db_topic::get_cached($topic);
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
            $topic = midcom_db_topic::get_cached($topic);
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