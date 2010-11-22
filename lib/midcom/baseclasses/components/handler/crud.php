<?php
/**
 * @package midcom.baseclasses
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: crud.php 26725 2010-11-02 21:57:06Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Generic Create-Read-Update-Delete handler baseclass
 *
 * @package midcom.baseclasses
 */
abstract class midcom_baseclasses_components_handler_crud extends midcom_baseclasses_components_handler
{
    /**
     * The content topic to use
     *
     * @var midcom_db_topic
     * @access private
     */
    var $_content_topic = null;

    /**
     * The DBA class to use
     *
     * @var string
     * @access private
     */
    var $_dba_class = null;

    /**
     * The prefix to use for style element names and URLs, if any
     *
     * @var string
     * @access private
     */
    var $_prefix = null;

    /**
     * The mode (Create, Read, Update, Delete) we're in
     *
     * @var string
     * @access private
     */
    var $_mode = null;

    /**
     * The object to operate on
     *
     * @access private
     */
    var $_object = null;

    /**
     * The parent of an object to be created
     *
     * @access private
     */
    var $_parent = null;

    /**
     * Datamanager2 to be used for displaying an object used for delete preview
     *
     * @var midcom_helper_datamanager2_datamanager
     * @access private
     */
    var $_datamanager = null;

    /**
     * The Datamanager2 controller of the object used for editing
     *
     * @var midcom_helper_datamanager2_controller_simple
     * @access private
     */
    var $_controller = null;

    /**
     * The schema database in use, available only while a datamanager is loaded.
     *
     * @var array
     * @access private
     */
    var $_schemadb = null;

    /**
     * The schema name to use when creating new objects
     *
     * @var string
     * @access private
     */
    var $_schema = 'default';

    /**
     * Default values to be given to creation controller.
     *
     * @var array
     * @access private
     */
    var $_defaults = array();

    /**
     * Method for loading an object, must be implemented in the component handler.
     *
     * The method will generate an error if the object could not be found.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _load_object($handler_id, $args, &$data)
    {
        $this->_object = new $this->_dba_class($args[0]);
        if (   !$this->_object
            || !$this->_object->guid)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRNOTFOUND, "The object with GUID {$args[0]} was not found.");
            // This will exit.
        }
    }

    /**
     * Method for loading parent object for an object that is to be created, must be
     * implemented in the component handler.
     *
     * The method will generate an error if the object could not be found.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _load_parent($handler_id, $args, &$data)
    {
        return;
    }

    /**
     * Method for adding or updating an object to the MidCOM indexer service.
     *
     * @param $dm Datamanager2 instance containing the object
     * @todo implement this properly now it's just a stub
     */
    public function _index_object(&$dm)
    {
        return;
        $indexer = $_MIDCOM->get_service('indexer');
        $topic =& $this->_content_topic;

        $nav = new midcom_helper_nav();
        $node = $nav->get_node($topic->id);

        $document = $indexer->new_document($dm);
        $document->topic_guid = $topic->guid;
        $document->topic_url = $node[MIDCOM_NAV_FULLURL];
        $document->read_metadata_from_object($dm->storage->object);
        $document->component = $topic->component;
        $indexer->index($document);
    }

    /**
     * Method for getting URL to the current object.
     *
     * <b>Note</b>: This implementation uses MidCOM's permalink resolution service for generating the
     * link, which is slow. Overriding this with a local implementation is recommended.
     *
     * @return string URL to the current object
     */
    public function _get_object_url()
    {
        return $_MIDCOM->permalinks->resolve_permalink($this->_object->guid);
    }

    /**
     * Helper function to convert the user's prefix (if any) into a URL prefix
     *
     * @return string URL prefix to the current object
     */
    public function _get_url_prefix()
    {
        $prefix = "";
        if (!empty($this->_prefix))
        {
            $prefix = $this->_prefix . '/';
        }
        return $prefix;
    }

    /**
     * Helper function to convert the user's prefix (if any) into a style name prefix
     *
     * @return string stylename prefix
     */
    private function _get_style_prefix()
    {
        $prefix = "";
        if (!empty($this->_prefix))
        {
            $prefix = $this->_prefix . '-';
        }
        return $prefix;
    }

