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
{
    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_delete($handler_id, array $args, array &$data)
    {
        midcom::get()->auth->require_user_do('org.openpsa.user:manage', null, 'org_openpsa_user_interface');
        $group = new midcom_db_group($args[0]);

        $controller = midcom_helper_datamanager2_handler::get_delete_controller();
        if ($controller->process_form() == 'delete')
        {
            if ($group->delete())
            {
                $indexer = midcom::get()->indexer;
                $indexer->delete($group->guid);
                midcom::get()->uimessages->add($this->_l10n->get($this->_component), sprintf($this->_l10n_midcom->get("%s deleted"), $group->get_label()));
                return new midcom_response_relocate('');
            }
            midcom::get()->uimessages->add($this->_l10n->get('org.openpsa.user'), $this->_l10n->get("failed to delete group, reason") . ' ' . midcom_connection::get_error_string(), 'error');
        }
        return new midcom_response_relocate('group' . $group->guid . '/');
    }
}
?>