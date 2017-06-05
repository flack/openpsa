<?php
/**
 * @package midcom.admin.folder
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Metadata editor.
 *
 * This handler uses midcom.helper.datamanager2 to edit object metadata properties
 *
 * @package midcom.admin.folder
 */
class midcom_admin_folder_handler_metadata extends midcom_baseclasses_components_handler
{
    /**
     * Object requested for metadata editing
     *
     * @var mixed Object for metadata editing
     */
    private $_object = null;

    /**
     * Edit controller instance for Datamanager 2
     *
     * @var midcom_helper_datamanager2_controller
     */
    private $_controller = null;

    /**
     * Load the DM2 edit controller instance
     */
    private function _load_datamanager()
    {
        $schemadb = midcom_helper_datamanager2_schema::load_database(midcom::get()->config->get('metadata_schema'));

        $this->_controller = midcom_helper_datamanager2_controller::create('simple');
        $this->_controller->schemadb =& $schemadb;

        $object_schema = midcom_helper_metadata::find_schemaname($schemadb, $this->_object);

        $this->_controller->set_storage($this->_object, $object_schema);

        if (!$this->_controller->initialize()) {
            throw new midcom_error("Failed to initialize a DM2 controller instance for article {$this->_article->id}.");
        }
    }

    /**
     * Handler for folder metadata. Checks for updating permissions, initializes
     * the metadata and the content topic itself. Handles also the sent form.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_metadata($handler_id, array $args, array &$data)
    {
        $this->_object = midcom::get()->dbfactory->get_object_by_guid($args[0]);
        $this->_object->require_do('midgard:update');

        if (is_a($this->_object, 'midcom_db_topic')) {
            $this->_object->require_do('midcom.admin.folder:topic_management');
        }

        // Load the DM2 controller instance
        $this->_load_datamanager();

        $object_label = midcom_helper_reflector::get($this->_object)->get_object_label($this->_object);
        midcom::get()->head->set_pagetitle(sprintf($this->_l10n->get('edit metadata of %s'), $object_label));

        $workflow = $this->get_workflow('datamanager2', [
            'controller' => $this->_controller,
            'save_callback' => [$this, 'save_callback']
        ]);
        return $workflow->run();
    }

    public function save_callback(midcom_helper_datamanager2_controller $controller)
    {
        midcom::get()->cache->invalidate($this->_object->guid);
    }
}
