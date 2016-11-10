<?php
/**
 * @package org.openpsa.user
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * View person class for user management
 *
 * @package org.openpsa.user
 */
class org_openpsa_user_handler_person_view extends midcom_baseclasses_components_handler
implements midcom_helper_datamanager2_interfaces_view
{
    /**
     * The person we're working on
     *
     * @var midcom_db_person
     */
    private $_person;

    /**
     * Loads and prepares the schema database.
     *
     * The operations are done on all available schemas within the DB.
     */
    public function load_schemadb()
    {
        return midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_person'));
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_view($handler_id, array $args, array &$data)
    {
        $this->_person = new org_openpsa_contacts_person_dba($args[0]);
        $data['view'] = midcom_helper_datamanager2_handler::get_view_controller($this, $this->_person);
        $this->add_breadcrumb('', $this->_person->get_label());

        $auth = midcom::get()->auth;
        if (   $this->_person->id == midcom_connection::get_user()
            || $auth->can_user_do('org.openpsa.user:manage', null, 'org_openpsa_user_interface')) {
            $buttons = array();
            $workflow = $this->get_workflow('datamanager2');
            if ($this->_person->can_do('midgard:update')) {
                $buttons[] = $workflow->get_button("edit/{$this->_person->guid}/", array
                (
                    MIDCOM_TOOLBAR_ACCESSKEY => 'e',
                ));
            }
            if ($this->_person->can_do('midgard:delete')) {
                $delete_workflow = $this->get_workflow('delete', array('object' => $this->_person));
                $buttons[] = $delete_workflow->get_button("delete/{$this->_person->guid}/");
            }
            if (   midcom_connection::is_user($this->_person)
                && $this->_person->can_do('midgard:privileges')) {
                $buttons[] = $workflow->get_button("privileges/{$this->_person->guid}/", array
                (
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get("permissions"),
                    MIDCOM_TOOLBAR_ICON => 'midgard.admin.asgard/permissions-16.png',
                ));
            }

            if ($this->_person->can_do('midgard:update')) {
                $buttons[] = $workflow->get_button("person/notifications/{$this->_person->guid}/", array
                (
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get("notification settings"),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock-discussion.png',
                ));
            }
            $this->_view_toolbar->add_items($buttons);
        }
        $this->bind_view_to_object($this->_person);
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_view($handler_id, array &$data)
    {
        $data['person'] = $this->_person;
        $data['account'] = new midcom_core_account($this->_person);
        midcom_show_style('show-person');
    }
}
