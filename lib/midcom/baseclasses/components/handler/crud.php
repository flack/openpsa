<?php
/**
 * @package midcom.baseclasses
 * @author The Midgard Project, http://www.midgard-project.org
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
     */
    protected $_content_topic = null;

    /**
     * The DBA class to use
     *
     * @var string
     */
    protected $_dba_class = null;

    /**
     * The prefix to use for style element names and URLs, if any
     *
     * @var string
     */
    protected $_prefix = null;

    /**
     * The mode (Create, Read, Update, Delete) we're in
     *
     * @var string
     */
    protected $_mode = null;

    /**
     * The object to operate on
     */
    protected $_object = null;

    /**
     * The parent of an object to be created
     */
    protected $_parent = null;

    /**
     * Datamanager2 to be used for displaying an object used for delete preview
     *
     * @var midcom_helper_datamanager2_datamanager
     */
    protected $_datamanager = null;

    /**
     * The Datamanager2 controller of the object used for editing
     *
     * @var midcom_helper_datamanager2_controller_simple
     */
    protected $_controller = null;

    /**
     * The schema database in use, available only while a datamanager is loaded.
     *
     * @var array
     */
    protected $_schemadb = null;

    /**
     * The schema name to use when creating new objects
     *
     * @var string
     */
    protected $_schema = 'default';

    /**
     * Default values to be given to creation controller.
     *
     * @var array
     */
    protected $_defaults = array();

    /**
     *
     * @var array
     */
    private $args = array();

    /**
     * Load an object, should be implemented in the component handler.
     *
     * The method will generate an error if the object could not be found.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _load_object($handler_id, array $args, array &$data)
    {
        $this->_object = new $this->_dba_class($args[0]);
    }

    /**
     * Load parent object for an object that is to be created, must be
     * implemented in the component handler.
     *
     * The method will generate an error if the object could not be found.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _load_parent($handler_id, array $args, array &$data)
    {
        return;
    }

    /**
     * Add or update an object to the MidCOM indexer service.
     *
     * @param &$dm Datamanager2 instance containing the object
     * @todo implement this properly now it's just a stub
     */
    public function _index_object(&$dm)
    {
        return;
        $indexer = midcom::get()->indexer;
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
     * Get URL to an object.
     *
     * <b>Note</b>: This implementation uses MidCOM's permalink resolution service for generating the
     * link, which is slow. Overriding this with a local implementation is recommended.
     *
     * @param midcom_core_dbaobject $object The object in question
     * @return string URL to the current object
     */
    public function _get_object_url(midcom_core_dbaobject $object)
    {
        return midcom::get()->permalinks->resolve_permalink($object->guid);
    }

    /**
     * Convert the user's prefix (if any) into a URL prefix
     *
     * @return string URL prefix to the current object
     */
    public function _get_url_prefix()
    {
        if (empty($this->_prefix)) {
            return '';
        }
        return $this->_prefix . '/';
    }

    /**
     * Convert the user's prefix (if any) into a style name prefix
     *
     * @return string stylename prefix
     */
    private function _get_style_prefix()
    {
        if (empty($this->_prefix)) {
            return '';
        }
        return $this->_prefix . '-';
    }

    /**
     * Update breadcrumb for current object and handler
     *
     * @param mixed $handler_id The ID of the handler.
     */
    abstract public function _update_breadcrumb($handler_id);

    /**
     * Update title for current object and handler, should be implemented in the
     * component handler for better performance.
     *
     * @param mixed $handler_id The ID of the handler.
     */
    public function _update_title($handler_id)
    {
        $ref = midcom_helper_reflector::get($this->_dba_class);
        $view_title = '';
        switch ($this->_mode) {
            case 'create':
                $view_title = sprintf($this->_l10n_midcom->get('create %s'), $ref->get_class_label());
                break;
            case 'read':
                $object_title = $ref->get_object_label($this->_object);
                $view_title = $object_title;
                break;
            case 'update':
                $object_title = $ref->get_object_label($this->_object);
                $view_title = sprintf($this->_l10n_midcom->get('edit %s'), $object_title);
                break;
        }

        midcom::get()->head->set_pagetitle($view_title);
    }

    /**
     * Add the supported operations into the toolbar.
     *
     * @param mixed $handler_id The ID of the handler.
     */
    public function _populate_toolbar($handler_id)
    {
        $prefix = $this->_get_url_prefix();
        $buttons = array();
        if (   $this->_mode !== 'update'
            && $this->_object->can_do('midgard:update')) {
            $workflow = $this->get_workflow('datamanager2');
            $buttons[] = $workflow->get_button($prefix . "edit/{$this->_object->guid}/", array
            (
                MIDCOM_TOOLBAR_ACCESSKEY => 'e',
            ));
        }

        if (   $this->_mode !== 'delete'
            && $this->_object->can_do('midgard:delete')) {
            $workflow = $this->get_workflow('delete', array('object' => $this->_object));
            $buttons[] = $workflow->get_button($prefix . "delete/{$this->_object->guid}/");
        }
        $this->_view_toolbar->add_items($buttons);
    }

    /**
     * Simple helper which references all important members to the request data listing
     * for usage within the style listing.
     */
    function _prepare_request_data()
    {
        $this->_request_data['object'] = $this->_object;
        $this->_request_data['datamanager'] = $this->_datamanager;
        $this->_request_data['controller'] = $this->_controller;
    }

    /**
     * Maps the content topic from the request data to local member variables.
     *
     * Also makes sure that DM2 is loaded
     */
    public function _on_initialize()
    {
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
        if (count($args) == 0) {
            // No arguments, we use "default";
            return;
        }

        if (isset($this->_schemadb[$args[0]])) {
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
     */
    public function _load_datamanager()
    {
        if (!$this->_object) {
            throw new midcom_error_notfound("No object defined for DM2.");
        }

        $this->_datamanager = new midcom_helper_datamanager2_datamanager($this->_schemadb);

        if (!$this->_datamanager->autoset_storage($this->_object)) {
            throw new midcom_error("Failed to create a DM2 instance for object {$this->_object->guid}.");
        }
    }

    /**
     * Internal helper, loads the controller for the current object. Any error triggers a 500.
     *
     * @param string $type Controller to instantiate (typically 'simple' or 'ajax')
     */
    public function _load_controller($type = 'simple')
    {
        $this->_controller = midcom_helper_datamanager2_controller::create($type);
        $this->_controller->schemadb =& $this->_schemadb;

        if ($type == 'create') {
            // Creation controller, give additional parameters
            $this->_controller->schemaname = $this->_schema;
            $this->_controller->defaults = $this->_defaults;
            $this->_controller->callback_object =& $this;
        } else {
            if (!$this->_object) {
                throw new midcom_error_notfound("No object defined for DM2.");
            }

            $this->_controller->set_storage($this->_object);
        }

        if (!$this->_controller->initialize()) {
            throw new midcom_error("Failed to initialize a DM2 controller instance for object {$this->_object->guid}.");
        }
    }

    /**
     * DM2 creation callback.
     *
     * @param $controller DM2 creation controller
     */
    public function &dm2_create_callback(&$controller)
    {
        throw new midcom_error("The creation callback has to be implemented in the child class");
    }

    /**
     * Helper function to extend the baseclass handlers
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_callback($handler_id, array $args, array &$data)
    {
    }

    /**
     * Generates an object creation view.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_create($handler_id, array $args, array &$data)
    {
        $this->_mode = 'create';
        $this->_load_parent($handler_id, $args, $data);
        if ($this->_parent) {
            // Parent object available, check permissions
            $this->_parent->require_do('midgard:create');
        } else {
            midcom::get()->auth->require_user_do('midgard:create', null, $this->_dba_class);
        }

        // Select schema name to use based on arguments
        $this->_load_schemadb();
        $this->_select_schema($args);

        // Prepare defaults
        $this->_load_defaults();

        // Prepare the creation controller
        $this->_load_controller('create');

        $this->_update_title($handler_id);
        $this->args = $args;
        $workflow = $this->get_workflow('datamanager2', array
        (
            'controller' => $this->_controller,
            'save_callback' => array($this, 'save_callback')
        ));
        return $workflow->run();
    }

    public function save_callback(midcom_helper_datamanager2_controller $controller)
    {
        $this->_index_object($controller->datamanager);
        $this->_handler_callback($this->_request_data['handler_id'], $this->args, $this->_request_data);
        if ($this->_mode !== 'update') {
            return $this->_get_object_url($this->_object);
        }
    }

    /**
     * Generates an object view.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_read($handler_id, array $args, array &$data)
    {
        $this->_mode = 'read';
        $this->_load_object($handler_id, $args, $data);

        $this->_load_schemadb();
        if (midcom::get()->config->get('enable_ajax_editing')) {
            // AJAX editing is possible
            $this->_load_controller('ajax');
            $this->_controller->process_ajax();
            $this->_datamanager =& $this->_controller->datamanager;
        } else {
            $this->_load_datamanager();
        }

        $this->_prepare_request_data();

        if ($this->_controller) {
            // For AJAX handling it is the controller that renders everything
            $this->_request_data['object_view'] = $this->_controller->get_content_html();
        } else {
            $this->_request_data['object_view'] = $this->_datamanager->get_content_html();
        }

        // Call the per-component metadata methods
        $this->_populate_toolbar($handler_id);
        $this->_update_title($handler_id);
        $this->_update_breadcrumb($handler_id);

        // Let MidCOM know about the object
        midcom::get()->metadata->set_request_metadata($this->_object->metadata->revised, $this->_object->guid);
        $this->bind_view_to_object($this->_object, $this->_datamanager->schema->name);

        $this->_handler_callback($handler_id, $args, $data);
    }

    /**
     * Shows the loaded object.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_read($handler_id, array &$data)
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
     */
    public function _handler_update($handler_id, array $args, array &$data)
    {
        $this->_mode = 'update';
        $this->_load_object($handler_id, $args, $data);

        $this->_object->require_do('midgard:update');

        $this->_load_schemadb();
        $this->_load_controller();
        $this->_update_title($handler_id);
        $this->args = $args;
        $data['controller'] = $this->_controller;
        $workflow = $this->get_workflow('datamanager2', array
        (
            'controller' => $this->_controller,
            'save_callback' => array($this, 'save_callback')
        ));
        return $workflow->run();
    }

    /**
     * Displays an object delete confirmation view.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_delete($handler_id, array $args, array &$data)
    {
        $this->_mode = 'delete';
        $this->_load_object($handler_id, $args, $data);

        $options = array('object' => $this->_object);
        $this->_load_parent($handler_id, $args, $data);
        if ($this->_parent) {
            $options['success_url'] = $this->_get_object_url($this->_parent);
        }
        $workflow = $this->get_workflow('delete', $options);
        return $workflow->run();
    }
}
