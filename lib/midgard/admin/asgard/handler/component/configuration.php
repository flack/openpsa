<?php
/**
 * @package midgard.admin.asgard
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: configuration.php 25318 2010-03-18 12:16:52Z indeyets $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Component configuration handler
 *
 * @package midgard.admin.asgard
 */
class midgard_admin_asgard_handler_component_configuration extends midcom_baseclasses_components_handler
{
    var $_lib = array();

    /**
     * Simple constructor
     *
     * @access public
     */
    function __construct()
    {
        $this->_component = 'midgard.admin.asgard';
        parent::__construct();
    }

    function _on_initialize()
    {
        $_MIDCOM->add_link_head
        (
            array
            (
                'rel' => 'stylesheet',
                'type' => 'text/css',
                'href' => MIDCOM_STATIC_URL . '/midgard.admin.asgard/libconfig.css',
            )
        );

        // Ensure we get the correct styles
        $_MIDCOM->style->prepend_component_styledir('midgard.admin.asgard');
        $_MIDCOM->skip_page_style = true;
    }

    function _prepare_toolbar($handler_id)
    {
        $this->_request_data['asgard_toolbar'] = new midcom_helper_toolbar();
        $this->_request_data['asgard_toolbar']->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "__mfa/asgard/components/configuration/{$this->_request_data['name']}/",
                MIDCOM_TOOLBAR_LABEL => $_MIDCOM->i18n->get_string('view', 'midcom'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/view.png',
            )
        );
        $this->_request_data['asgard_toolbar']->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "__mfa/asgard/components/configuration/edit/{$this->_request_data['name']}/",
                MIDCOM_TOOLBAR_LABEL => $_MIDCOM->i18n->get_string('edit', 'midcom'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/edit.png',
            )
        );

        switch ($handler_id)
        {
            case '____mfa-asgard-components_configuration_edit':
                $this->_request_data['asgard_toolbar']->disable_item("__mfa/asgard/components/configuration/edit/{$this->_request_data['name']}/");
                break;
            case '____mfa-asgard-components_configuration':
                $this->_request_data['asgard_toolbar']->disable_item("__mfa/asgard/components/configuration/{$this->_request_data['name']}/");
                break;
        }

        midgard_admin_asgard_plugin::get_common_toolbar($this->_request_data);
    }

    function _prepare_breadcrumbs($handler_id)
    {
        // Set the breadcrumb data
        $tmp = array();
        $tmp[] = array
        (
            MIDCOM_NAV_URL => '__mfa/asgard/',
            MIDCOM_NAV_NAME => $_MIDCOM->i18n->get_string('midgard.admin.asgard', 'midgard.admin.asgard'),
        );
        $tmp[] = array
        (
            MIDCOM_NAV_URL => '__mfa/asgard/components/',
            MIDCOM_NAV_NAME => $_MIDCOM->i18n->get_string('components', 'midgard.admin.asgard'),
        );
        $tmp[] = array
        (
            MIDCOM_NAV_URL => "__mfa/asgard/components/{$this->_request_data['name']}/",
            MIDCOM_NAV_NAME => $_MIDCOM->i18n->get_string($this->_request_data['name'], $this->_request_data['name']),
        );
        $tmp[] = array
        (
            MIDCOM_NAV_URL => "__mfa/asgard/components/configuration/{$this->_request_data['name']}/",
            MIDCOM_NAV_NAME => $_MIDCOM->i18n->get_string('component configuration', 'midcom'),
        );

        if ($handler_id == '____mfa-asgard-components_configuration_edit')
        {
            $tmp[] = array
            (
                MIDCOM_NAV_URL => "__mfa/asgard/components/configuration/{$this->_request_data['name']}/edit/",
                MIDCOM_NAV_NAME => $_MIDCOM->i18n->get_string('edit', 'midcom'),
            );
        }

        $_MIDCOM->set_custom_context_data('midcom.helper.nav.breadcrumb', $tmp);
    }

    function _load_configs($component, $object = null)
    {
        $lib = $_MIDCOM->componentloader->manifests[$component];
        $componentpath = MIDCOM_ROOT . $_MIDCOM->componentloader->path_to_snippetpath($component);

        // Load and parse the global config
        $cfg = midcom_baseclasses_components_interface::read_array_from_file("{$componentpath}/config/config.inc");
        if (! $cfg)
        {
            // Empty defaults
            $cfg = array();
        }

        $config = new midcom_helper_configuration($cfg);

        if ($object)
        {
            $topic_config = new midcom_helper_configuration($object, $component);
        }

        // Go for the sitewide default
        $cfg = midcom_baseclasses_components_interface::read_array_from_file("/etc/midgard/midcom/{$component}/config.inc");
        if ($cfg !== false)
        {
            $config->store($cfg, false);
        }

        // Finally, check the sitegroup config
        $cfg = midcom_baseclasses_components_interface::read_array_from_snippet("{$GLOBALS['midcom_config']['midcom_sgconfig_basedir']}/{$component}/config");
        if ($cfg !== false)
        {
            $config->store($cfg, false);
        }

        if (isset($topic_config))
        {
            $config->store($topic_config->_local);
        }

        return $config;
    }

    function _load_schemadb($component)
    {
        // Load SchemaDb
        $schemadb_config_path = $_MIDCOM->componentloader->path_to_snippetpath($component) . '/config/config_schemadb.inc';
        $schemadb = null;
        $schema = 'default';


        if (file_exists(MIDCOM_ROOT . $schemadb_config_path))
        {
            // Check that the schema is valid DM2 schema
            $schema_array = midcom_baseclasses_components_interface::read_array_from_file(MIDCOM_ROOT . $schemadb_config_path);
            if (isset($schema_array['config']))
            {
                $schema = 'config';
            }

            if (!isset($schema_array[$schema]['name']))
            {
                // This looks like DM2 schema
                $schemadb = midcom_helper_datamanager2_schema::load_database("file:/{$schemadb_config_path}");
            }

            // TODO: Log error on deprecated config schema?
        }

        if (!$schemadb)
        {
            // Create dummy schema. Naughty component would not provide config schema.
            $schemadb = midcom_helper_datamanager2_schema::load_database("file:/midgard/admin/asgard/config/schemadb_libconfig.inc");
        }
        $schemadb[$schema]->l10n_schema = $component;

        foreach($this->_request_data['config']->_global as $key => $value)
        {
            // try to sniff what fields are missing in schema
            if (!array_key_exists($key, $schemadb[$schema]->fields))
            {
                $schemadb[$schema]->append_field
                (
                    $key,
                    $this->_detect_schema($key, $value)
                );
                $schemadb[$schema]->fields[$key]['title'] = $_MIDCOM->i18n->get_string($schemadb[$schema]->fields[$key]['title'], $schemadb[$schema]->l10n_schema);
            }

            if (   !isset($this->_request_data['config']->_local[$key])
                || $this->_request_data['config']->_local[$key] == $this->_request_data['config']->_global[$key])
            {
                // No local configuration setting, note to user that this is the global value
                $schemadb[$schema]->fields[$key]['title'] = $_MIDCOM->i18n->get_string($schemadb[$schema]->fields[$key]['title'], $schemadb[$schema]->l10n_schema);
                $schemadb[$schema]->fields[$key]['title'] .= " <span class=\"global\">(" . $_MIDCOM->i18n->get_string('global value', 'midgard.admin.asgard') .")</span>";
            }
        }

        // Prepare defaults
        foreach($this->_request_data['config']->_merged as $key => $value)
        {
            if (!isset($schemadb[$schema]->fields[$key]))
            {
                // Skip
                continue;
            }

            if (is_array($value))
            {
                $schemadb[$schema]->fields[$key]['default'] = "array(\n" . $this->_draw_array($value, '    ') . ")";
            }
            else
            {
                $schemadb[$schema]->fields[$key]['default'] = $value;
            }
        }

        return $schemadb;
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_view($handler_id, $args, &$data)
    {
        $data['name'] = $args[0];
        if (!array_key_exists($data['name'], $_MIDCOM->componentloader->manifests))
        {
            $_MIDCOM->generate_error(MIDCOM_ERRNOTFOUND, "Component '{$data['name']}' was not found.");
            // This will exit
        }

        $data['config'] = $this->_load_configs($data['name']);

        $data['view_title'] = sprintf($this->_l10n->get('configuration for %s'), $data['name']);
        $this->_prepare_toolbar($handler_id);
        $_MIDCOM->set_pagetitle($data['view_title']);
        $this->_prepare_breadcrumbs($handler_id);

        return true;
    }


    /**
     * Show list of the style elements for the currently edited topic component
     *
     * @access private
     * @param string $handler_id Name of the used handler
     * @param mixed &$data Data passed to the show method
     */
    function _show_view($handler_id, &$data)
    {
        midgard_admin_asgard_plugin::asgard_header();

        midcom_show_style('midgard_admin_asgard_component_configuration_header');

        foreach($data['config']->_global as $key => $value)
        {
            $data['key'] = $_MIDCOM->i18n->get_string($key, $data['name']);
            $data['global'] = $this->_detect($data['config']->_global[$key]);

            if (isset($data['config']->_local[$key]))
            {
                $data['local'] = $this->_detect($data['config']->_local[$key]);
            }
            else
            {
                $data['local'] = $this->_detect(null);
            }

            midcom_show_style('midgard_admin_asgard_component_configuration_item');
        }
        midcom_show_style('midgard_admin_asgard_component_configuration_footer');
        midgard_admin_asgard_plugin::asgard_footer();

    }

    function _detect($value)
    {
        $type = gettype($value);

        switch ($type)
        {
            case 'boolean':
                $src = MIDCOM_STATIC_URL . '/stock-icons/16x16/cancel.png';
                $result = "<img src='{$src}'/>";

                if ($value === true)
                {
                    $result = "<img src='" . MIDCOM_STATIC_URL . "/stock-icons/16x16/ok.png'/>";
                }

                break;
            case 'array':
                $content = '<ul>';
                foreach ($value as $key => $val)
                {
                    $content .= "<li>{$key} => " . $this->_detect($val) . ",</li>\n";
                }
                $content .= '</ul>';
                $result = "<ul>\n<li>array</li>\n<li>(\n{$content}\n)</li>\n</ul>\n";
                break;
            case 'object':
                $result = '<strong>Object</strong>';
                break;
            case 'NULL':
                $result = "<img src='" . MIDCOM_STATIC_URL . "/stock-icons/16x16/cancel.png'/>";
                $result = '<strong>N/A</strong>';
                break;
            default:
                $result = $value;

        }

        return $result;

    }

    /**
     * Ensure the configuration is valid
     *
     * This method will throw an Exception if it is not.
     */
    private function _check_config($config)
    {
        $tmpfile = tempnam($GLOBALS['midcom_config']['midcom_tempdir'], 'midgard_admin_asgard_handler_component_configuration_');
        $fp = fopen($tmpfile, 'w');
        fwrite($fp, "<?php\n\$data = array({$config}\n);\n?>");
        fclose($fp);
        $parse_results = `php -l {$tmpfile}`;
        debug_push_class(__CLASS__, __FUNCTION__);
        debug_add("'php -l {$tmpfile}' returned: \n===\n{$parse_results}\n===\n");
        debug_pop();
        unlink($tmpfile);

        if (strstr($parse_results, 'Parse error'))
        {
            $line = preg_replace('/\n.+?on line (\d+?)\n.*\n/', '\1', $parse_results);
            throw new Exception(sprintf($_MIDCOM->i18n->get_string('type php: parse error in line %s', 'midcom.helper.datamanager2'), $line));
        }
    }

    /**
     * Save configuration values to a topic as "serialized" array
     *
     * @return boolean
     */
    function _save_snippet($config)
    {
        $sg_snippetdir = new midcom_db_snippetdir();
        $sg_snippetdir->get_by_path($GLOBALS['midcom_config']['midcom_sgconfig_basedir']);
        if (!$sg_snippetdir->guid)
        {
            // Create SG config snippetdir
            $sd = new midcom_db_snippetdir();
            $sd->up = 0;
            $sd->name = $GLOBALS['midcom_config']['midcom_sgconfig_basedir'];
            // remove leading slash from name
            $sd->name = preg_replace("/^\//", "", $sd->name);
            if (!$sd->create())
            {
                $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Failed to create snippetdir {$GLOBALS['midcom_config']['midcom_sgconfig_basedir']}: " . midcom_application::get_error_string());
            }
            $sg_snippetdir = new midcom_db_snippetdir($sd->guid);
        }

        $lib_snippetdir = new midcom_db_snippetdir();
        $lib_snippetdir->get_by_path("{$GLOBALS['midcom_config']['midcom_sgconfig_basedir']}/{$this->_request_data['name']}");
        if (!$lib_snippetdir->guid)
        {
            $sd = new midcom_db_snippetdir();
            $sd->up = $sg_snippetdir->id;
            $sd->name = $this->_request_data['name'];
            if (!$sd->create())
            {
                $_MIDCOM->generate_error(MIDCOM_ERRCRIT,"Failed to create snippetdir {$GLOBALS['midcom_config']['midcom_sgconfig_basedir']}/{$data['name']}: " . midcom_application::get_error_string());
            }
            $lib_snippetdir = new midcom_db_snippetdir($sd->guid);
        }

        $snippet = new midcom_db_snippet();
        $snippet->get_by_path("{$GLOBALS['midcom_config']['midcom_sgconfig_basedir']}/{$this->_request_data['name']}/config");
        if ($snippet->id == false)
        {
            $sn = new midcom_db_snippet();
            $sn->up = $lib_snippetdir->id;
            $sn->name = 'config';
            $sn->code = $config;
            return $sn->create();
        }

        $snippet->code = $config;
        return $snippet->update();
    }

    /**
     * Save configuration values to a topic as parameters
     *
     * @return boolean
     */
    function _save_topic($topic, $config)
    {
        foreach ($this->_request_data['config']->_global as $global_key => $global_val)
        {
            if (isset($config[$global_key]))
            {
                continue;
                // Skip the ones we will set next
            }

            // Clear unset params
            if ($topic->get_parameter($this->_request_data['name'], $global_key))
            {
                $topic->set_parameter($this->_request_data['name'], $global_key, '');
            }
        }

        foreach ($config as $key => $value)
        {
            if (   is_array($value)
                || is_object($value))
            {
                /**
                 * See http://trac.midgard-project.org/ticket/1442
                $topic->set_parameter($this->_request_data['name'], $key, "array(\n" . $this->_draw_array($value, '    ') . ")");
                 */
                 continue;
            }
            $topic->set_parameter($this->_request_data['name'], $key, $value);
        }

        return true;
    }

    private function _get_config_from_controller()
    {
        $post = $this->_controller->formmanager->form->_submitValues;
        $config_array = array();
        foreach ($this->_request_data['config']->_global as $key => $val)
        {
            if (isset($post[$key]))
            {
                $newval = $post[$key];
            }

            if (   is_a($this->_controller->datamanager->types[$key], 'midcom_helper_datamanager2_type_select')
                || is_a($this->_controller->datamanager->types[$key], 'midcom_helper_datamanager2_type_boolean'))
            {
                // We want the actual values regardless of widget
                $newval = $this->_controller->datamanager->types[$key]->convert_to_storage();
            }

            if (!isset($newval))
            {
                continue;
            }

            if (is_array($val))
            {
                //try make sure entries have the same format before deciding if there was a change
                $val = "array(\n" . $this->_draw_array($val, "    ") . ")";
                $newval = str_replace("\r\n", "\n", $newval);
            }

            if ($newval != $val)
            {
                $config_array[$key] = $newval;
            }
        }

        return $config_array;
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_edit($handler_id, $args, &$data)
    {
        $data['name'] = $args[0];
        if (!array_key_exists($data['name'], $_MIDCOM->componentloader->manifests))
        {
            $_MIDCOM->generate_error(MIDCOM_ERRNOTFOUND, "Component '{$data['name']}' was not found.");
            // This will exit
        }

        if ($handler_id == '____mfa-asgard-components_configuration_edit_folder')
        {
            $data['folder'] = new midcom_db_topic($args[1]);
            if (   !$data['folder']->guid
                || $data['folder']->component != $data['name'])
            {
                $_MIDCOM->generate_error(MIDCOM_ERRNOTFOUND, "Folder {$args[1]} not found for configuration.");
                // This will exit
            }

            $data['folder']->require_do('midgard:update');

            $data['config'] = $this->_load_configs($data['name'], $data['folder']);
        }
        else
        {
            $data['config'] = $this->_load_configs($data['name']);
        }

        $data['schemadb'] = $this->_load_schemadb($data['name']);

        $this->_controller = midcom_helper_datamanager2_controller::create('nullstorage');
        $this->_controller->schemadb =& $data['schemadb'];
        if (! $this->_controller->initialize())
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Failed to initialize a DM2 controller instance for configuration.");
        // This will exit.
        }

        switch ($this->_controller->process_form())
        {
            case 'save':
                $config_array = $this->_get_config_from_controller($this->_controller);

                $config = $this->_draw_array($config_array, '', $data['config']->_global);

                try
                {
                    $this->_check_config($config);
                }
                catch (Exception $e)
                {
                    $_MIDCOM->uimessages->add
                    (
                        $_MIDCOM->i18n->get_string('component configuration', 'midcom'),
                        sprintf($_MIDCOM->i18n->get_string('configuration save failed: %s', 'midgard.admin.asgard'), $e->getMessage()),
                        'error'
                    );
                    break;
                    // Get back to form
                }

                if ($handler_id == '____mfa-asgard-components_configuration_edit_folder')
                {
                    // Editing folder configuration
                    $this->_save_topic($data['folder'], $config_array);
                    $_MIDCOM->uimessages->add
                    (
                        $_MIDCOM->i18n->get_string('component configuration', 'midcom'),
                        $_MIDCOM->i18n->get_string('configuration saved successfully', 'midgard.admin.asgard'),
                        'ok'
                    );
                    $_MIDCOM->relocate("__mfa/asgard/components/configuration/edit/{$data['name']}/{$data['folder']->guid}/");
                    // This will exit
                }

                if ($this->_save_snippet($config))
                {
                    $_MIDCOM->uimessages->add
                    (
                        $_MIDCOM->i18n->get_string('component configuration', 'midcom'),
                        $_MIDCOM->i18n->get_string('configuration saved successfully', 'midgard.admin.asgard'),
                        'ok'
                    );
                }
                else
                {
                    $_MIDCOM->uimessages->add
                    (
                        $_MIDCOM->i18n->get_string('component configuration', 'midcom'),
                        sprintf($_MIDCOM->i18n->get_string('configuration save failed: %s', 'midgard.admin.asgard'), midcom_application::get_error_string()),
                        'error'
                    );
                }
                // *** FALL-THROUGH ***

            case 'cancel':
                if ($handler_id == '____mfa-asgard-components_configuration_edit_folder')
                {
                    $_MIDCOM->relocate("__mfa/asgard/object/view/{$data['folder']->guid}/");
                    // This will exit.
                }
                $_MIDCOM->relocate("__mfa/asgard/components/configuration/{$data['name']}/");
                // This will exit.
        }


        $data['controller'] =& $this->_controller;

        if ($handler_id == '____mfa-asgard-components_configuration_edit_folder')
        {
            midgard_admin_asgard_plugin::bind_to_object($data['folder'], $handler_id, $data);
            $data['view_title'] = sprintf($this->_l10n->get('edit configuration for %s folder %s'), $data['name'], $data['folder']->extra);
        }
        else
        {
            $this->_prepare_toolbar($handler_id);
            $data['view_title'] = sprintf($this->_l10n->get('edit configuration for %s'), $data['name']);
            $this->_prepare_breadcrumbs($handler_id);
        }

        $_MIDCOM->set_pagetitle($data['view_title']);

        return true;
    }


    /**
     * Show list of the style elements for the currently edited topic component
     *
     * @access private
     * @param string $handler_id Name of the used handler
     * @param mixed &$data Data passed to the show method
     */
    function _show_edit($handler_id, &$data)
    {
        midgard_admin_asgard_plugin::asgard_header();
        midcom_show_style('midgard_admin_asgard_component_configuration_edit');
        midgard_admin_asgard_plugin::asgard_footer();
    }

    function _detect_schema($key, $value)
    {
        $result = array
        (
            'title'       => $key,
            'type'        => 'text',
            'widget'      => 'text',
        );

        $type = gettype($value);
        switch ($type)
        {
            case "boolean":
                $result['type'] = 'boolean';
                $result['widget'] = 'checkbox';
                break;
            case "array":
                $result['widget'] = 'textarea';

                if (isset($this->_request_data['folder']))
                {
                    // Complex Array fields should be readonly for topics as we cannot store and read them properly with parameters
                    $result['readonly'] = true;
                }

                break;
            default:
                if (preg_match("/\n/", $value))
                {
                    $result['widget'] = 'textarea';
                }
        }

        return $result;

    }

    function _draw_array($array, $prefix = '', $type_array = null)
    {
        $data = '';
        foreach ($array as $key => $val)
        {
            $data .= $prefix;
            if (!is_numeric($key))
            {
                $data .= "'{$key}' => ";
            }

            $type = gettype($val);
            if (   $type_array
                && isset($type_array[$key]))
            {
                $type = gettype($type_array[$key]);
            }

            switch($type)
            {
                case 'boolean':
                    $data .= ($val)?'true':'false';
                    break;
                case 'array':
                    if (empty($val))
                    {
                        $data .= 'array()';
                    }
                    else
                    {
                        if (is_string($val))
                        {
                            $res = eval("\$val = $val;");
                            /*if (!$res)
                            {
                                $data .= 'array()';
                                break;
                            }*/
                        }
                        $data .= "array\n{$prefix}(\n" . $this->_draw_array($val, "{$prefix}    ") . "{$prefix})";
                    }
                    break;

                default:
                    if (is_numeric($val))
                    {
                        $data .= $val;
                    }
                    else
                    {
                        $data .= "'{$val}'";
                    }
            }

            $data .= ",\n";

        }
        return $data;
    }

}
?>