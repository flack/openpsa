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
     * It must return a reference to a freshly created object that should be populated
     * with the validated form data. It receives a reference to the controller instance
     * calling it.
     *
     * If the callback is unable to create an empty object for whatever reason, you should
     * throw midcom_error. There is no error handling whatsoever on the side of the
     * controller instance. If the function returns, a valid instance is expected.
     *
     * @param midcom_helper_datamanager2_controller_create &$controller The current controller
     * @return midcom_core_dbaobject The newly-created object
     */
    public function & dm2_create_callback (&$controller);
}
