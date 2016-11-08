<?php
/**
 * @package midgard.admin.asgard
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

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
    private $_object = null;

    /**
     * Some newly created object
     *
     * @var midcom_core_dbaobject
     */
    private $_new_object = null;

    /**
     * Some MgdSchema class
     *
     * @var string
     */
    private $_new_type = null;

    /**
     * The Datamanager of the object to display.
     *
     * @var midcom_helper_datamanager2_datamanager
     */
    private $_datamanager = null;

    /**
     * The Controller of the object used for editing
     *
     * @var midcom_helper_datamanager2_controller_simple
     */
    private $_controller = null;

    /**
     * The schema database in use, available only while a datamanager is loaded.
     *
     * @var array
     */
    private $_schemadb = null;

    /**
     * Retrieve the object from the db
     *
     * @param string $guid GUID
     */
    private function _load_object($guid)
    {
        try
        {
            $this->_object = midcom::get()->dbfactory->get_object_by_guid($guid);
        }
        catch (midcom_error $e)
        {
            if (midcom_connection::get_error() == MGD_ERR_OBJECT_DELETED)
            {
                $relocate = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX) . '__mfa/asgard/object/deleted/' . $guid . '/';
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
        $this->_request_data['controller'] = $this->_controller;
        $this->_request_data['schemadb'] = $this->_schemadb;
        $this->_request_data['datamanager'] = $this->_datamanager;
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
        $this->_schemadb = $schema_helper->create($include_fields);
    }

    /**
     * Looks up the user's default mode and redirects there. This is mainly useful for links from outside Asgard
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_open($handler_id, array $args, array &$data)
    {
        $page_prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
        return new midcom_response_relocate($page_prefix . '__mfa/asgard/object/' . $data['default_mode'] . '/' . $args[0] . '/');
    }

    /**
     * Object display
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_view($handler_id, array $args, array &$data)
    {
        $this->_load_object($args[0]);

        midcom::get()->auth->require_user_do('midgard.admin.asgard:manage_objects', null, 'midgard_admin_asgard_plugin');

        $this->_load_schemadb();

        // Hide the revision message
        $this->_schemadb['object']->fields['_rcs_message']['hidden'] = true;

        $this->_datamanager = new midcom_helper_datamanager2_datamanager($this->_schemadb);
        $this->_datamanager->set_schema('object');
        if (!$this->_datamanager->set_storage($this->_object))
        {
            throw new midcom_error("Failed to create a DM2 instance for object {$this->_object->guid}.");
        }

        midgard_admin_asgard_plugin::bind_to_object($this->_object, $handler_id, $data);
        $this->_prepare_request_data();
        return new midgard_admin_asgard_response($this, '_show_view');
    }

    /**
     * Shows the loaded object.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_view($handler_id, array &$data)
    {
        midcom_show_style('midgard_admin_asgard_object_view');
    }

    /**
     * Object editing view
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_edit($handler_id, array $args, array &$data)
    {
        $this->_load_object($args[0]);

        $this->_object->require_do('midgard:update');
        midcom::get()->auth->require_user_do('midgard.admin.asgard:manage_objects', null, 'midgard_admin_asgard_plugin');

        $this->_load_schemadb();
        $this->_controller = midcom_helper_datamanager2_controller::create('simple');
        $this->_controller->schemadb =& $this->_schemadb;
        $this->_controller->set_storage($this->_object, 'object');
        if (!$this->_controller->initialize())
        {
            throw new midcom_error("Failed to initialize a DM2 controller instance for object {$this->_object->guid}.");
        }

        switch ($this->_controller->process_form())
        {
            case 'save':
                if (is_a($this->_object, 'midcom_db_topic'))
                {
                    if (   !empty($this->_object->symlink)
                        && !empty($this->_object->component))
                    {
                        $this->_object->symlink = null;
                        $this->_object->update();
                    }
                }

                // Reindex the object
                //$indexer = midcom::get()->indexer;
                //net_nemein_wiki_viewer::index($this->_request_data['controller']->datamanager, $indexer, $this->_topic);
                //Fall-through

            case 'cancel':
                return $this->_prepare_relocate($this->_object);

            case 'edit':
                $qf =& $this->_controller->formmanager->form;
                if (   isset($_REQUEST['midcom_helper_datamanager2_save'])
                    && isset($qf->_errors))
                {
                    foreach ($qf->_errors as $field => $error)
                    {
                        $element =& $qf->getElement($field);
                        $message = sprintf($this->_l10n->get('validation error in field %s: %s'), $element->getLabel(), $error);
                        midcom::get()->uimessages->add
                            (
                                $this->_l10n->get('midgard.admin.asgard'),
                                $message,
                                'error'
                            );
                    }
                }
        }

        $this->_prepare_request_data();
        midgard_admin_asgard_plugin::bind_to_object($this->_object, $handler_id, $data);
        return new midgard_admin_asgard_response($this, '_show_edit');
    }

    /**
     * Shows the loaded object in editor.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_edit($handler_id, array &$data)
    {
        midcom_show_style('midgard_admin_asgard_object_edit');
    }

    /**
     * DM2 creation callback, binds to the current content topic.
     */
    public function & dm2_create_callback(&$controller)
    {
        $create_type = $this->_new_type;
        $this->_new_object = new $create_type();
        $mgd_type = midcom::get()->dbclassloader->get_mgdschema_class_name_for_midcom_class($create_type);

        if ($parent_property = midgard_object_class::get_property_parent($mgd_type));
        {
            $this->_new_object->$parent_property = $controller->formmanager->get_value($parent_property);
        }

        if (!$this->_new_object->create())
        {
            debug_print_r('We operated on this object:', $this->_new_object);
            throw new midcom_error('Failed to create a new object. Last Midgard error was: '. midcom_connection::get_error_string());
        }

        return $this->_new_object;
    }

    /**
     * Object creating view
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_create($handler_id, array $args, array &$data)
    {
        $this->_new_type = midcom::get()->dbclassloader->get_midcom_class_name_for_mgdschema_object($args[0]);
        if (!$this->_new_type)
        {
            throw new midcom_error_notfound('Failed to find type for the new object');
        }

        midcom::get()->dbclassloader->load_mgdschema_class_handler($this->_new_type);
        if (!class_exists($this->_new_type))
        {
            throw new midcom_error_notfound("Component handling MgdSchema type '{$args[0]}' was not found.");
        }
        $data['current_type'] = $args[0];

        midcom::get()->auth->require_user_do('midgard.admin.asgard:manage_objects', null, 'midgard_admin_asgard_plugin');

        $data['defaults'] = array();
        if (   $handler_id == '____mfa-asgard-object_create_toplevel'
            || $handler_id == '____mfa-asgard-object_create_chooser')
        {
            midcom::get()->auth->require_user_do('midgard:create', null, $this->_new_type);

            $data['view_title'] = sprintf($this->_l10n_midcom->get('create %s'), midgard_admin_asgard_plugin::get_type_label($data['current_type']));
        }
        else
        {
            $this->_object = midcom::get()->dbfactory->get_object_by_guid($args[1]);
            $this->_object->require_do('midgard:create');
            midgard_admin_asgard_plugin::bind_to_object($this->_object, $handler_id, $data);
        }

        $this->_load_schemadb($this->_new_type);

        if (isset($this->_schemadb['object']->fields['guid']))
        {
            $this->_schemadb['object']->fields['guid']['hidden'] = true;
        }

        $this->_controller = midcom_helper_datamanager2_controller::create('create');
        $this->_controller->schemadb =& $this->_schemadb;
        $this->_controller->schema = 'object';
        $this->_controller->callback_object =& $this;
        $this->_controller->defaults = $this->_get_defaults();
        if (!$this->_controller->initialize())
        {
            throw new midcom_error("Failed to initialize a DM2 create controller.");
        }

        switch ($this->_controller->process_form())
        {
            case 'edit':
                if ($this->_new_object)
                {
                    $this->_new_object = null;
                }
                break;

            case 'save':
                // Reindex the object
                //$indexer = midcom::get()->indexer;
                //net_nemein_wiki_viewer::index($this->_request_data['controller']->datamanager, $indexer, $this->_topic);
                // *** FALL-THROUGH ***
                $this->_new_object->set_parameter('midcom.helper.datamanager2', 'schema_name', 'default');

                if ($handler_id !== '____mfa-asgard-object_create_chooser')
                {
                    return $this->_prepare_relocate($this->_new_object);
                }
                break;

            case 'cancel':
                $data['cancelled'] = true;
                if ($handler_id !== '____mfa-asgard-object_create_chooser')
                {
                    if ($this->_object)
                    {
                        return $this->_prepare_relocate($this->_object);
                    }
                    return new midcom_response_relocate("__mfa/asgard/{$args[0]}/");
                }
        }

        $this->_prepare_request_data();
        if ($handler_id !== '____mfa-asgard-object_create_chooser')
        {
            return new midgard_admin_asgard_response($this, '_show_create');
        }
    }

    private function _get_defaults()
    {
        $defaults = array();
        if ($this->_object)
        {
            // Figure out the linking property
            $parent_property = null;
            $new_type_reflector = midcom_helper_reflector::get($this->_new_type);
            $link_properties = $new_type_reflector->get_link_properties();
            $type_to_link_to =  midcom_helper_reflector::class_rewrite(get_class($this->_object));
            foreach ($link_properties as $child_property => $link)
            {
                $linked_type = midcom_helper_reflector::class_rewrite($link['class']);
                if (midcom_helper_reflector::is_same_class($linked_type, $type_to_link_to)
                    || (   $link['type'] == MGD_TYPE_GUID
                        && is_null($link['class'])))
                {
                    $parent_property = $link['target'];
                    break;
                }
            }
            if (empty($parent_property))
            {
                throw new midcom_error("Could not establish link between {$this->_new_type} and " . get_class($this->_object));
            }
            $defaults[$child_property] = $this->_object->$parent_property;
        }

        // Allow setting defaults from query string, useful for things like "create event for today" and chooser
        if (   isset($_GET['defaults'])
            && is_array($_GET['defaults']))
        {
            $get_defaults = array_intersect_key($_GET['defaults'], $this->_schemadb['object']->fields);
            $defaults = array_merge($defaults, array_map('trim', $get_defaults));
        }
        return $defaults;
    }

    /**
     * Shows the loaded object in editor.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_create($handler_id, array &$data)
    {
        if ($handler_id == '____mfa-asgard-object_create_chooser')
        {
            midcom_show_style('midgard_admin_asgard_popup_header');
            if (   $this->_new_object
                || isset($data['cancelled']))
            {
                $data['jsdata'] = $this->_object_to_jsdata($this->_new_object);
                midcom_show_style('midgard_admin_asgard_object_create_after');
            }
            else
            {
                midcom_show_style('midgard_admin_asgard_object_create');
            }
            midcom_show_style('midgard_admin_asgard_popup_footer');
            return;
        }

        midcom_show_style('midgard_admin_asgard_object_create');
    }

    private function _object_to_jsdata($object)
    {
        $jsdata = array
        (
            'id' => (string) @$object->id,
            'guid' => @$object->guid,
            'pre_selected' => true
        );

        foreach (array_keys($this->_schemadb['object']->fields) as $field)
        {
            $value = @$object->$field;
            $value = rawurlencode($value);
            $jsdata[$field] = $value;
        }

        return json_encode($jsdata);
    }

    private function _prepare_relocate(midcom_core_dbaobject $object, $mode = 'default')
    {
        // Redirect parameters to overview
        if (is_a($object, 'midcom_db_parameter'))
        {
            return new midcom_response_relocate("__mfa/asgard/object/parameters/{$object->parentguid}/");
        }

        if ($mode == 'delete')
        {
            // Redirect person deletion to user management
            if (is_a($object, 'midcom_db_person'))
            {
                return new midcom_response_relocate("__mfa/asgard_midcom.admin.user/");
            }
            if ($parent = $object->get_parent())
            {
                return $this->_prepare_relocate($parent);
            }

            $type = $object->__mgdschema_class_name__;
            $url = $type;

            $class_extends = $this->_config->get('class_extends');
            if (   is_array($class_extends)
                && array_key_exists($type, $class_extends))
            {
                $url = $class_extends[$type];
            }
            $url = '__mfa/asgard/' . $url;
        }
        else
        {
            // Redirect persons to user management
            if (is_a($object, 'midcom_db_person'))
            {
                return new midcom_response_relocate("__mfa/asgard_midcom.admin.user/edit/{$object->guid}/");
            }
            // Redirect to default object mode page.
            $url = "__mfa/asgard/object/{$this->_request_data['default_mode']}/{$object->guid}/";
        }
        return new midcom_response_relocate($url);
    }

    /**
     * Object display
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_delete($handler_id, array $args, array &$data)
    {
        $this->_load_object($args[0]);
        $this->_object->require_do('midgard:delete');
        midcom::get()->auth->require_user_do('midgard.admin.asgard:manage_objects', null, 'midgard_admin_asgard_plugin');

        $this->_load_schemadb();
        $this->_datamanager = new midcom_helper_datamanager2_datamanager($this->_schemadb);
        $this->_datamanager->set_schema('object');
        if (!$this->_datamanager->set_storage($this->_object))
        {
            throw new midcom_error("Failed to create a DM2 instance for object {$this->_object->guid}.");
        }

        if (array_key_exists('midgard_admin_asgard_deleteok', $_REQUEST))
        {
            // Deletion confirmed.
            if (array_key_exists('midgard_admin_asgard_disablercs', $_REQUEST))
            {
                $this->_object->_use_rcs = false;
            }

            if (!$this->_object->delete_tree())
            {
                throw new midcom_error("Failed to delete object {$args[0]}, last Midgard error was: " . midcom_connection::get_error_string());
            }

            return $this->_prepare_relocate($this->_object, 'delete');
        }

        if (array_key_exists('midgard_admin_asgard_deletecancel', $_REQUEST))
        {
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
     * @param array &$data The local request data.
     */
    public function _show_delete($handler_id, array &$data)
    {
        $data['view_object'] = $this->_datamanager->get_content_html();

        // Initialize the tree
        $data['tree'] = new midgard_admin_asgard_copytree($this->_object, $data);
        $data['tree']->copy_tree = false;
        $data['tree']->inputs = false;

        midcom_show_style('midgard_admin_asgard_object_delete');
    }

    /**
     * Copy handler
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_copy($handler_id, array $args, array &$data)
    {
        // Get the object that will be copied
        $this->_load_object($args[0]);

        midcom::get()->auth->require_user_do('midgard.admin.asgard:manage_objects', null, 'midgard_admin_asgard_plugin');
        $target = midcom_helper_reflector_copy::get_target_properties($this->_object);

        // Load the schemadb for searching the parent object
        $this->_load_schemadb($target['class'], $target['parent'], true);
        // Change the name for the parent field
        $this->_schemadb['object']->fields[$target['parent']]['title'] = $this->_l10n->get('choose the target');

        // Load the nullstorage controller
        $this->_controller = midcom_helper_datamanager2_controller::create('nullstorage');
        $this->_controller->schemadb =& $this->_schemadb;

        if (!$this->_controller->initialize())
        {
            throw new midcom_error('Failed to initialize the controller');
        }

        $this->_prepare_request_data();

        // Process the form
        switch ($this->_controller->process_form())
        {
            case 'save':
                $new_object = $this->_process_copy($target);
                // Relocate to the newly created object
                return $this->_prepare_relocate($new_object);

            case 'cancel':
                return $this->_prepare_relocate($this->_object);
        }

        $this->_add_jscripts();

        // Common hooks for Asgard
        midgard_admin_asgard_plugin::bind_to_object($this->_object, $handler_id, $data);

        // Set the page title
        switch ($handler_id)
        {
            case '____mfa-asgard-object_copy_tree':
                $data['page_title'] = sprintf($this->_l10n->get('copy %s and its descendants'), $this->_object->{$target['label']});
                break;
            default:
                $data['page_title'] = sprintf($this->_l10n->get('copy %s'), $this->_object->{$target['label']});
        }

        $data['target'] = $target;
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

    private function _process_copy($target)
    {
        // Get the target information of the form
        $target['id'] = $this->_controller->datamanager->types[$target['parent']]->convert_to_storage();
        $this->_controller->datamanager->types['metadata']->convert_to_storage();
        $this->_controller->datamanager->types['attachments']->convert_to_storage();
        $this->_controller->datamanager->types['privileges']->convert_to_storage();

        $copy = new midcom_helper_reflector_copy();
        $copy->source = $this->_object;

        // Set the target - if available
        if (!empty($target['id']))
        {
            $link_properties = $target['reflector']->get_link_properties();
            $parent = $target['parent'];

            if (empty($link_properties[$parent]))
            {
                throw new midcom_error('Failed to construct the target class object');
            }

            $class_name = $link_properties[$parent]['class'];
            $target_object = new $class_name($target['id']);
            $copy->target = $target_object;
        }

        // Copying of parameters, metadata and such
        $copy->copy_parameters = $this->_controller->datamanager->types['parameters']->convert_to_storage();
        $copy->copy_metadata = $this->_controller->datamanager->types['metadata']->convert_to_storage();
        $copy->copy_attachments = $this->_controller->datamanager->types['attachments']->convert_to_storage();
        $copy->copy_privileges = $this->_controller->datamanager->types['privileges']->convert_to_storage();

        if ($this->_request_data['handler_id'] === '____mfa-asgard-object_copy_tree')
        {
            $copy->exclude = array_diff($_POST['all_objects'], $_POST['selected']);
        }
        else
        {
            $copy->copy_tree = false;
        }

        if (!$copy->execute())
        {
            debug_print_r('Copying failed with the following errors', $copy->errors, MIDCOM_LOG_ERROR);
            throw new midcom_error('Failed to successfully copy the object. Details in error level log');
        }

        $new_object = $copy->get_object();

        if (empty($new_object->guid))
        {
            throw new midcom_error('Failed to copy the object');
        }

        if ($this->_request_data['handler_id'] === '____mfa-asgard-object_copy_tree')
        {
            midcom::get()->uimessages->add($this->_l10n->get('midgard.admin.asgard'), $this->_l10n->get('copy successful, you have been relocated to the root of the new object tree'));
        }
        else
        {
            midcom::get()->uimessages->add($this->_l10n->get('midgard.admin.asgard'), $this->_l10n->get('copy successful, you have been relocated to the new object'));
        }
        return midcom::get()->dbfactory->convert_midgard_to_midcom($new_object);
    }

    /**
     * Show copy style
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_copy($handler_id, array &$data)
    {
        // Show the tree hierarchy
        if ($handler_id === '____mfa-asgard-object_copy_tree')
        {
            $data['tree'] = new midgard_admin_asgard_copytree($this->_object, $data);
            $data['tree']->inputs = true;
            $data['tree']->copy_tree = true;
            midcom_show_style('midgard_admin_asgard_object_copytree');
        }
        else
        {
            // Show the copy page
            midcom_show_style('midgard_admin_asgard_object_copy');
        }
    }
}
