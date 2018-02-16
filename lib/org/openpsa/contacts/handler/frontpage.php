<?php
/**
 * @package org.openpsa.contacts
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\helper\autocomplete;

/**
 * Frontpage class
 *
 * @package org.openpsa.contacts
 */
class org_openpsa_contacts_handler_frontpage extends midcom_baseclasses_components_handler
{
    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_frontpage($handler_id, array $args, array &$data)
    {
        autocomplete::add_head_elements();
        $data['tree'] = $this->_master->get_group_tree();

        $workflow = $this->get_workflow('datamanager');
        $buttons = [];
        if (midcom::get()->auth->can_user_do('midgard:create', null, org_openpsa_contacts_person_dba::class)) {
            $buttons[] = $workflow->get_button('person/create/', [
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('create person'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_person-new.png',
            ]);
        }

        if (midcom::get()->auth->can_user_do('midgard:create', null, org_openpsa_contacts_group_dba::class)) {
            $buttons[] = $workflow->get_button("group/create/organization/", [
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('create organization'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_people-new.png',
            ]);
            $buttons[] = $workflow->get_button("group/create/group/", [
                MIDCOM_TOOLBAR_LABEL => sprintf($this->_l10n_midcom->get('create %s'), $this->_l10n->get('group')),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_people-new.png',
            ]);
        }

        $p_merger = new org_openpsa_contacts_duplicates_merge('person', $this->_config);
        if ($p_merger->merge_needed()) {
            $buttons[] = [
                MIDCOM_TOOLBAR_URL => 'duplicates/person/',
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('merge persons'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/repair.png',
                MIDCOM_TOOLBAR_ENABLED => midcom::get()->auth->can_user_do('midgard:update', null, org_openpsa_contacts_person_dba::class),
            ];
        }
        $this->_view_toolbar->add_items($buttons);

        if (   $this->_topic->can_do('midgard:update')
            && $this->_topic->can_do('midcom:component_config')) {
            $this->_node_toolbar->add_item($workflow->get_button('config/', [
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('component configuration'),
                MIDCOM_TOOLBAR_HELPTEXT => $this->_l10n_midcom->get('component configuration helptext'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_folder-properties.png',
            ]));
        }

        midcom::get()->head->set_pagetitle($this->_l10n->get("my contacts"));
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_frontpage($handler_id, array &$data)
    {
        midcom_show_style('show-frontpage');
    }
}
