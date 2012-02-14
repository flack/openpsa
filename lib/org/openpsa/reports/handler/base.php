<?php
/**
 * @package org.openpsa.reports
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Baseclass for reports handler, provides some common methods
 *
 * @package org.openpsa.reports
 */
abstract class org_openpsa_reports_handler_base extends midcom_baseclasses_components_handler
{
    private $_datamanagers = array();
    var $module = false;

    abstract function _handler_generator($handler_id, array $args, array &$data);

    abstract function _show_generator($handler_id, array &$data);

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_generator_get($handler_id, array $args, array &$data)
    {
        $this->_set_active_leaf();
        midcom::get('auth')->require_valid_user();
        if (   !array_key_exists('org_openpsa_reports_query_data', $_REQUEST)
            || !is_array($_REQUEST['org_openpsa_reports_query_data']))
        {
            throw new midcom_error('query data not present or invalid');
        }

        // NOTE: This array must be a same format as we get from DM get_array() method
        $this->_request_data['query_data'] = $_REQUEST['org_openpsa_reports_query_data'];
        $this->_request_data['filename'] = 'get';

        $this->_handler_generator_style();
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_generator_get($handler_id, array &$data)
    {
        $this->_show_generator($handler_id, $data);
    }

    protected function _initialize_datamanager()
    {
        $_MIDCOM->load_library('midcom.helper.datamanager2');

        $this->_load_schemadb();

        // Initialize the datamanager with the schema
        $this->_datamanagers[$this->module] = new midcom_helper_datamanager2_datamanager($this->_schemadb);

        return true;
    }

    private function _load_query($identifier, $dm_key)
    {
        $query = new org_openpsa_reports_query_dba($identifier);

        // Load the query object to datamanager
        if (!$this->_datamanagers[$dm_key]->autoset_storage($query))
        {
            throw new midcom_error('Could not load query');
        }
        return $query;
    }

    /**
     * Internal helper, loads the controller for the current salesproject. Any error triggers a 500.
     */
    private function _load_edit_controller()
    {
        $this->_controller = midcom_helper_datamanager2_controller::create('simple');
        $this->_controller->schemadb =& $this->_schemadb;
        $this->_controller->set_storage($this->_request_data['query'], 'default');
        if (! $this->_controller->initialize())
        {
            throw new midcom_error("Failed to initialize a DM2 controller instance for document {$this->_document->id}.");
        }
    }

    /**
     * This is what Datamanager calls to actually create a query
     */
    function & dm2_create_callback(&$controller)
    {
        $query = new org_openpsa_reports_query_dba();
        $query->component = $this->_component;

        if (! $query->create())
        {
            debug_print_r('We operated on this object:', $query);
            throw new midcom_error("Failed to create a new project. Error: " . midcom_connection::get_error_string());
        }

        $this->_request_data['query'] = $query;

        return $query;
    }

    /**
     * Loads and prepares the schema database.
     *
     * The operations are done on all available schemas within the DB.
     */
    private function _load_schemadb()
    {
        $schema_snippet = $this->_config->get('schemadb_queryform_' . $this->module);
        $this->_schemadb = midcom_helper_datamanager2_schema::load_database($schema_snippet);
    }

    /**
     * Internal helper, fires up the creation mode controller. Any error triggers a 500.
     */
    private function _load_create_controller()
    {
        $this->_load_schemadb();
        $this->_controller = midcom_helper_datamanager2_controller::create('create');
        $this->_controller->schemadb =& $this->_schemadb;
        $this->_controller->schemaname = 'default';
        $this->_controller->callback_object =& $this;
        if (! $this->_controller->initialize())
        {
            throw new midcom_error("Failed to initialize a DM2 create controller.");
        }
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_query_form($handler_id, array $args, array &$data)
    {
        $this->_set_active_leaf();
        midcom::get('auth')->require_valid_user();

        if (isset($args[0]))
        {
            $data['query'] = $this->_load_query($args[0], $this->module);
            $data['query']->require_do('midgard:update');

            $this->_load_edit_controller();
        }
        else
        {
            $this->_load_create_controller();
        }

        // Process the form
        switch ($this->_controller->process_form())
        {
            case 'save':
                // Relocate to report view
                $prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
                midcom::get()->relocate($prefix . $this->module . '/' . $this->_request_data['query']->guid . "/");
                //this will exit

            case 'cancel':
                midcom::get()->relocate('');
                // This will exit
        }

        $this->_request_data['controller'] =& $this->_controller;
        $this->_request_data['datamanager'] = $this->_datamanagers[$this->module];

        org_openpsa_helpers::dm2_savecancel($this);

        if (isset($data['query']))
        {
            $breadcrumb_label =  sprintf($this->_l10n->get('edit report %s'), $data['query']->title);
        }
        else
        {
            $breadcrumb_label =  $this->_l10n->get('define custom report');
        }
        $this->add_breadcrumb("", $breadcrumb_label);
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_query_form($handler_id, array &$data)
    {
        midcom_show_style("{$this->module}_query_form");
    }

    private function _set_active_leaf()
    {
        // This should be overridden, but we default for 'generator_<module>'
        $this->set_active_leaf($this->_topic->id . ':generator_' . $this->module);
    }

    protected function _generator_load_redirect(&$args)
    {
        debug_add('Loading query object ' . $args[0]);
        $this->_request_data['query'] = $this->_load_query($args[0], $this->module);

        if (empty($args[1]))
        {
            debug_add('Filename part not specified in URL, generating');
            //We do not have filename in URL, generate one and redirect
            $timestamp = $this->_request_data['query']->metadata->created;
            if (!$timestamp)
            {
                $timestamp = time();
            }
            $filename = date('Y_m_d', $timestamp);
            if ($this->_request_data['query']->title)
            {
                $filename .= '_' . preg_replace('/[^a-z0-9-]/i', '_', strtolower($this->_request_data['query']->title));
            }
            $filename .= $this->_request_data['query']->extension;

            midcom::get()->relocate($this->module . '/' . $this->_request_data['query']->guid . '/' . $filename);
            //this will exit
        }
        $this->_request_data['filename'] = $args[1];

        //Get DM schema data to array
        $this->_request_data['query_data'] = $this->_datamanagers[$this->module]->get_content_raw();
    }

    public function _handler_generator_style()
    {
        //Handle style
        if (empty($this->_request_data['query_data']['style']))
        {
            debug_add('Empty style definition encountered, forcing builtin:basic');
            $this->_request_data['query_data']['style'] = 'builtin:basic';
        }
        if (!preg_match('/^builtin:(.+)/', $this->_request_data['query_data']['style']))
        {
            debug_add("appending '{$this->_request_data['query_data']['style']}' to substyle path");
            midcom::get('style')->append_substyle($this->_request_data['query_data']['style']);
        }

        //TODO: Check if we're inside DL if so do not force mimetype
        if (   !isset($this->_request_data['query_data']['skip_html_headings'])
            || empty($this->_request_data['query_data']['skip_html_headings']))
        {
            //Skip normal style, and force content type based on query data.
            $_MIDCOM->skip_page_style = true;
            debug_add('Forcing content type: ' . $this->_request_data['query_data']['mimetype']);
            midcom::get('cache')->content->content_type($this->_request_data['query_data']['mimetype']);
        }
    }

    /**
     * Convert midcom acl identifier to array of person ids
     */
    protected function _expand_resource($resource_id, $ret = array())
    {
        debug_add('Got resource_id: ' . $resource_id);
        $dba_obj = midcom::get('auth')->get_assignee($resource_id);

        self::_verify_cache('users', $this->_request_data);
        switch (get_class($dba_obj))
        {
            case 'midcom_core_group':
                $members = $dba_obj->list_members();
                if (is_array($members))
                {
                    foreach ($members as $core_user)
                    {
                        $user_obj = $core_user->get_storage();
                        debug_add(sprintf('Adding user %s (id: %s)', $core_user->name, $user_obj->id));
                        $ret[] = $user_obj->id;
                        $this->_request_data['object_cache'][$user_obj->guid] = $user_obj;
                        $this->_request_data['object_cache']['users'][$user_obj->id] =& $this->_request_data['object_cache'][$user_obj->guid];
                    }
                }
            break;
            case 'midcom_core_user':
                $user_obj = $dba_obj->get_storage();
                debug_add(sprintf('Adding user %s (id: %s)', $dba_obj->name, $user_obj->id));
                $ret[] = $user_obj->id;
                $this->_request_data['object_cache'][$user_obj->guid] = $user_obj;
                $this->_request_data['object_cache']['users'][$user_obj->id] =& $this->_request_data['object_cache'][$user_obj->guid];
            break;
            default:
                debug_add('Got unrecognized class for dba_obj: ' . get_class($dba_obj), MIDCOM_LOG_WARN);
            break;
        }
        return $ret;
    }

    protected static function _verify_cache($key, &$request_data)
    {
        if (   !array_key_exists('object_cache', $request_data)
            || !is_array($request_data['object_cache']))
        {
            $request_data['object_cache'] = array();
        }
        if ($key !== null)
        {
            if (   !array_key_exists($key, $request_data['object_cache'])
                || !is_array($request_data['object_cache'][$key]))
            {
                $request_data['object_cache'][$key] = array();
            }
        }
    }

    public static function &_get_cache($type, $id, &$request_data)
    {
        self::_verify_cache($type, $request_data);
        if (!array_key_exists($id, $request_data['object_cache'][$type]))
        {
            switch ($type)
            {
                case 'users':
                    $core_user = new midcom_core_user($id);
                    $obj = $core_user->get_storage();
                break;
                case 'groups':
                    $obj = new org_openpsa_contacts_group_dba($id);
                break;
                default:
                    $method = "_get_cache_obj_{$type}";
                    $classname = __CLASS__;
                    if (!method_exists($classname, $method))
                    {
                        // TODO: generate error
                        debug_add("Method '{$method}' not in class '{$classname}'", MIDCOM_LOG_WARN);
                        return false;
                    }
                    $obj = call_user_func(array($classname, $method), $id);
                break;
            }
            $request_data['object_cache'][$obj->guid] = $obj;
            $request_data['object_cache'][$type][$obj->id] =& $request_data['object_cache'][$obj->guid];
        }
        else
        {
            $obj =& $request_data['object_cache'][$type][$id];
        }
        return $request_data['object_cache'][$type][$obj->id];
    }

    private static function _get_cache_obj_tasks($id)
    {
        return new org_openpsa_projects_task_dba($id);
    }
}
?>