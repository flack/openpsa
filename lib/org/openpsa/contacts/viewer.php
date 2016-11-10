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
    public function _on_handle($handler, array $args)
    {
        // Always run in uncached mode
        midcom::get()->cache->content->no_cache();

        midcom::get()->auth->require_valid_user();
        org_openpsa_widgets_contact::add_head_elements();
    }

    public function get_group_tree()
    {
        $root_group = org_openpsa_contacts_interface::find_root_group();

        $tree = new org_openpsa_widgets_tree('org_openpsa_contacts_group_dba', 'owner');
        $tree->link_callback = array($this, 'get_group_link');
        $tree->constraints[] = array('orgOpenpsaObtype', '<', org_openpsa_contacts_group_dba::MYCONTACTS);
        $tree->root_node = $root_group->id;
        $tree->title_fields = array('official', 'name');
        return $tree;
    }

    public function get_group_link($guid)
    {
        $nap = new midcom_helper_nav;
        $node = $nap->get_node($this->_topic->id);
        return $node[MIDCOM_NAV_ABSOLUTEURL] . 'group/' . $guid . '/';
    }

    /**
     * Get schema name for person
     *
     * @return string Schema name
     */
    public function get_person_schema(org_openpsa_contacts_person_dba $contact)
    {
        $my_company_guid = $this->_config->get('owner_organization');

        if (   empty($my_company_guid)
            || !mgd_is_guid($my_company_guid)) {
            if (midcom::get()->auth->admin) {
                midcom::get()->uimessages->add(
                    $this->_l10n->get($this->_component),
                    $this->_l10n->get('owner organization couldnt be found'),
                    'error'
                );
            }
        } else {
            // Figure out if user is from own organization or other org
            $person_user = new midcom_core_user($contact->id);

            if ($person_user->is_in_group("group:{$my_company_guid}")) {
                return 'employee';
            }
        }

        return null;
    }

    public static function add_breadcrumb_path_for_group($group, $handler)
    {
        if (!is_object($group)) {
            return;
        }
        $tmp = array();

        $root_group = org_openpsa_contacts_interface::find_root_group();
        $root_id = $root_group->id;

        $tmp[$group->guid] = $group->official;

        $parent = $group->get_parent();
        while ($parent && $parent->id != $root_id) {
            $group = $parent;
            $tmp[$group->guid] = $group->official;
            $parent = $group->get_parent();
        }

        $tmp = array_reverse($tmp, true);

        foreach ($tmp as $guid => $title) {
            $handler->add_breadcrumb('group/' . $guid . '/', $title);
        }
    }
}