    /**
     * Method for updating breadcrumb for current object and handler, must be implemented in the
     * component handler.
     *
     * @param mixed $handler_id The ID of the handler.
     */
    abstract public function _update_breadcrumb($handler_id);

    /**
     * Method for updating title for current object and handler, should be implemented in the
     * component handler for better performance.
     *
     * @param mixed $handler_id The ID of the handler.
     */
    public function _update_title($handler_id)
    {
        $ref = midcom_helper_reflector::get($this->_dba_class);
        $view_title = $this->_topic->extra . ': ';
        switch ($this->_mode)
        {
            case 'create':
                $view_title = sprintf($this->_l10n_midcom->get('create %s'), $ref->get_class_label());
                break;
            case 'read':
                $object_title = $ref->get_object_label($this->_object);
                $view_title .= $object_title;
                break;
            case 'update':
                $object_title = $ref->get_object_label($this->_object);
                $view_title .= sprintf($this->_l10n_midcom->get('edit %s'), $object_title);
                break;
            case 'delete':
                $object_title = $ref->get_object_label($this->_object);
                $view_title .= sprintf($this->_l10n_midcom->get('delete %s'), $object_title);
                break;
        }

        $_MIDCOM->set_pagetitle($view_title);
    }

    /**
     * Method for adding the supported operations into the toolbar.
     *
     * @param mixed $handler_id The ID of the handler.
     */
    public function _populate_toolbar($handler_id)
    {
        $prefix = $this->_get_url_prefix();

        if (   !$this->_object
            || !$this->_object->guid)
        {
            return;
        }

        if ($this->_object->can_do('midgard:update'))
        {
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => $prefix . "edit/{$this->_object->guid}/",
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('edit'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/edit.png',
                    MIDCOM_TOOLBAR_ACCESSKEY => 'e',
                )
            );
        }

