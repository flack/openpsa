<?php
/**
 * @package midgard.admin.asgard
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;
use midcom\datamanager\controller;
use midcom\datamanager\schemadb;
use Symfony\Component\HttpFoundation\Request;

/**
 * Object management interface
 *
 * @package midgard.admin.asgard
 */
class midgard_admin_asgard_handler_object_manage extends midcom_baseclasses_components_handler
{
    /**
     * Some object
     *
     * @var midcom_core_dbaobject
     */
    private $_object;

    /**
     * Some newly created object
     *
     * @var midcom_core_dbaobject
     */
    private $_new_object;

    /**
     * The Datamanager of the object to display.
     *
     * @var datamanager
     */
    private $datamanager;

    /**
     * The Controller of the object used for editing
     *
     * @var controller
     */
    private $controller;

    /**
     * The schema database in use, available only while a datamanager is loaded.
     *
     * @var schemadb
     */
    private $schemadb;

    /**
     * Retrieve the object from the db
     *
     * @param string $guid GUID
     */
    private function _load_object($guid)
    {
        try {
            $this->_object = midcom::get()->dbfactory->get_object_by_guid($guid);
        } catch (midcom_error $e) {
            if (midcom_connection::get_error() == MGD_ERR_OBJECT_DELETED) {
                $relocate = $this->router->generate('object_deleted', ['guid' => $guid]);
                midcom::get()->relocate($relocate);
            }

            throw $e;
        }
    }

