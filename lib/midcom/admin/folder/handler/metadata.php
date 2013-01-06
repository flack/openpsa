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
     * Datamanager 2 schema instance
     *
     * @var midcom_helper_datamanager2_schema
     */
    private $_schemadb = null;

    /**
     * Load the DM2 edit controller instance
     *
     * @return boolean Indicating success of DM2 edit controller instance
     */
    private function _load_datamanager()
    {
        $this->_schemadb = midcom_helper_datamanager2_schema::load_database($GLOBALS['midcom_config']['metadata_schema']);

        $this->_controller = midcom_helper_datamanager2_controller::create('simple');
        $this->_controller->schemadb =& $this->_schemadb;

        // Check if we have metadata schema defined in the schemadb specific for the object's schema or component
        $object_schema = $this->_object->get_parameter('midcom.helper.datamanager2', 'schema_name');
        $component_schema = str_replace('.', '_', midcom_core_context::get()->get_key(MIDCOM_CONTEXT_COMPONENT));
        if (   $object_schema == ''
            || !isset($this->_schemadb[$object_schema]))
        {
            if (isset($this->_schemadb[$component_schema]))
            {
                // No specific metadata schema for object, fall back to component-specific metadata schema
                $object_schema = $component_schema;
            }
            else
            {
                // No metadata schema for component, fall back to default
                $object_schema = 'metadata';
            }
        }

        $this->_controller->set_storage($this->_object, $object_schema);


        if (! $this->_controller->initialize())
        {
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
     * @return boolean Indicating success
     */
    public function _handler_metadata($handler_id, array $args, array &$data)
    {
        $this->_object = midcom::get('dbfactory')->get_object_by_guid($args[0]);

        // FIXME: We should modify the schema according to whether or not scheduling is used
        $this->_object->require_do('midgard:update');

        if (is_a($this->_object, 'midcom_db_topic'))
        {
            // This is a topic
            $this->_object->require_do('midcom.admin.folder:topic_management');
        }
        else
        {
            // This is a regular object, bind to view
            $this->bind_view_to_object($this->_object);
        }

        $this->_metadata = midcom_helper_metadata::retrieve($this->_object);

        if (! $this->_metadata)
        {
            throw new midcom_error("Failed to retrieve Metadata for " . get_class($this->_object) . " {$this->_object->guid}.");
        }

        // Load the DM2 controller instance
        $this->_load_datamanager();

        switch ($this->_controller->process_form())
        {
            case 'save':
                midcom::get('cache')->invalidate($this->_object->guid);
            case 'cancel':
                return new midcom_response_relocate(midcom::get('permalinks')->create_permalink($this->_object->guid));
        }

        $object_label = midcom_helper_reflector::get($this->_object)->get_object_label($this->_object);

        if (is_a($this->_object, 'midcom_db_topic'))
        {
            $this->_node_toolbar->hide_item("__ais/folder/metadata/{$this->_object->guid}/");
        }
        else
        {
            $this->add_breadcrumb(midcom::get('permalinks')->create_permalink($this->_object->guid), $object_label);
            $this->_view_toolbar->hide_item("__ais/folder/metadata/{$this->_object->guid}/");
        }

        $this->add_breadcrumb("__ais/folder/metadata/{$this->_object->guid}/", $this->_l10n->get('edit metadata'));

        $data['title'] = sprintf($this->_l10n->get('edit metadata of %s'), $object_label);
        midcom::get('head')->set_pagetitle($data['title']);

        // Set the help object in the toolbar
        $help_toolbar = midcom::get('toolbars')->get_help_toolbar();
        $help_toolbar->add_help_item('edit_metadata', 'midcom.admin.folder', null, null, 1);

        // Ensure we get the correct styles
        midcom::get('style')->prepend_component_styledir('midcom.admin.folder');
    }

    /**
     * Output the style element for metadata editing
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_metadata($handler_id, array &$data)
    {
        // Bind object details to the request data
        $data['controller'] =& $this->_controller;
        $data['object'] =& $this->_object;

        if (   is_a($this->_object, 'midcom_db_topic')
            && !empty($this->_object->symlink))
        {
            try
            {
                $topic = new midcom_db_topic($this->_object->symlink);
                $data['symlink'] = '';
                $nap = new midcom_helper_nav();
                if ($node = $nap->get_node($topic))
                {
                    $data['symlink'] = $node[MIDCOM_NAV_FULLURL];
                }
            }
            catch (midcom_error $e)
            {
                debug_add("Could not get target for symlinked topic #{$this->_object->id}: " .
                    $e->getMessage(), MIDCOM_LOG_ERROR);
            }
        }

        midcom_show_style('midcom-admin-show-folder-metadata');
    }
}
?>
