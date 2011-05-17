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
     * @var midgard_object
     */
    private $_object = null;

    /**
     * Some newly created object
     *
     * @var midgard_object
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
     * Helper function to retrieve the object from the db
     *
     * @param string $guid GUID
     */
    private function _load_object($guid)
    {
        try
        {
            $this->_object = $_MIDCOM->dbfactory->get_object_by_guid($guid);
        }
        catch (midcom_error $e)
        {
            if (midcom_connection::get_error() == MGD_ERR_OBJECT_DELETED)
            {
                $relocate = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX) . '__mfa/asgard/object/deleted/' . $guid;
                $_MIDCOM->relocate($relocate);
            }

            throw $e;
        }
    }

    public function _on_initialize()
    {
        // Ensure we get the correct styles
        $_MIDCOM->style->prepend_component_styledir('midgard.admin.asgard');
        $_MIDCOM->skip_page_style = true;

        $_MIDCOM->load_library('midcom.helper.datamanager2');

        // Accordion is needed for per-type help when available
        $_MIDCOM->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.core.min.js');
        $_MIDCOM->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.widget.min.js');
        $_MIDCOM->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.accordion.min.js');
    }

    /**
     * Simple helper which references all important members to the request data listing
     * for usage within the style listing.
     */
    private function _prepare_request_data()
    {
        $this->_request_data['object'] =& $this->_object;
        $this->_request_data['controller'] =& $this->_controller;
        $this->_request_data['schemadb'] =& $this->_schemadb;
        $this->_request_data['datamanager'] =& $this->_datamanager;
        $this->_request_data['asgard_prefix'] = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX) . '__mfa/asgard/';
    }

    /**
     * Loads the schemadb from the helper class
     */
    private function _load_schemadb($type = null, $include_fields = null, $add_copy_fields = false)
    {
        $schema_helper = new midgard_admin_asgard_schemadb($this->_object, $this->_config);
        $schema_helper->add_copy_fields = $add_copy_fields;
        $this->_schemadb = $schema_helper->create($type, $include_fields);
    }

    /**
     * Looks up the user's default mode and redirects there. This is mainly useful for links from outside Asgard
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    public function _handler_open($handler_id, array $args, array &$data)
    {
        $page_prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
        $_MIDCOM->relocate($page_prefix . '__mfa/asgard/object/' . $data['default_mode'] . '/' . $args[0] . '/');
    }

    /**
     * Object display
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    public function _handler_view($handler_id, array $args, array &$data)
    {
        $this->_load_object($args[0]);

        $_MIDCOM->auth->require_user_do('midgard.admin.asgard:manage_objects', null, 'midgard_admin_asgard_plugin');

        $this->_prepare_request_data();

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
    }

    /**
     * Shows the loaded object.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_view($handler_id, array &$data)
    {
        if (isset($_GET['ajax']))
        {
            $data['view_object'] = $this->_datamanager->get_content_html();
            midcom_show_style('midgard_admin_asgard_object_view');
            return;
        }

        $data['view_object'] = $this->_datamanager->get_content_html();
        midcom_show_style('midgard_admin_asgard_header');
        midcom_show_style('midgard_admin_asgard_middle');
        midcom_show_style('midgard_admin_asgard_object_view');
        midcom_show_style('midgard_admin_asgard_footer');
    }

    /**
     * Object editing view
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    public function _handler_edit($handler_id, array $args, array &$data)
    {
        $this->_load_object($args[0]);

        $this->_object->require_do('midgard:update');
        $_MIDCOM->auth->require_user_do('midgard.admin.asgard:manage_objects', null, 'midgard_admin_asgard_plugin');

        $this->_load_schemadb();
        $this->_controller = midcom_helper_datamanager2_controller::create('simple');
        $this->_controller->schemadb =& $this->_schemadb;
        $this->_controller->set_storage($this->_object, 'object');
        if (! $this->_controller->initialize())
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

                if (   is_a($this->_object, 'midcom_db_style')
                    || is_a($this->_object, 'midcom_db_element')
                    || is_a($this->_object, 'midcom_db_page')
                    || is_a($this->_object, 'midcom_db_pageelement'))
                {
                    mgd_cache_invalidate();
                }

                // Reindex the object
                //$indexer = $_MIDCOM->get_service('indexer');
                //net_nemein_wiki_viewer::index($this->_request_data['controller']->datamanager, $indexer, $this->_topic);
                $_MIDCOM->relocate("__mfa/asgard/object/edit/{$this->_object->guid}/");
                // This will exit.

            case 'cancel':
                $_MIDCOM->relocate("__mfa/asgard/object/{$this->_request_data['default_mode']}/{$this->_object->guid}/");
                // This will exit.
            case 'edit':
                $qf =& $this->_controller->formmanager->form;
                if(isset($_REQUEST['midcom_helper_datamanager2_save']) && isset($qf->_errors))
                {
                    foreach($qf->_errors as $field => $error)
                    {
                        $element =& $qf->getElement($field);
                        $message = sprintf($this->_l10n->get('validation error in field %s: %s'), $element->getLabel(), $error);
                        $_MIDCOM->uimessages->add
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
    }

    /**
     * Shows the loaded object in editor.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_edit($handler_id, array &$data)
    {
        midcom_show_style('midgard_admin_asgard_header');
        midcom_show_style('midgard_admin_asgard_middle');
        midcom_show_style('midgard_admin_asgard_object_edit');
        midcom_show_style('midgard_admin_asgard_footer');
    }

    private function _find_linking_property($new_type)
    {
        // Figure out the linking property
        $new_type_reflector = midcom_helper_reflector::get($new_type);
        $link_properties = $new_type_reflector->get_link_properties();
        $type_to_link_to =  midcom_helper_reflector::class_rewrite(get_class($this->_object));
        foreach ($link_properties as $new_type_property => $link)
        {
            $linked_type = midcom_helper_reflector::class_rewrite($link['class']);
            if (midcom_helper_reflector::is_same_class($linked_type, $type_to_link_to)
                || (   $link['type'] == MGD_TYPE_GUID
                    && is_null($link['class'])))
            {
                $parent_property = $link['target'];
                return array($new_type_property, $parent_property);
            }
        }
        return false;
    }

    /**
     * DM2 creation callback, binds to the current content topic.
     */
    function & dm2_create_callback(&$controller)
    {
        $create_type = $this->_new_type;
        $this->_new_object = new $create_type();

        if ($this->_object)
        {
            if ($this->_new_type == 'midcom_db_parameter')
            {
                // Parameters are linked a bit differently
                $this->_new_object->parentguid = $this->_object->guid;
            }
            else
            {
                // Figure out the linking property
                $link_info = $this->_find_linking_property($create_type);
                if (!is_array($link_info))
                {
                    throw new midcom_error("Could not establish link between {$create_type} and " . get_class($this->_object));
                }

                $child_property = $link_info[0];
                $parent_property = $link_info[1];
                $this->_new_object->$child_property = $this->_object->$parent_property;
            }
        }

        if (! $this->_new_object->create())
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
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    public function _handler_create($handler_id, array $args, array &$data)
    {
        $this->_new_type = $_MIDCOM->dbclassloader->get_midcom_class_name_for_mgdschema_object($args[0]);
        if (!$this->_new_type)
        {
            throw new midcom_error_notfound('Failed to find type for the new object');
        }

        $_MIDCOM->dbclassloader->load_mgdschema_class_handler($this->_new_type);
        if (!class_exists($this->_new_type))
        {
            throw new midcom_error_notfound("Component handling MgdSchema type '{$args[0]}' was not found.");
        }
        $data['new_type_arg'] = $args[0];

        $_MIDCOM->auth->require_user_do('midgard.admin.asgard:manage_objects', null, 'midgard_admin_asgard_plugin');

        $data['defaults'] = array();
        if (   $handler_id == '____mfa-asgard-object_create_toplevel'
            || $handler_id == '____mfa-asgard-object_create_chooser')
        {
            $_MIDCOM->auth->require_user_do('midgard:create', null, $this->_new_type);

            $data['view_title'] = sprintf($_MIDCOM->i18n->get_string('create %s', 'midcom'), midgard_admin_asgard_plugin::get_type_label($data['new_type_arg']));
        }
        else
        {
            $this->_object = $_MIDCOM->dbfactory->get_object_by_guid($args[1]);
            $this->_object->require_do('midgard:create');
            midgard_admin_asgard_plugin::bind_to_object($this->_object, $handler_id, $data);

            // FIXME: Make a general case for all objects that are linked by guid to any other class
            if ($this->_new_type == 'midcom_db_parameter')
            {
                // Parameters are linked a bit differently
                $parent_property = 'guid';
                $data['defaults']['parentguid'] = $this->_object->$parent_property;
            }
            else
            {
                // Set "defaults"
                $link_info = $this->_find_linking_property($this->_new_type);
                if (!is_array($link_info))
                {
                    throw new midcom_error("Could not establish link between {$this->_new_type} and " . get_class($this->_object));
                }
                $parent_property = $link_info[1];
                $data['defaults'][$link_info[0]] = $this->_object->$parent_property;
            }
        }

        $this->_load_schemadb($this->_new_type);

        if (isset($this->_schemadb['object']->fields['guid']))
        {
            $this->_schemadb['object']->fields['guid']['hidden'] = true;
        }

        // Allow setting defaults from query string, useful for things like "create event for today" and chooser
        if (   isset($_GET['defaults'])
            && is_array($_GET['defaults']))
        {
            foreach ($_GET['defaults'] as $key => $value)
            {
                if (!isset($this->_schemadb['object']->fields[$key]))
                {
                    // No such field in schema
                    continue;
                }

                $data['defaults'][$key] = trim($value);
            }
        }

        $this->_controller = midcom_helper_datamanager2_controller::create('create');
        $this->_controller->schemadb =& $this->_schemadb;
        $this->_controller->schema = 'object';
        $this->_controller->callback_object =& $this;
        $this->_controller->defaults = $data['defaults'];
        if (! $this->_controller->initialize())
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
                if (   is_a($this->_new_object, 'midcom_db_style')
                    || is_a($this->_new_object, 'midcom_db_element'))
                {
                    mgd_cache_invalidate();
                }

                // Reindex the object
                //$indexer = $_MIDCOM->get_service('indexer');
                //net_nemein_wiki_viewer::index($this->_request_data['controller']->datamanager, $indexer, $this->_topic);
                // *** FALL-THROUGH ***
                $this->_new_object->set_parameter('midcom.helper.datamanager2', 'schema_name', 'default');

                if ($handler_id != '____mfa-asgard-object_create_chooser')
                {
                    $redirect_url = str_replace('//', '/', "__mfa/asgard/object/edit/{$this->_new_object->guid}/");
                    $_MIDCOM->relocate($redirect_url);
                    // This will exit.
                }
                break;

            case 'cancel':
                $data['cancelled'] = true;
                if ($this->_object)
                {
                    $objecturl = "object/{$this->_request_data['default_mode']}/{$this->_object->guid}/";
                }
                else
                {
                    $objecturl = $args[0];
                }

                if ($handler_id != '____mfa-asgard-object_create_chooser')
                {
                    $_MIDCOM->relocate("__mfa/asgard/{$objecturl}/");
                    // This will exit.
                }
        }

        $this->_prepare_request_data();
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

        midcom_show_style('midgard_admin_asgard_header');
        midcom_show_style('midgard_admin_asgard_middle');
        midcom_show_style('midgard_admin_asgard_object_create');
        midcom_show_style('midgard_admin_asgard_footer');
    }

    private function _object_to_jsdata(&$object)
    {
        $id = @$object->id;
        $guid = @$object->guid;

        $jsdata = "{";

        $jsdata .= "id: '{$id}',";
        $jsdata .= "guid: '{$guid}',";
        $jsdata .= "pre_selected: true,";

        $hi_count = count($this->_schemadb['object']->fields);
        $i = 1;
        foreach ($this->_schemadb['object']->fields as $field => $field_data)
        {
            $value = @$object->$field;
            $value = rawurlencode($value);
            $jsdata .= "{$field}: '{$value}'";

            if ($i < $hi_count)
            {
                $jsdata .= ", ";
            }

            $i++;
        }

        $jsdata .= "}";

        return $jsdata;
    }

    /**
     * Object display
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    public function _handler_delete($handler_id, array $args, array &$data)
    {
        $this->_load_object($args[0]);

        $this->_object->require_do('midgard:delete');
        $_MIDCOM->auth->require_user_do('midgard.admin.asgard:manage_objects', null, 'midgard_admin_asgard_plugin');

        $type = $this->_object->__mgdschema_class_name__;

        $relocate_url = $type;
        $cancel_url = "__mfa/asgard/object/{$this->_request_data['default_mode']}/{$this->_object->guid}/";

        $class_extends = $this->_config->get('class_extends');
        if (   is_array($class_extends)
            && array_key_exists($type, $class_extends))
        {
            $relocate_url = $class_extends[$type];
        }

        // Redirect person deletion to user management
        if (is_a($this->_object, 'midcom_db_person'))
        {
            $relocate_url = "../asgard_midcom.admin.user/";
            $cancel_url = "__mfa/asgard_midcom.admin.user/edit/{$args[0]}/";
        }

        $this->_prepare_request_data();

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
            $parent = $this->_object->get_parent();

            if (array_key_exists('midgard_admin_asgard_disablercs', $_REQUEST))
            {
                $this->_object->_use_rcs = false;
            }

            if (!$this->_object->delete_tree())
            {
                throw new midcom_error("Failed to delete object {$args[0]}, last Midgard error was: " . midcom_connection::get_error_string());
            }

            if (   is_a($this->_object, 'midcom_db_style')
                || is_a($this->_object, 'midcom_db_element'))
            {
                mgd_cache_invalidate();
            }

            // Update the index
            $indexer = $_MIDCOM->get_service('indexer');
            $indexer->delete($this->_object->guid);

            if ($parent)
            {
                $_MIDCOM->relocate(midcom_connection::get_url('self') . "__mfa/asgard/object/{$data['default_mode']}/{$parent->guid}/");
                // This will exit()
            }

            $_MIDCOM->relocate(midcom_connection::get_url('self') . "__mfa/asgard/" . $relocate_url);
            // This will exit.
        }

        if (array_key_exists('midgard_admin_asgard_deletecancel', $_REQUEST))
        {
            // Redirect to default object mode page.
            $_MIDCOM->relocate($cancel_url);
            // This will exit()
        }

        midgard_admin_asgard_plugin::bind_to_object($this->_object, $handler_id, $data);

        // Add Thickbox
        $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . '/midgard.admin.asgard/object_browser.js');
        $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . '/jQuery/thickbox/jquery-thickbox-3.1.pack.js');
        $this->add_stylesheet(MIDCOM_STATIC_URL . '/jQuery/thickbox/thickbox.css', 'screen');
        $_MIDCOM->add_jscript('var tb_pathToImage = "' . MIDCOM_STATIC_URL . '/jQuery/thickbox/loadingAnimation.gif"');

        // Add jQuery file for the checkbox operations
        $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . '/midgard.admin.asgard/jquery-copytree.js');
        $_MIDCOM->add_jscript('jQuery(document).ready(function(){jQuery("#midgard_admin_asgard_copytree").tree_checker();})');

        return true;
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
        midcom_show_style('midgard_admin_asgard_header');

        midcom_show_style('midgard_admin_asgard_middle');

        // Initialize the tree
        $data['tree'] = new midgard_admin_asgard_copytree($this->_object, $data);
        $data['tree']->copy_tree = false;
        $data['tree']->inputs = false;

        midcom_show_style('midgard_admin_asgard_object_delete');
        midcom_show_style('midgard_admin_asgard_footer');
    }

    /**
     * Copy handler
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    public function _handler_copy($handler_id, array $args, array &$data)
    {
        // Get the object that will be copied
        $this->_load_object($args[0]);

        $_MIDCOM->auth->require_user_do('midgard.admin.asgard:manage_objects', null, 'midgard_admin_asgard_plugin');

        static $targets = array();

        $mgdschema_class = midcom_helper_reflector::resolve_baseclass(get_class($this->_object));

        // Get the target details
        if (in_array($mgdschema_class, $targets))
        {
            $target = $targets[$mgdschema_class];
        }
        else
        {
            $target = midcom_helper_reflector_copy::get_target_properties($this->_object);
        }

        // Load the schemadb for searching the parent object
        $this->_load_schemadb($target['class'], $target['parent'], true);
        // Change the name for the parent field
        $this->_schemadb['object']->fields[$target['parent']]['title'] = $_MIDCOM->i18n->get_string('choose the target', 'midgard.admin.asgard');

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
                $_MIDCOM->relocate("__mfa/asgard/object/{$this->_request_data['default_mode']}/{$new_object->guid}/");
                break;

            case 'cancel':
                $_MIDCOM->relocate("__mfa/asgard/object/{$this->_request_data['default_mode']}/{$args[0]}/");
        }

        // Add Thickbox
        $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . '/midgard.admin.asgard/object_browser.js');
        $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . '/jQuery/thickbox/jquery-thickbox-3.1.pack.js');
        $this->add_stylesheet(MIDCOM_STATIC_URL . '/jQuery/thickbox/thickbox.css', 'screen');
        $_MIDCOM->add_jscript('var tb_pathToImage = "' . MIDCOM_STATIC_URL . '/jQuery/thickbox/loadingAnimation.gif"');

        // Add jQuery file for the checkbox operations
        $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . '/midgard.admin.asgard/jquery-copytree.js');
        $_MIDCOM->add_jscript('jQuery(document).ready(function(){jQuery("#midgard_admin_asgard_copytree").tree_checker();})');

        // Common hooks for Asgard
        midgard_admin_asgard_plugin::bind_to_object($this->_object, $handler_id, $data);

        // Set the page title
        switch ($handler_id)
        {
            case '____mfa-asgard-object_copy_tree':
                $data['page_title'] = sprintf($_MIDCOM->i18n->get_string('copy %s and its descendants', 'midgard.admin.asgard'), $this->_object->$target['label']);
                break;
            default:
                $data['page_title'] = sprintf($_MIDCOM->i18n->get_string('copy %s', 'midgard.admin.asgard'), $this->_object->$target['label']);
        }

        $data['target'] = $target;

        return true;
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
        if (   isset($target['id'])
            && $target['id'])
        {
            $link_properties = $target['reflector']->get_link_properties();
            $parent = $target['parent'];

            if (   !$link_properties
                || !isset($link_properties[$parent]))
            {
                throw new midcom_error('Failed to construct the target class object');
            }

            $class_name = $link_properties[$parent]['class'];
            $target_object = new $class_name($target['id']);

            if (   $target_object
                && $target_object->guid)
            {
                $copy->target = $target_object;
            }
            else
            {
                throw new midcom_error('Failed to get the target object');
            }
        }

        // Copying of parameters, metadata and such
        $copy->parameters = $this->_controller->datamanager->types['parameters']->convert_to_storage();
        $copy->metadata = $this->_controller->datamanager->types['metadata']->convert_to_storage();
        $copy->attachments = $this->_controller->datamanager->types['attachments']->convert_to_storage();
        $copy->privileges = $this->_controller->datamanager->types['privileges']->convert_to_storage();

        if ($this->_request_data['handler_id'] === '____mfa-asgard-object_copy_tree')
        {
            foreach ($_POST['all_objects'] as $guid)
            {
                if (!in_array($guid, $_POST['selected']))
                {
                    $copy->exclude[] = $guid;
                }
            }
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

        if (   !$new_object
            || !$new_object->guid)
        {
            throw new midcom_error('Failed to copy the object');
        }

        if ($this->_request_data['handler_id'] === '____mfa-asgard-object_copy_tree')
        {
            $_MIDCOM->uimessages->add($this->_l10n->get('midgard.admin.asgard'), $this->_l10n->get('copy successful, you have been relocated to the root of the new object tree'));
        }
        else
        {
            $_MIDCOM->uimessages->add($this->_l10n->get('midgard.admin.asgard'), $this->_l10n->get('copy successful, you have been relocated to the new object'));
        }
        return $new_object;
    }

    /**
     * Show copy style
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_copy($handler_id, array &$data)
    {
        midcom_show_style('midgard_admin_asgard_header');

        midcom_show_style('midgard_admin_asgard_middle');

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
        midcom_show_style('midgard_admin_asgard_footer');
    }
}
?>