    /**
     * Simple helper which references all important members to the request data listing
     * for usage within the style listing.
     */
    private function _prepare_request_data()
    {
        $this->_request_data['object'] = $this->_object;
        $this->_request_data['controller'] = $this->controller;
        $this->_request_data['datamanager'] = $this->datamanager;
        $this->_request_data['asgard_prefix'] = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX) . '__mfa/asgard/';
        $this->_request_data['style_helper'] = new midgard_admin_asgard_stylehelper($this->_request_data);
    }

    /**
     * Loads the schemadb from the helper class
     */
    private function _load_schemadb($type = null, $include_fields = null, $add_copy_fields = false)
    {
        $schema_helper = new midgard_admin_asgard_schemadb($this->_object, $this->_config, $type);
        $schema_helper->add_copy_fields = $add_copy_fields;
        $this->schemadb = $schema_helper->create($include_fields);
    }

    /**
     * Looks up the user's default mode and redirects there. This is mainly useful for links from outside Asgard
     *
     * @param string $guid The object's GUID
     * @param array $data The local request data.
     */
    public function _handler_open($guid, array &$data)
    {
        $relocate = $this->router->generate('object_' . $data['default_mode'], ['guid' => $guid]);
        return new midcom_response_relocate($relocate);
    }

    /**
     * Object display
     *
     * @param mixed $handler_id The ID of the handler.
     * @param string $guid The object's GUID
     * @param array $data The local request data.
     */
    public function _handler_view($handler_id, $guid, array &$data)
    {
        $this->_load_object($guid);

        midcom::get()->auth->require_user_do('midgard.admin.asgard:manage_objects', null, 'midgard_admin_asgard_plugin');

        $this->_load_schemadb();

        // Hide the revision message
        $field =& $this->schemadb->get_first()->get_field('_rcs_message');
        $field['hidden'] = true;

        $this->datamanager = new datamanager($this->schemadb);
        $this->datamanager
            ->set_storage($this->_object)
            ->get_form(); // currently needed to add head elements

        midgard_admin_asgard_plugin::bind_to_object($this->_object, $handler_id, $data);
        $this->_prepare_request_data();
        return new midgard_admin_asgard_response($this, '_show_view');
    }

    /**
     * Shows the loaded object.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $data The local request data.
     */
    public function _show_view($handler_id, array &$data)
    {
        midcom_show_style('midgard_admin_asgard_object_view');
    }

    /**
     * Object editing view
     *
     * @param Request $request The request object
     * @param mixed $handler_id The ID of the handler.
     * @param string $guid The object's GUID
     * @param array $data The local request data.
     */
    public function _handler_edit(Request $request, $handler_id, $guid, array &$data)
    {
        $this->_load_object($guid);

        $this->_object->require_do('midgard:update');
        midcom::get()->auth->require_user_do('midgard.admin.asgard:manage_objects', null, 'midgard_admin_asgard_plugin');

        $this->_load_schemadb();
        $dm = new datamanager($this->schemadb);
        $this->controller = $dm
            ->set_storage($this->_object, 'default')
            ->get_controller();
        switch ($this->controller->handle($request)) {
            case 'save':
                // Reindex the object
                //$indexer = midcom::get()->indexer;
                //net_nemein_wiki_viewer::index($this->_request_data['controller']->datamanager, $indexer, $this->_topic);
                //Fall-through

            case 'cancel':
                return $this->_prepare_relocate($this->_object);
        }

        $this->_prepare_request_data();
        midgard_admin_asgard_plugin::bind_to_object($this->_object, $handler_id, $data);
        return new midgard_admin_asgard_response($this, '_show_edit');
    }

    /**
     * Shows the loaded object in editor.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $data The local request data.
     */
    public function _show_edit($handler_id, array &$data)
    {
        midcom_show_style('midgard_admin_asgard_object_edit');
    }

    /**
     * Object creating view
     *
     * @param Request $request The request object
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array $data The local request data.
     */
    public function _handler_create(Request $request, $handler_id, array $args, array &$data)
    {
        midcom::get()->auth->require_user_do('midgard.admin.asgard:manage_objects', null, 'midgard_admin_asgard_plugin');

        $data['current_type'] = $args[0];
        $create_type = midcom::get()->dbclassloader->get_midcom_class_name_for_mgdschema_object($data['current_type']);
        if (!$create_type) {
            throw new midcom_error_notfound('Failed to find type for the new object');
        }
        $this->_new_object = new $create_type();

        if (   $handler_id == 'object_create_toplevel'
            || $handler_id == 'object_create_chooser') {
            midcom::get()->auth->require_user_do('midgard:create', null, $create_type);

            $data['view_title'] = sprintf($this->_l10n_midcom->get('create %s'), midgard_admin_asgard_plugin::get_type_label($data['current_type']));
        } else {
            $this->_object = midcom::get()->dbfactory->get_object_by_guid($args[1]);
            $this->_object->require_do('midgard:create');
            midgard_admin_asgard_plugin::bind_to_object($this->_object, $handler_id, $data);
        }

        $this->_load_schemadb($create_type);

        $dm = new datamanager($this->schemadb);
        $this->controller = $dm
            ->set_defaults($this->get_defaults($request, $create_type))
            ->set_storage($this->_new_object, 'default')
            ->get_controller();

        switch ($this->controller->handle($request)) {
            case 'save':
                // Reindex the object
                //$indexer = midcom::get()->indexer;
                //net_nemein_wiki_viewer::index($this->_request_data['controller']->datamanager, $indexer, $this->_topic);

                if ($handler_id !== 'object_create_chooser') {
                    return $this->_prepare_relocate($this->_new_object);
                }
                break;
            case 'cancel':
                $data['cancelled'] = true;
                if ($handler_id !== 'object_create_chooser') {
                    if ($this->_object) {
                        return $this->_prepare_relocate($this->_object);
                    }
                    return new midcom_response_relocate($this->router->generate('type', ['type' => $args[0]]));
                }
        }

        $this->_prepare_request_data();
        if ($handler_id !== 'object_create_chooser') {
            return new midgard_admin_asgard_response($this, '_show_create');
        }
    }

    private function get_defaults(Request $request, $new_type)
    {
        $defaults = [];
        if ($this->_object) {
            // Figure out the linking property
            $parent_property = midgard_object_class::get_property_parent($this->_request_data['current_type']);
            $new_type_reflector = midcom_helper_reflector::get($new_type);
            $link_properties = $new_type_reflector->get_link_properties();
            $type_to_link_to = midcom_helper_reflector::class_rewrite($this->_object->__mgdschema_class_name__);
            foreach ($link_properties as $child_property => $link) {
                $linked_type = midcom_helper_reflector::class_rewrite($link['class']);
                if (   midcom_helper_reflector::is_same_class($linked_type, $type_to_link_to)
                    || (   $link['type'] == MGD_TYPE_GUID
                        && $link['class'] === null)) {
                    $defaults[$child_property] = $this->_object->{$link['target']};
                } elseif (   $child_property == $parent_property
                          && midcom_helper_reflector::is_same_class($new_type, $type_to_link_to)) {
                    $defaults[$child_property] = $this->_object->$parent_property;
                }
            }
            if (empty($defaults)) {
                throw new midcom_error("Could not establish link between {$new_type} and " . get_class($this->_object));
            }
        }

        // Allow setting defaults from query string, useful for things like "create event for today" and chooser
        if ($request->query->has('defaults')) {
            $get_defaults = array_intersect_key($request->query->get('defaults'), $this->schemadb->get_first()->get('fields'));
            $defaults = array_merge($defaults, array_map('trim', $get_defaults));
        }
        return $defaults;
    }

    /**
     * Shows the loaded object in editor.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $data The local request data.
     */
    public function _show_create($handler_id, array &$data)
    {
        if ($handler_id == 'object_create_chooser') {
            midcom_show_style('midgard_admin_asgard_popup_header');
            if (   $this->_new_object->id
                || isset($data['cancelled'])) {
                $data['jsdata'] = $this->_object_to_jsdata($this->_new_object);
                midcom_show_style('midgard_admin_asgard_object_create_after');
            } else {
                midcom_show_style('midgard_admin_asgard_object_create');
            }
            midcom_show_style('midgard_admin_asgard_popup_footer');
            return;
        }

        midcom_show_style('midgard_admin_asgard_object_create');
    }

    private function _object_to_jsdata($object)
    {
        $jsdata = [
            'id' => (string) @$object->id,
            'guid' => @$object->guid,
            'pre_selected' => true
        ];

        foreach (array_keys($this->schemadb->get_first()->get('fields')) as $field) {
            $value = @$object->$field;
            $value = rawurlencode($value);
            $jsdata[$field] = $value;
        }

        return json_encode($jsdata);
    }

    private function _prepare_relocate(midcom_core_dbaobject $object, $mode = 'default')
    {
        // Redirect parameters to overview
        if ($object instanceof midcom_db_parameter) {
            return new midcom_response_relocate($this->router->generate('object_parameters', ['guid' => $object->parentguid]));
        }

        if ($mode == 'delete') {
            // Redirect person deletion to user management
            if ($object instanceof midcom_db_person) {
                return new midcom_response_relocate("__mfa/asgard_midgard.admin.user/");
            }
            if ($parent = $object->get_parent()) {
                return $this->_prepare_relocate($parent);
            }

            $url = $this->router->generate('type', ['type' => $object->__mgdschema_class_name__]);
        } else {
            // Redirect persons to user management
            if ($object instanceof midcom_db_person) {
                return new midcom_response_relocate("__mfa/asgard_midgard.admin.user/edit/{$object->guid}/");
            }
            // Redirect to default object mode page.
            $url = $this->router->generate('object_' . $this->_request_data['default_mode'], ['guid' => $object->guid]);
        }
        return new midcom_response_relocate($url);
    }

    /**
     * Object display
     *
     * @param Request $request The request object
     * @param mixed $handler_id The ID of the handler.
     * @param string $guid The object's GUID
     * @param array $data The local request data.
     */
    public function _handler_delete(Request $request, $handler_id, $guid, array &$data)
    {
        $this->_load_object($guid);
        $this->_object->require_do('midgard:delete');
        midcom::get()->auth->require_user_do('midgard.admin.asgard:manage_objects', null, 'midgard_admin_asgard_plugin');

        $this->_load_schemadb();
        $this->datamanager = new datamanager($this->schemadb);
        $this->datamanager
            ->set_storage($this->_object, 'default')
            ->get_form();

        if ($request->request->has('midgard_admin_asgard_deleteok')) {
            // Deletion confirmed.
            $this->_object->_use_rcs = !$request->request->getBoolean('midgard_admin_asgard_disablercs');

            if (!$this->_object->delete_tree()) {
                throw new midcom_error("Failed to delete object {$guid}, last Midgard error was: " . midcom_connection::get_error_string());
            }

            return $this->_prepare_relocate($this->_object, 'delete');
        }

        if ($request->request->has('midgard_admin_asgard_deletecancel')) {
            return $this->_prepare_relocate($this->_object);
        }

        midgard_admin_asgard_plugin::bind_to_object($this->_object, $handler_id, $data);
        $this->_prepare_request_data();
        $this->_add_jscripts();
        return new midgard_admin_asgard_response($this, '_show_delete');
    }

    /**
     * Shows the object to delete.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $data The local request data.
     */
    public function _show_delete($handler_id, array &$data)
    {
        $data['view_object'] = $this->datamanager->get_content_html();

        // Initialize the tree
        $data['tree'] = new midgard_admin_asgard_copytree($this->_object, $data);
        $data['tree']->copy_tree = false;
        $data['tree']->inputs = false;

        midcom_show_style('midgard_admin_asgard_object_delete');
    }

    /**
     * Copy handler
     *
     * @param Request $request The request object
     * @param mixed $handler_id The ID of the handler.
     * @param string $guid The object's GUID
     * @param array $data The local request data.
     */
    public function _handler_copy(Request $request, $handler_id, $guid, array &$data)
    {
        // Get the object that will be copied
        $this->_load_object($guid);
        midcom::get()->auth->require_user_do('midgard.admin.asgard:manage_objects', null, 'midgard_admin_asgard_plugin');

        if ($handler_id === 'object_copy_tree') {
            $parent = midcom_helper_reflector_copy::get_parent_property($this->_object->__object);
            $this->_load_schemadb(get_class($this->_object), $parent, true);
            // Change the name for the parent field
            $field =& $this->schemadb->get_first()->get_field($parent);
            $field['title'] = $this->_l10n->get('choose the target');
        } else {
            $parent = null;
            $this->_load_schemadb(get_class($this->_object), [false], true);
        }

        $dm = new datamanager($this->schemadb);
        $this->controller = $dm->get_controller();

        $this->_prepare_request_data();
        $reflector = new midcom_helper_reflector($this->_object);

        // Process the form
        switch ($this->controller->handle($request)) {
            case 'save':
                $new_object = $this->_process_copy($request, $parent, $reflector);
                // Relocate to the newly created object
                return $this->_prepare_relocate($new_object);

            case 'cancel':
                return $this->_prepare_relocate($this->_object);
        }

        $this->_add_jscripts();

        // Common hooks for Asgard
        midgard_admin_asgard_plugin::bind_to_object($this->_object, $handler_id, $data);

        // Set the page title
        $label = $reflector->get_object_label($this->_object);
        if ($handler_id === 'object_copy_tree') {
            $data['page_title'] = sprintf($this->_l10n->get('copy %s and its descendants'), $label);
        } else {
            $data['page_title'] = sprintf($this->_l10n->get('copy %s'), $label);
        }

        return new midgard_admin_asgard_response($this, '_show_copy');
    }

    /**
     * Add the necessary static files for copy/delete operations
     */
    private function _add_jscripts()
    {
        // Add Colorbox
        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/jQuery/colorbox/jquery.colorbox-min.js');
        $this->add_stylesheet(MIDCOM_STATIC_URL . '/jQuery/colorbox/colorbox.css', 'screen');
        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/midgard.admin.asgard/object_browser.js');

        // Add jQuery file for the checkbox operations
        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/midgard.admin.asgard/jquery-copytree.js');
    }

    private function _process_copy(Request $request, $parent, midcom_helper_reflector $reflector)
    {
        $formdata = $this->controller->get_datamanager()->get_content_raw();
        $copy = new midcom_helper_reflector_copy();

        // Copying of parameters, metadata and such
        $copy->parameters = $formdata['parameters'];
        $copy->metadata = $formdata['metadata'];
        $copy->attachments = $formdata['attachments'];
        $copy->privileges = $formdata['privileges'];

        if ($this->_request_data['handler_id'] === 'object_copy_tree') {
            // Set the target - if available
            if (!empty($formdata[$parent])) {
                $link_properties = $reflector->get_link_properties();

                if (empty($link_properties[$parent])) {
                    throw new midcom_error('Failed to construct the target class object');
                }

                $class_name = $link_properties[$parent]['class'];
                $copy->target = new $class_name($formdata[$parent]);
            }
            $copy->exclude = array_diff($request->request->get('all_objects'), $request->request->get('selected'));
        } else {
            $copy->recursive = false;
        }
        $new_object = $copy->execute($this->_object);

        if ($new_object === false) {
            debug_print_r('Copying failed with the following errors', $copy->errors, MIDCOM_LOG_ERROR);
            throw new midcom_error('Failed to successfully copy the object. Details in error level log');
        }

        if ($this->_request_data['handler_id'] === 'object_copy_tree') {
            midcom::get()->uimessages->add($this->_l10n->get('midgard.admin.asgard'), $this->_l10n->get('copy successful, you have been relocated to the root of the new object tree'));
        } else {
            midcom::get()->uimessages->add($this->_l10n->get('midgard.admin.asgard'), $this->_l10n->get('copy successful, you have been relocated to the new object'));
        }
        return midcom::get()->dbfactory->convert_midgard_to_midcom($new_object);
    }

    /**
     * Show copy style
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $data The local request data.
     */
    public function _show_copy($handler_id, array &$data)
    {
        // Show the tree hierarchy
        if ($handler_id === 'object_copy_tree') {
            $data['tree'] = new midgard_admin_asgard_copytree($this->_object, $data);
            $data['tree']->inputs = true;
            $data['tree']->copy_tree = true;
            midcom_show_style('midgard_admin_asgard_object_copytree');
        } else {
            // Show the copy page
            midcom_show_style('midgard_admin_asgard_object_copy');
        }
    }
}
