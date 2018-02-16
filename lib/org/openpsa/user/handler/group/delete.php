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
        midcom::get()->auth->require_user_do('org.openpsa.user:manage', null, org_openpsa_user_interface::class);
        $group = new midcom_db_group($args[0]);
        $workflow = $this->get_workflow('delete', ['object' => $group]);
        return $workflow->run();
    }
}
