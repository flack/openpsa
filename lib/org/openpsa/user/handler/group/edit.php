<?php
/**
 * @package org.openpsa.user
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Edit group class for user management
 *
 * @package org.openpsa.user
 */
class org_openpsa_user_handler_group_edit extends midcom_baseclasses_components_handler
implements midcom_helper_datamanager2_interfaces_edit
{
    /**
     * The person we're working on
     *
     * @var midcom_db_group
     */
    private $_group;

    /**
     * Loads and prepares the schema database.
     *
     * The operations are done on all available schemas within the DB.
     */
    public function load_schemadb()
    {
        return midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_group'));
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_edit($handler_id, array $args, array &$data)
    {
        midcom::get('auth')->require_user_do('org.openpsa.user:manage', null, 'org_openpsa_user_interface');

        $this->_group = new midcom_db_group($args[0]);
        $data['controller'] = $this->get_controller('simple', $this->_group);
        switch ($data['controller']->process_form())
        {
            case 'save':
                midcom::get('uimessages')->add($this->_l10n->get('org.openpsa.user'), sprintf($this->_l10n->get('group %s saved'), $this->_group->get_label()));
                // Fall-through

            case 'cancel':
                return new midcom_response_relocate('group/' . $this->_group->guid . '/');
        }

        $this->add_breadcrumb('groups/', $this->_l10n->get('groups'));
        $this->add_breadcrumb('', sprintf($this->_l10n_midcom->get('edit %s'), $this->_group->get_label()));

        org_openpsa_helpers::dm2_savecancel($this);
        $this->bind_view_to_object($this->_group);
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_edit($handler_id, array &$data)
    {
        midcom_show_style("show-group-edit");
    }

}
?>