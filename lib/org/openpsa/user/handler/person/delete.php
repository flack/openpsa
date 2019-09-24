<?php
/**
 * @package org.openpsa.user
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use Symfony\Component\HttpFoundation\Request;

/**
 * Delete person class for user management
 *
 * @package org.openpsa.user
 */
class org_openpsa_user_handler_person_delete extends midcom_baseclasses_components_handler
{
    public function _handler_delete(Request $request, string $guid)
    {
        $person = new midcom_db_person($guid);
        if ($person->id != midcom_connection::get_user()) {
            midcom::get()->auth->require_user_do('org.openpsa.user:manage', null, org_openpsa_user_interface::class);
        }

        $workflow = $this->get_workflow('delete', ['object' => $person]);
        return $workflow->run($request);
    }
}
