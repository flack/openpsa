<?php
/**
 * @package midcom.helper.datamanager2
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Interface for DM2 option callback classes
 *
 * @package midcom.helper.datamanager2
 */
interface midcom_helper_datamanager2_callback_interface
{
    /**
     * @param array $arg The arguments for the callback
     */
    public function __construct($arg);

    /**
     * The set_type call is executed upon type startup, giving you a reference to the type
     * you are supplying with information. You may ignore this call (but it has to be defined
     * to satisfy PHP).
     *
     * @param midcom_helper_datamanager2_type &$type The current type
     */
    public function set_type(&$type);

    /**
     * @return boolean
     */
    public function key_exists($key);

    /**
     * You can safely assume that get_name_for_key receives only valid keys.
     *
     * @return string
     */
    public function get_name_for_key($key);

    /**
     * list_all must use the same return format as the options array would normally
     * have. One instance of this class is created per type.
     *
     * @return array
     */
    public function list_all();
}
