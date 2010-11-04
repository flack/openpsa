<?php
/**
 * @package midcom.admin.folder
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: metadata.php 25746 2010-04-23 07:25:06Z jval $
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
     * @access private
     * @var mixed Object for metadata editing
     */
    var $_object = null;

    /**
     * Edit controller instance for Datamanager 2
     *
     * @access private
     * @var midcom_helper_datamanager2_controller
     */
    var $_controller = null;

    /**
     * Datamanager 2 schema instance
     *
     * @access private
     * @var midcom_helper_datamanager2_schema
     */
    var $_schemadb = null;

    /**
     * Constructor, call for the class parent constructor method.
     *
     * @access public
     */
    function __construct()
    {
        parent::__construct();
    }

    /**
     * Get the object title of the content topic.
     *
     * @return string containing the content topic title
     */
    function _get_object_title(&$object)
    {
        $this->_request_data['object_reflector'] = midcom_helper_reflector::get($object);
        return $this->_request_data['object_reflector']->get_object_label($object);
    }

    /**
     * Load the DM2 edit controller instance
     *
     * @access private
     * @return boolean Indicating success of DM2 edit controller instance
     */
    function _load_datamanager()
    {
        $this->_schemadb = midcom_helper_datamanager2_schema::load_database($GLOBALS['midcom_config']['metadata_schema']);

        $this->_controller = midcom_helper_datamanager2_controller::create('simple');
        $this->_controller->schemadb =& $this->_schemadb;

        // Check if we have metadata schema defined in the schemadb specific for the object's schema or component
        $object_schema = $this->_object->get_parameter('midcom.helper.datamanager2', 'schema_name');
        $component_schema = str_replace('.', '_', $_MIDCOM->get_context_data(MIDCOM_CONTEXT_COMPONENT));
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
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Failed to initialize a DM2 controller instance for article {$this->_article->id}.");
            // This will exit.
        }
    }

    /**
     * Handler for folder metadata. Checks for updating permissions, initializes
     * the metadata and the content topic itself. Handles also the sent form.
     *
     * @access private
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success
     */
    function _handler_metadata($handler_id, $args, &$data)
    {
        debug_push_class(__CLASS__, __FUNCTION__);

        $this->_object = $_MIDCOM->dbfactory->get_object_by_guid($args[0]);
        if (! $this->_object)
        {
            debug_add("Object with GUID '{$args[0]}' was not found!", MIDCOM_LOG_ERROR);
            debug_pop();

            $_MIDCOM->generate_error(MIDCOM_ERRNOTFOUND, "The GUID '{$args[0]}' was not found.");
            // This will exit.
        }

        // FIXME: We should modify the schema according to whether or not scheduling is used
        $this->_object->require_do('midgard:update');

        if (is_a($this->_object, 'midcom_baseclasses_database_topic'))
        {
            // This is a topic
            $this->_object->require_do('midcom.admin.folder:topic_management');
        }
        else
        {
            // This is a regular object, bind to view
            $_MIDCOM->bind_view_to_object($this->_object);
        }

        $this->_metadata = midcom_helper_metadata::retrieve($this->_object);

        if (! $this->_metadata)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                "Failed to retrieve Metadata for " . get_class($object) . " {$object->guid}.");
            // This will exit.
        }

        // Load the DM2 controller instance
        $this->_load_datamanager();

        switch ($this->_controller->process_form())
        {
            case 'save':
                $_MIDCOM->cache->invalidate($this->_object->guid);
            case 'cancel':
                $_MIDCOM->relocate('');
                // This will exit
        }

        $tmp = array();

        if (is_a($this->_object, 'midcom_baseclasses_database_topic'))
        {
            $this->_node_toolbar->hide_item("__ais/folder/metadata/{$this->_object->guid}/");
        }
        else
        {
            $tmp[] = array
            (
                MIDCOM_NAV_URL => $_MIDCOM->permalinks->create_permalink($this->_object->guid),
                MIDCOM_NAV_NAME => $this->_get_object_title($this->_object),
            );
            $this->_view_toolbar->hide_item("__ais/folder/metadata/{$this->_object->guid}/");
        }

        $tmp[] = Array
        (
            MIDCOM_NAV_URL => "__ais/folder/metadata/{$this->_object->guid}/",
            MIDCOM_NAV_NAME => $_MIDCOM->i18n->get_string('edit metadata', 'midcom.admin.folder'),
        );
        $_MIDCOM->set_custom_context_data('midcom.helper.nav.breadcrumb', $tmp);

        $data['title'] = sprintf($_MIDCOM->i18n->get_string('edit metadata of %s', 'midcom.admin.folder'), $this->_get_object_title($this->_object));
        $_MIDCOM->set_pagetitle($data['title']);

        // Set the help object in the toolbar
        $help_toolbar = $_MIDCOM->toolbars->get_help_toolbar();
        $help_toolbar->add_help_item('edit_metadata', 'midcom.admin.folder', null, null, 1);

        // Ensure we get the correct styles
        $_MIDCOM->style->prepend_component_styledir('midcom.admin.folder');

        return true;
    }

    /**
     * Output the style element for metadata editing
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     * @access private
     */
    function _show_metadata($handler_id, &$data)
    {
        // Bind object details to the request data
        $data['controller'] =& $this->_controller;
        $data['object'] =& $this->_object;

        if (   is_a($this->_object, 'midcom_baseclasses_database_topic')
            && !empty($this->_object->symlink))
        {
            $topic = new midcom_db_topic($this->_object->symlink);
            if ($topic && $topic->guid)
            {
                $data['symlink'] = '';
                $nap = new midcom_helper_nav();
                if ($node = $nap->get_node($topic))
                {
                    $data['symlink'] = $node[MIDCOM_NAV_FULLURL];
                }
            }
            else
            {
                debug_push_class(__CLASS__, __FUNCTION__);
                debug_add("Could not get target for symlinked topic #{$this->_object->id}: " .
                    midcom_application::get_error_string(), MIDCOM_LOG_ERROR);
                debug_pop();
            }
        }

        midcom_show_style('midcom-admin-show-folder-metadata');
    }

}
?>
