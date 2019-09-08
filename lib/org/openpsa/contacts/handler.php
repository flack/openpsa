<?php
/**
 * @package org.openpsa.contacts
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Handler addons
 *
 * @package org.openpsa.contacts
 */
trait org_openpsa_contacts_handler
{
    public function add_head_elements()
    {
        midcom::get()->uimessages->add_head_elements();
        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . "/org.openpsa.helpers/editable.js");
        org_openpsa_widgets_contact::add_head_elements();
    }

    public function get_group_tree()
    {
        $root_group = org_openpsa_contacts_interface::find_root_group();
        $nap = new midcom_helper_nav;
        $prefix = $nap->get_node($this->_topic->id)[MIDCOM_NAV_ABSOLUTEURL];

        $tree = new org_openpsa_widgets_tree(org_openpsa_contacts_group_dba::class, 'owner');
        $tree->link_callback = function ($guid) use ($prefix) {
            return $prefix . 'group/' . $guid . '/';
        };
        $tree->constraints[] = ['orgOpenpsaObtype', '<', org_openpsa_contacts_group_dba::MYCONTACTS];
        $tree->root_node = $root_group->id;
        $tree->title_fields = ['official', 'name'];
        return $tree;
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

        return 'default';
    }
}
