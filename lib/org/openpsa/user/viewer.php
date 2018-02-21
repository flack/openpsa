<?php
/**
 * @package org.openpsa.user
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Request class for user management
 *
 * @package org.openpsa.user
 */
class org_openpsa_user_viewer extends midcom_baseclasses_components_request
{
    public function _on_handle($handler_id, array $args)
    {
        if ($handler_id != 'lostpassword') {
            midcom::get()->auth->require_valid_user();
        }
    }
}
