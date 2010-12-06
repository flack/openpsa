<?php
/**
 * @package midcom.helper.datamanager2
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Interface for create-type handlers
 *
 * @package midcom.helper.datamanager2
 */
interface midcom_helper_datamanager2_interfaces_create extends midcom_helper_datamanager2_interfaces_nullstorage
{
    /**
     * DM2 callback, has to create the new DBA object
     *
     * Assumes Admin Privileges.
     *
     * @param midcom_helper_datamanager2_controller_create &$controller The current controller
     * @return midcom_core_dbaobject The newly-created object
     */
    public function & dm2_create_callback (&$controller);
}
?>