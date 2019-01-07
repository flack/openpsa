<?php
use Symfony\Component\HttpFoundation\Request;

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
     * @param string $guid The object's GUID
     */
    public function _handler_delete(Request $request, $guid)
    {
        midcom::get()->auth->require_user_do('org.openpsa.user:manage', null, org_openpsa_user_interface::class);
        $group = new midcom_db_group($guid);
        $workflow = $this->get_workflow('delete', ['object' => $group]);
        return $workflow->run($request);
    }
}
