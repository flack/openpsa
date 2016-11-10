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
     * @param midcom_baseclasses_components_handler $handler The handler from which we were called
     * @param midcom_core_dbaobject $object The object to display
     * @return array The get_content_html output for the requested object
     */
    public static function get_view(midcom_helper_datamanager2_interfaces_view $handler, $object)
    {
        $datamanager = self::get_view_controller($handler, $object);
        return $datamanager->get_content_html();
    }

    /**
     * Loads the DM2 view of an object.
     *
     * @param midcom_baseclasses_components_handler $handler The handler from which we were called
     * @param midcom_core_dbaobject $object The object to display
     * @return array The get_content_html output for the requested object
     */
    public static function get_view_controller(midcom_helper_datamanager2_interfaces_view $handler, $object)
    {
        $schemadb = $handler->load_schemadb();
        $datamanager = new midcom_helper_datamanager2_datamanager($schemadb);
        $datamanager->set_schema($handler->get_schema_name());

        if (!$datamanager->set_storage($object)) {
            throw new midcom_error("Failed to create a DM2 instance for object {$object->guid}.");
        }
        return $datamanager;
    }

    /**
     * Loads and prepares the edit controller. Any error triggers a 500.
     *
     * @param midcom_baseclasses_components_handler $handler The handler from which we were called
     * @param midcom_core_dbaobject $object The object to display
     * @return midcom_helper_datamanager2_controller_simple The edit controller for the requested object
     */
    public static function get_simple_controller(midcom_helper_datamanager2_interfaces_edit $handler, $object)
    {
        $controller = midcom_helper_datamanager2_controller::create('simple');
        $controller->schemadb = $handler->load_schemadb();

        $controller->set_storage($object, $handler->get_schema_name());
        if (!$controller->initialize()) {
            throw new midcom_error("Failed to initialize a DM2 controller instance for object {$object->guid}.");
        }
        return $controller;
    }

    /**
     * Loads and prepares the nullstorage controller. Any error triggers a 500.
     *
     * @param midcom_baseclasses_components_handler $handler The handler from which we were called
     * @return midcom_helper_datamanager2_controller_nullstorage The requested controller
     */
    public static function get_nullstorage_controller(midcom_helper_datamanager2_interfaces_nullstorage $handler)
    {
        $controller = midcom_helper_datamanager2_controller::create('nullstorage');
        $controller->schemadb = $handler->load_schemadb();
        $controller->schemaname = $handler->get_schema_name();
        $controller->defaults = $handler->get_schema_defaults();
        if (!$controller->initialize()) {
            throw new midcom_error('Failed to initialize a DM2 nullstorage controller.');
        }
        return $controller;
    }

    /**
     * Loads and prepares the create controller. Any error triggers a 500.
     *
     * @param midcom_baseclasses_components_handler $handler The handler from which we were called
     * @return midcom_helper_datamanager2_controller_create The requested controller
     */
    public static function get_create_controller(midcom_helper_datamanager2_interfaces_create $handler)
    {
        $controller = midcom_helper_datamanager2_controller::create('create');
        $controller->schemadb = $handler->load_schemadb();
        $controller->schemaname = $handler->get_schema_name();
        $controller->defaults = $handler->get_schema_defaults();
        $controller->callback_object = $handler;
        if (!$controller->initialize()) {
            throw new midcom_error('Failed to initialize a DM2 create controller.');
        }
        return $controller;
    }

    /**
     * Loads and prepares a delete controller. Any error triggers a 500.
     *
     * @return midcom_helper_datamanager2_controller_nullstorage The delete controller
     */
    public static function get_delete_controller()
    {
        $controller = midcom_helper_datamanager2_controller::create('nullstorage');
        $delete_schemadb = midcom_baseclasses_components_configuration::get('midcom.helper.datamanager2', 'config')->get('schemadb_delete');
        $controller->schemadb = midcom_helper_datamanager2_schema::load_database($delete_schemadb);

        if (!$controller->initialize()) {
            throw new midcom_error("Failed to initialize a DM2 delete controller.");
        }
        return $controller;
    }
}
