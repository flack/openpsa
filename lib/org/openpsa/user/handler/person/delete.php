<?php
/**
 * @package org.openpsa.user
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Delete person class for user management
 *
 * @package org.openpsa.user
 */
class org_openpsa_user_handler_person_delete extends midcom_baseclasses_components_handler
{
    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_delete($handler_id, array $args, array &$data)
    {
        $person = new midcom_db_person($args[0]);
        if ($person->id != midcom_connection::get_user())
        {
            midcom::get()->auth->require_user_do('org.openpsa.user:manage', null, 'org_openpsa_user_interface');
        }

        $controller = midcom_helper_datamanager2_handler::get_delete_controller();
        if ($controller->process_form() == 'delete')
        {
            if ($person->delete())
            {
                $indexer = midcom::get()->indexer;
                $indexer->delete($person->guid);
                midcom::get()->uimessages->add($this->_l10n->get($this->_component), sprintf($this->_l10n_midcom->get("%s deleted"), $person->name));
                return new midcom_response_relocate('');
            }
            midcom::get()->uimessages->add($this->_l10n->get('org.openpsa.user'), $this->_l10n->get("failed to delete person, reason") . ' ' . midcom_connection::get_error_string(), 'error');
        }
        return new midcom_response_relocate('view/' . $person->guid . '/');
    }
}
