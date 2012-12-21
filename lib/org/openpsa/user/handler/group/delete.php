<?php
/**
 * @package org.openpsa.user
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Delete group class for user management
 *
 * @package org.openpsa.user
 */
class org_openpsa_user_handler_group_delete extends midcom_baseclasses_components_handler
implements midcom_helper_datamanager2_interfaces_view
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
    public function load_schemadb()
    {
        return midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_group'));
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_delete($handler_id, array $args, array &$data)
    {
        midcom::get('auth')->require_user_do('org.openpsa.user:manage', null, 'org_openpsa_user_interface');

        $this->_group = new midcom_db_group($args[0]);

        if (array_key_exists('org_openpsa_user_deleteok', $_POST))
        {
            $delete_succeeded = $this->_group->delete();
            if ($delete_succeeded)
            {
                // Update the index
                $indexer = midcom::get('indexer');
                $indexer->delete($this->_group->guid);

                return new midcom_response_relocate('');
            }
            else
            {
                // Failure, give a message
                midcom::get('uimessages')->add($this->_l10n->get('org.openpsa.user'), $this->_l10n->get("failed to delete group, reason") . ' ' . midcom_connection::get_error_string(), 'error');
                return new midcom_response_relocate('group/' . $this->_group->guid . '/');
            }
        }

        $data['view'] = midcom_helper_datamanager2_handler::get_view_controller($this, $this->_group);
        $data['group'] = $this->_group;

        org_openpsa_widgets_tree::add_head_elements();

        $this->add_breadcrumb('groups/', $this->_l10n->get('groups'));
        $this->add_breadcrumb('', sprintf($this->_l10n_midcom->get('delete %s'), $this->_group->get_label()));

        $this->bind_view_to_object($this->_group);
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_delete($handler_id, array &$data)
    {
        midcom_show_style("show-group-delete");
    }

}
?>