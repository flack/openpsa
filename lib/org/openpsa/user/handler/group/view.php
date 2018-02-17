<?php
/**
 * @package org.openpsa.user
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use midcom\datamanager\datamanager;

/**
 * View group class for user management
 *
 * @package org.openpsa.user
 */
class org_openpsa_user_handler_group_view extends midcom_baseclasses_components_handler
{
    /**
     * The group we're working on
     *
     * @var midcom_db_group
     */
    private $_group;

    /**
     * Loads and prepares the schema database.
     *
     * The operations are done on all available schemas within the DB.
     */
    private function load_datamanager()
    {
        $dm = datamanager::from_schemadb($this->_config->get('schemadb_group'));
        $persons =& $dm->get_schema('default')->get_field('persons');
        $persons['hidden'] = true;
        return $dm->set_storage($this->_group);
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_view($handler_id, array $args, array &$data)
    {
        midcom::get()->auth->require_user_do('org.openpsa.user:access', null, org_openpsa_user_interface::class);

        $this->_group = new midcom_db_group($args[0]);
        $data['group'] = $this->_group;

        $data['view'] = $this->load_datamanager();
        org_openpsa_widgets_tree::add_head_elements();
        org_openpsa_widgets_grid::add_head_elements();

        $this->add_breadcrumb('groups/', $this->_l10n->get('groups'));
        $this->add_breadcrumb('', $this->_group->get_label());

        $workflow = $this->get_workflow('datamanager');
        $buttons = [];
        if ($this->_group->can_do('midgard:update')) {
            $buttons[] = $workflow->get_button("group/edit/{$this->_group->guid}/", [
                MIDCOM_TOOLBAR_ACCESSKEY => 'e',
            ]);
        }
        if ($this->_group->can_do('midgard:delete')) {
            $delete_workflow = $this->get_workflow('delete', ['object' => $this->_group]);
            $buttons[] = $delete_workflow->get_button("group/delete/{$this->_group->guid}/");
        }

        if ($this->_group->can_do('midgard:privileges')) {
            $buttons[] = $workflow->get_button("group/privileges/{$this->_group->guid}/", [
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get("permissions"),
                MIDCOM_TOOLBAR_ICON => 'midgard.admin.asgard/permissions-16.png',
            ]);
        }

        if ($this->_group->can_do('midgard:update')) {
            $buttons[] = $workflow->get_button("group/notifications/{$this->_group->guid}/", [
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get("notification settings"),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock-discussion.png',
            ]);
        }

        if (midcom::get()->auth->can_user_do('midgard:create', null, org_openpsa_contacts_person_dba::class)) {
            $buttons[] = $workflow->get_button("create/{$this->_group->guid}/", [
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('create person'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_person-new.png',
            ]);
        }
        $this->_view_toolbar->add_items($buttons);
        $this->bind_view_to_object($this->_group);

        return $this->show('show-group');
    }
}
