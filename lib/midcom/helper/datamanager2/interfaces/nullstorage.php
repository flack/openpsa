<?php
/**
 * @package midcom.helper.datamanager2
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Interface for nullstorage-type handlers
 *
 * @package midcom.helper.datamanager2
 */
interface midcom_helper_datamanager2_interfaces_nullstorage extends midcom_helper_datamanager2_interfaces_edit
{
    /**
     * Callback that returns default values for the DM2 form
     *
     * @return array Default values for the form (the array key has to match a key in the schema)
     */
    public function get_schema_defaults();
}