        if ($this->_object->can_do('midgard:delete'))
        {
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => $prefix . "delete/{$this->_object->guid}/",
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('delete'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/trash.png',
                    MIDCOM_TOOLBAR_ACCESSKEY => 'd',
                )
            );
        }
    }

    /**
     * Simple helper which references all important members to the request data listing
     * for usage within the style listing.
     */
    function _prepare_request_data()
    {
        $this->_request_data['object'] =& $this->_object;
        $this->_request_data['datamanager'] =& $this->_datamanager;
        $this->_request_data['controller'] =& $this->_controller;
    }

    /**
     * Maps the content topic from the request data to local member variables.
     *
     * Also makes sure that DM2 is loaded
     */
    function _on_initialize()
    {
        if (!$_MIDCOM->componentloader->is_loaded('midcom.helper.datamanager2'))
        {
            $_MIDCOM->load_library('midcom.helper.datamanager2');
        }
        $this->_content_topic =& $this->_request_data['content_topic'];
    }

    /**
     * Loads and prepares the schema database.
     *
     * The operations are done on all available schemas within the DB.
     */
    public function _load_schemadb()
    {
        $this->_schemadb =& $this->_request_data['schemadb'];
    }

    /**
     * Selects the schema name based on arguments.
     *
     * @param array $args URL arguments
     */
    public function _select_schema($args)
    {
        if (count($args) == 0)
        {
            // No arguments, we use "default";
            return;
        }

        if (isset($this->_schemadb[$args[0]]))
        {
            $this->_schema = $args[0];
        }
    }

    /**
     * Loads default values for the creation controller.
     */
    public function _load_defaults()
    {
    }

    /**
     * Internal helper, loads the datamanager for the current object. Any error triggers a 500.
     *
     * @access private
     */
    public function _load_datamanager()
    {
        if (!$this->_object)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRNOTFOUND, "No object defined for DM2.");
            // This will exit.
        }

        $this->_datamanager = new midcom_helper_datamanager2_datamanager($this->_schemadb);

        if (   !$this->_datamanager
            || !$this->_datamanager->autoset_storage($this->_object))
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Failed to create a DM2 instance for object {$this->_object->guid}.");
            // This will exit.
        }
    }

    /**
     * Internal helper, loads the controller for the current object. Any error triggers a 500.
     *
     * @param string $type Controller to instantiate (typically 'simple' or 'ajax')
     * @access private
     */
    public function _load_controller($type = 'simple')
    {
        $this->_controller = midcom_helper_datamanager2_controller::create($type);
        $this->_controller->schemadb =& $this->_schemadb;

        if ($type == 'create')
        {
            // Creation controller, give additional parameters
            $this->_controller->schemaname = $this->_schema;
            $this->_controller->defaults = $this->_defaults;
            $this->_controller->callback_object =& $this;
        }
        else
        {
            if (!$this->_object)
            {
                $_MIDCOM->generate_error(MIDCOM_ERRNOTFOUND, "No object defined for DM2.");
                // This will exit.
            }

            $this->_controller->set_storage($this->_object);
        }

        if (!$this->_controller->initialize())
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Failed to initialize a DM2 controller instance for object {$this->_object->guid}.");
            // This will exit.
        }
    }

    /**
     * DM2 creation callback.
     *
     * @param $controller DM2 creation controller
     */
    function &dm2_create_callback(&$controller)
    {
        echo "The creation callback has to be implemented in the child class";
        _midcom_stop_request();
    }

    /**
     * Helper function to extend the baseclass handlers
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     * @return boolean Indicating success.
     */
    public function _handler_callback($handler_id, $args, &$data)
    {
        return true;
    }

    /**
     * Generates an object creation view.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_create($handler_id, $args, &$data)
    {
        $this->_mode = 'create';
        $this->_load_parent($handler_id, $args, $data);
        if ($this->_parent)
        {
            // Parent object available, check permissions
            $this->_parent->require_do('midgard:create');
        }
        else
        {
            $_MIDCOM->auth->require_user_do('midgard:create', null, $this->_dba_class);
        }

        // Select schema name to use based on arguments
        $this->_load_schemadb();
        $this->_select_schema($args);

        // Prepare defaults
        $this->_load_defaults();

        // Prepare the creation controller
        $this->_load_controller('create');

        switch ($this->_controller->process_form())
        {
            case 'save':
                $this->_index_object($this->_controller->datamanager);

                $_MIDCOM->relocate($this->_get_object_url());
                // This will exit.
            case 'cancel':
                // Redirect to parent page, if any.
                if ($this->_parent)
                {
                    $_MIDCOM->relocate($_MIDCOM->permalinks->resolve_permalink($this->_parent->guid));
                }
                // If nothing helps, try the topic's front page
                else
                {
                    $_MIDCOM->relocate($_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX));
                    // This will exit.
                }
        }

        $this->_prepare_request_data();

        // Call the per-component metadata methods
        $this->_populate_toolbar($handler_id);
        $this->_update_title($handler_id);
        $this->_update_breadcrumb($handler_id);

        if ($this->_object)
        {
            // Let MidCOM know about the object
            $_MIDCOM->set_26_request_metadata($this->_object->metadata->revised, $this->_object->guid);
            $this->_view_toolbar->bind_to($this->_object);
        }

        return $this->_handler_callback($handler_id, $args, $data);
    }

    /**
     * Shows the object creation form.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    function _show_create($handler_id, &$data)
    {
        $prefix = $this->_get_style_prefix();
        midcom_show_style($prefix . 'admin-create');
    }

    /**
     * Generates an object view.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_read($handler_id, $args, &$data)
    {
        $this->_mode = 'read';
        $this->_load_object($handler_id, $args, $data);

        $this->_load_schemadb();
        if ($GLOBALS['midcom_config']['enable_ajax_editing'])
        {
            // AJAX editing is possible
            $this->_load_controller('ajax');
            $this->_controller->process_ajax();
            $this->_datamanager =& $this->_controller->datamanager;
        }
        else
        {
            $this->_load_datamanager();
        }

        $this->_prepare_request_data();

        if ($this->_controller)
        {
            // For AJAX handling it is the controller that renders everything
            $this->_request_data['object_view'] = $this->_controller->get_content_html();
        }
        else
        {
            $this->_request_data['object_view'] = $this->_datamanager->get_content_html();
        }

        // Call the per-component metadata methods
        $this->_populate_toolbar($handler_id);
        $this->_update_title($handler_id);
        $this->_update_breadcrumb($handler_id);

        // Let MidCOM know about the object
        $_MIDCOM->set_26_request_metadata($this->_object->metadata->revised, $this->_object->guid);
        $_MIDCOM->bind_view_to_object($this->_object, $this->_datamanager->schema->name);
        $this->_view_toolbar->bind_to($this->_object);

        return $this->_handler_callback($handler_id, $args, $data);
    }

    /**
     * Shows the loaded object.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    function _show_read($handler_id, &$data)
    {
        $prefix = $this->_get_style_prefix();
        midcom_show_style($prefix . 'admin-read');
    }

    /**
     * Generates an object update view.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_update($handler_id, $args, &$data)
    {
        $this->_mode = 'update';
        $this->_load_object($handler_id, $args, $data);

        $this->_object->require_do('midgard:update');

        $this->_load_schemadb();
        $this->_load_controller();

        switch ($this->_controller->process_form())
        {
            case 'save':
                $this->_index_object($this->_controller->datamanager);
                // *** FALL-THROUGH ***

            case 'cancel':
                // Redirect to view page.
                $_MIDCOM->relocate($this->_get_object_url());
                // This will exit.
        }

        $this->_prepare_request_data();

        // Call the per-component metadata methods
        $this->_populate_toolbar($handler_id);
        $this->_update_title($handler_id);
        $this->_update_breadcrumb($handler_id);

        // Let MidCOM know about the object
        $_MIDCOM->set_26_request_metadata($this->_object->metadata->revised, $this->_object->guid);
        $_MIDCOM->bind_view_to_object($this->_object, $this->_controller->datamanager->schema->name);
        $this->_view_toolbar->bind_to($this->_object);

        return $this->_handler_callback($handler_id, $args, $data);
    }

    /**
     * Shows the loaded object in editor.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    function _show_update($handler_id, &$data)
    {
        $prefix = $this->_get_style_prefix();
        midcom_show_style($prefix . 'admin-update');
    }

    /**
     * Displays an object delete confirmation view.
     *
     * Note, that the object for non-index mode operation is automatically determined in the can_handle
     * phase.
     *
     * If create privileges apply, we relocate to the index creation object
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_delete($handler_id, $args, &$data)
    {
        $this->_mode = 'delete';
        $this->_load_object($handler_id, $args, $data);

        $this->_object->require_do('midgard:delete');

        $this->_load_schemadb();
        $this->_load_datamanager();

        if (array_key_exists('midcom_baseclasses_components_handler_crud_deleteok', $_POST))
        {
            // Deletion confirmed, try doing it.
            if (!$this->_object->delete())
            {
                $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Failed to delete object {$this->_object->guid}, last Midgard error was: " . midcom_connection::get_error_string());
                // This will exit.
            }

            // Update the index
            $indexer = $_MIDCOM->get_service('indexer');
            $indexer->delete($this->_object->guid);

            // Show user interface message
            // $_MIDCOM->uimessages->add($this->_l10n->get('net.nehmer.blog'), sprintf($this->_l10n->get('object %s deleted'), $title));

            // Delete ok, relocating to welcome.
            $_MIDCOM->relocate('');
            // This will exit.
        }

        if (array_key_exists('midcom_baseclasses_components_handler_crud_deletecancel', $_REQUEST))
        {
            //$_MIDCOM->uimessages->add($this->_l10n->get('net.nehmer.blog'), $this->_l10n->get('delete cancelled'));

            // Redirect to view page.
            $_MIDCOM->relocate($this->_get_object_url());
            // This will exit
        }

        $this->_prepare_request_data();

        // Call the per-component metadata methods
        $this->_populate_toolbar($handler_id);
        $this->_update_title($handler_id);
        $this->_update_breadcrumb($handler_id);

        // Let MidCOM know about the object
        $_MIDCOM->set_26_request_metadata($this->_object->metadata->revised, $this->_object->guid);
        $_MIDCOM->bind_view_to_object($this->_object, $this->_datamanager->schema->name);
        $this->_view_toolbar->bind_to($this->_object);

        return $this->_handler_callback($handler_id, $args, $data);
    }

    /**
     * Shows a delete dialog with an object preview.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    public function _show_delete($handler_id, &$data)
    {
        $prefix = $this->_get_style_prefix();
        $data['object_view'] = $this->_datamanager->get_content_html();
        midcom_show_style($prefix . 'admin-delete');
    }
}
?>
