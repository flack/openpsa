<?php
/**
 * @package midcom.helper.datamanager2
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Shortcut functions for use from handlers which implement the respective interfaces
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_handler
{
    /**
     * Loads the DM2 view of an object.
     *
     * @param midcom_baseclasses_components_handler &$handler The handler from which we were called
     * @param midcom_core_dbaobject The object to display
     * @return array The get_content_html output for the requested object
     */
    public static function get_view(&$handler, &$object)
    {
        $schemadb = $handler->load_schemadb();
        $datamanager = new midcom_helper_datamanager2_datamanager($schemadb);

        if (   ! $datamanager
            || ! $datamanager->autoset_storage($object))
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Failed to create a DM2 instance for object {$object->guid}.");
            // This will exit.
        }
        return $datamanager->get_content_html();
    }

    /**
     * Loads and prepares the edit controller. Any error triggers a 500.
     *
     * @param midcom_baseclasses_components_handler &$handler The handler from which we were called
     * @param midcom_core_dbaobject The object to display
     * @return midcom_helper_datamanager2_controller_simple The edit controller for the requested object
     */
    public static function get_simple_controller(&$handler, &$object)
    {
        $schemadb = $handler->load_schemadb();
        $controller = midcom_helper_datamanager2_controller::create('simple');
        $controller->schemadb =& $schemadb;

        $controller->set_storage($object, $handler->get_schema_name());
        if (! $controller->initialize())
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Failed to initialize a DM2 controller instance for object {$object->guid}.");
            // This will exit.
        }
        return $controller;
    }
}
?>