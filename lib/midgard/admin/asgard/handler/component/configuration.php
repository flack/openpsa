<?php
/**
 * @package midgard.admin.asgard
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Component configuration handler
 *
 * @package midgard.admin.asgard
 */
class midgard_admin_asgard_handler_component_configuration extends midcom_baseclasses_components_handler
implements midcom_helper_datamanager2_interfaces_nullstorage
{
    private $_controller;

    private $_schema_name = 'default';

    public function _on_initialize()
    {
        $this->add_stylesheet(MIDCOM_STATIC_URL . '/midgard.admin.asgard/libconfig.css');
    }

    private function _prepare_toolbar($handler_id)
    {
        $buttons = array
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "__mfa/asgard/components/configuration/{$this->_request_data['name']}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('view'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/view.png',
            ),
            array
            (
                MIDCOM_TOOLBAR_URL => "__mfa/asgard/components/configuration/edit/{$this->_request_data['name']}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('edit'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/edit.png',
            )
        );
        $this->_request_data['asgard_toolbar']->add_items($buttons);

        switch ($handler_id) {
            case '____mfa-asgard-components_configuration_edit':
                $this->_request_data['asgard_toolbar']->disable_item("__mfa/asgard/components/configuration/edit/{$this->_request_data['name']}/");
                break;
            case '____mfa-asgard-components_configuration':
                $this->_request_data['asgard_toolbar']->disable_item("__mfa/asgard/components/configuration/{$this->_request_data['name']}/");
                break;
        }
    }

    /**
     * Set the breadcrumb data
     */
    private function _prepare_breadcrumbs($handler_id)
    {
        $this->add_breadcrumb('__mfa/asgard/', $this->_l10n->get('midgard.admin.asgard'));
        $this->add_breadcrumb('__mfa/asgard/components/', $this->_l10n->get('components'));

        $this->add_breadcrumb
        (
            "__mfa/asgard/components/{$this->_request_data['name']}/",
            midcom::get()->i18n->get_string($this->_request_data['name'], $this->_request_data['name'])
        );
        $this->add_breadcrumb
        (
            "__mfa/asgard/components/configuration/{$this->_request_data['name']}/",
            $this->_l10n_midcom->get('component configuration')
        );

        if ($handler_id == '____mfa-asgard-components_configuration_edit') {
            $this->add_breadcrumb
            (
                "__mfa/asgard/components/configuration/{$this->_request_data['name']}/edit/",
                $this->_l10n_midcom->get('edit')
            );
        }
    }

    private function _load_configs($component, $object = null)
    {
        $config = midcom_baseclasses_components_configuration::get($component, 'config');

        if ($object) {
            $topic_config = new midcom_helper_configuration($object, $component);
            $config->store($topic_config->_local, false);
        }

        return $config;
    }

    public function load_schemadb()
    {
        // Load SchemaDb
        $schemadb_config_path = midcom::get()->componentloader->path_to_snippetpath($this->_request_data['name']) . '/config/config_schemadb.inc';
        $schema = 'default';

        if (file_exists($schemadb_config_path)) {
            // Check that the schema is valid DM2 schema
            $schema_array = midcom_baseclasses_components_configuration::read_array_from_file($schemadb_config_path);
            if (isset($schema_array['config'])) {
                $schema = 'config';
            }

            $schemadb = midcom_helper_datamanager2_schema::load_database($schema_array);
            // TODO: Log error on deprecated config schema?
        } else {
            // Create dummy schema. Naughty component would not provide config schema.
            $schemadb = midcom_helper_datamanager2_schema::load_database("file:/midgard/admin/asgard/config/schemadb_libconfig.inc");
        }
        $schemadb[$schema]->l10n_schema = $this->_i18n->get_l10n($this->_request_data['name']);

        foreach ($this->_request_data['config']->_global as $key => $value) {
            // try to sniff what fields are missing in schema
            if (!array_key_exists($key, $schemadb[$schema]->fields)) {
                $schemadb[$schema]->append_field($key, $this->_detect_schema($key, $value));
                $schemadb[$schema]->fields[$key]['title'] = $schemadb[$schema]->l10n_schema->get($schemadb[$schema]->fields[$key]['title']);
            }

            if (   !isset($this->_request_data['config']->_local[$key])
                || $this->_request_data['config']->_local[$key] == $this->_request_data['config']->_global[$key]) {
                // No local configuration setting, note to user that this is the global value
                $schemadb[$schema]->fields[$key]['title'] = $schemadb[$schema]->l10n_schema->get($schemadb[$schema]->fields[$key]['title']);
                $schemadb[$schema]->fields[$key]['title'] .= " <span class=\"global\">(" . $this->_l10n->get('global value') .")</span>";
            }
        }

        // Prepare defaults
        $config = array_intersect_key($this->_request_data['config']->get_all(), $schemadb[$schema]->fields);
        foreach ($config as $key => $value) {
            if (is_array($value)) {
                $schemadb[$schema]->fields[$key]['default'] = "array(\n" . $this->_draw_array($value, '    ') . ")";
            } else {
                $schemadb[$schema]->fields[$key]['default'] = $value;
            }
        }

        $this->_schema_name = $schema;

        return $schemadb;
    }

    public function get_schema_name()
    {
        return $this->_schema_name;
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_view($handler_id, array $args, array &$data)
    {
        $data['name'] = $args[0];
        if (!midcom::get()->componentloader->is_installed($data['name'])) {
            throw new midcom_error_notfound("Component {$data['name']} was not found.");
        }

        $data['config'] = $this->_load_configs($data['name']);

        $data['view_title'] = sprintf($this->_l10n->get('configuration for %s'), $data['name']);
        $this->_prepare_toolbar($handler_id);
        $this->_prepare_breadcrumbs($handler_id);
        return new midgard_admin_asgard_response($this, '_show_view');
    }

    /**
     * @param string $handler_id Name of the used handler
     * @param array &$data Data passed to the show method
     */
    public function _show_view($handler_id, array &$data)
    {
        midcom_show_style('midgard_admin_asgard_component_configuration_header');

        foreach ($data['config']->_global as $key => $value) {
            $data['key'] = $this->_i18n->get_string($key, $data['name']);
            $data['global'] = $this->_detect($value);

            if (isset($data['config']->_local[$key])) {
                $data['local'] = $this->_detect($data['config']->_local[$key]);
            } else {
                $data['local'] = $this->_detect(null);
            }

            midcom_show_style('midgard_admin_asgard_component_configuration_item');
        }
        midcom_show_style('midgard_admin_asgard_component_configuration_footer');
    }

    private function _detect($value)
    {
        $type = gettype($value);

        switch ($type) {
            case 'boolean':
                $src = MIDCOM_STATIC_URL . '/stock-icons/16x16/cancel.png';
                $result = "<img src='{$src}'/>";

                if ($value === true) {
                    $result = "<img src='" . MIDCOM_STATIC_URL . "/stock-icons/16x16/ok.png'/>";
                }

                break;
            case 'array':
                $content = '<ul>';
                foreach ($value as $key => $val) {
                    $content .= "<li>{$key} => " . $this->_detect($val) . ",</li>\n";
                }
                $content .= '</ul>';
                $result = "<ul>\n<li>array</li>\n<li>(\n{$content}\n)</li>\n</ul>\n";
                break;
            case 'object':
                $result = '<strong>Object</strong>';
                break;
            case 'NULL':
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
     * @throws midcom_error
     */
    private function _check_config($config)
    {
        $tmpfile = tempnam(midcom::get()->config->get('midcom_tempdir'), 'midgard_admin_asgard_handler_component_configuration_');
        file_put_contents($tmpfile, "<?php\n\$data = array({$config}\n);\n?>");
        $parse_results = `php -l {$tmpfile}`;
        debug_add("'php -l {$tmpfile}' returned: \n===\n{$parse_results}\n===\n");
        unlink($tmpfile);

        if (strstr($parse_results, 'Parse error')) {
            $line = preg_replace('/\n.+?on line (\d+?)\n.*\n/', '\1', $parse_results);
            throw new midcom_error(sprintf($this->_i18n->get_string('type php: parse error in line %s', 'midcom.helper.datamanager2'), $line));
        }
    }

    /**
     * Save configuration values to a topic as "serialized" array
     *
     * @return boolean
     */
    private function _save_snippet($config)
    {
        $basedir = midcom::get()->config->get('midcom_sgconfig_basedir');
        $sg_snippetdir = new midcom_db_snippetdir();
        $sg_snippetdir->get_by_path($basedir);
        if (!$sg_snippetdir->guid) {
            // Create SG config snippetdir
            $sd = new midcom_db_snippetdir();
            $sd->up = 0;
            $sd->name = $basedir;
            // remove leading slash from name
            $sd->name = preg_replace("/^\//", "", $sd->name);
            if (!$sd->create()) {
                throw new midcom_error("Failed to create snippetdir {$basedir}: " . midcom_connection::get_error_string());
            }
            $sg_snippetdir = new midcom_db_snippetdir($sd->guid);
        }

        $lib_snippetdir = new midcom_db_snippetdir();
        $lib_snippetdir->get_by_path("{$basedir}/{$this->_request_data['name']}");
        if (!$lib_snippetdir->guid) {
            $sd = new midcom_db_snippetdir();
            $sd->up = $sg_snippetdir->id;
            $sd->name = $this->_request_data['name'];
            if (!$sd->create()) {
                throw new midcom_error("Failed to create snippetdir {$basedir}/{$sd->name}: " . midcom_connection::get_error_string());
            }
            $lib_snippetdir = new midcom_db_snippetdir($sd->guid);
        }

        $snippet = new midcom_db_snippet();
        $snippet->get_by_path("{$basedir}/{$this->_request_data['name']}/config");
        if ($snippet->id == false) {
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
     */
    private function _save_topic($topic, $config)
    {
        foreach (array_keys($this->_request_data['config']->_global) as $global_key) {
            if (isset($config[$global_key])) {
                continue;
                // Skip the ones we will set next
            }

            // Clear unset params
            if ($topic->get_parameter($this->_request_data['name'], $global_key)) {
                $topic->set_parameter($this->_request_data['name'], $global_key, '');
            }
        }

        foreach ($config as $key => $value) {
            if (   is_array($value)
                || is_object($value)) {
                /**
                 * See http://trac.midgard-project.org/ticket/1442
                $topic->set_parameter($this->_request_data['name'], $key, "array(\n" . $this->_draw_array($value, '    ') . ")");
                 */
                 continue;
            }
            $topic->set_parameter($this->_request_data['name'], $key, $value);
        }
    }

    private function _get_config_from_controller()
    {
        $post = $this->_controller->formmanager->form->getSubmitValues();
        $config_array = array();
        foreach ($this->_request_data['config']->_global as $key => $val) {
            if (isset($post[$key])) {
                $newval = $post[$key];
            }

            if (   is_a($this->_controller->datamanager->types[$key], 'midcom_helper_datamanager2_type_select')
                || is_a($this->_controller->datamanager->types[$key], 'midcom_helper_datamanager2_type_boolean')) {
                // We want the actual values regardless of widget
                $newval = $this->_controller->datamanager->types[$key]->convert_to_storage();
            }

            if (!isset($newval)) {
                continue;
            }

            if (is_array($val)) {
                //try make sure entries have the same format before deciding if there was a change
                $val = "array(\n" . $this->_draw_array($val, "    ") . ")";
                $newval = str_replace("\r\n", "\n", $newval);
            }

            if ($newval != $val) {
                $config_array[$key] = $newval;
            }
        }

        return $config_array;
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_edit($handler_id, array $args, array &$data)
    {
        $data['name'] = $args[0];
        if (!midcom::get()->componentloader->is_installed($data['name'])) {
            throw new midcom_error_notfound("Component {$data['name']} was not found.");
        }

        if ($handler_id == '____mfa-asgard-components_configuration_edit_folder') {
            $data['folder'] = new midcom_db_topic($args[1]);
            if ($data['folder']->component != $data['name']) {
                throw new midcom_error_notfound("Folder {$args[1]} not found for configuration.");
            }

            $data['folder']->require_do('midgard:update');

            $data['config'] = $this->_load_configs($data['name'], $data['folder']);
        } else {
            $data['config'] = $this->_load_configs($data['name']);
        }

        $this->_controller = $this->get_controller('nullstorage');

        switch ($this->_controller->process_form()) {
            case 'save':
                $this->_save_configuration($data);
                // *** FALL-THROUGH ***

            case 'cancel':
                if ($handler_id == '____mfa-asgard-components_configuration_edit_folder') {
                    return new midcom_response_relocate("__mfa/asgard/object/view/{$data['folder']->guid}/");
                }
                return new midcom_response_relocate("__mfa/asgard/components/configuration/{$data['name']}/");
        }

        $data['controller'] = $this->_controller;

        if ($handler_id == '____mfa-asgard-components_configuration_edit_folder') {
            midgard_admin_asgard_plugin::bind_to_object($data['folder'], $handler_id, $data);
            $data['view_title'] = sprintf($this->_l10n->get('edit configuration for %s folder %s'), $data['name'], $data['folder']->extra);
        } else {
            $this->_prepare_toolbar($handler_id);
            $data['view_title'] = sprintf($this->_l10n->get('edit configuration for %s'), $data['name']);
            $this->_prepare_breadcrumbs($handler_id);
        }

        return new midgard_admin_asgard_response($this, '_show_edit');
    }

    private function _save_configuration(array $data)
    {
        $config_array = $this->_get_config_from_controller();

        $config = $this->_draw_array($config_array, '', $data['config']->_global);

        try {
            $this->_check_config($config);
        } catch (Exception $e) {
            midcom::get()->uimessages->add
            (
                $this->_l10n_midcom->get('component configuration'),
                sprintf($this->_l10n->get('configuration save failed: %s'), $e->getMessage()),
                'error'
            );
            return;
            // Get back to form
        }

        if ($data['handler_id'] == '____mfa-asgard-components_configuration_edit_folder') {
            // Editing folder configuration
            $this->_save_topic($data['folder'], $config_array);
            midcom::get()->uimessages->add
            (
                $this->_l10n_midcom->get('component configuration'),
                $this->_l10n->get('configuration saved successfully')
            );
            midcom::get()->relocate("__mfa/asgard/components/configuration/edit/{$data['name']}/{$data['folder']->guid}/");
            // This will exit
        }

        if ($this->_save_snippet($config)) {
            midcom::get()->uimessages->add
            (
                $this->_l10n_midcom->get('component configuration'),
                $this->_l10n->get('configuration saved successfully')
            );
        } else {
            midcom::get()->uimessages->add
            (
                $this->_l10n_midcom->get('component configuration'),
                sprintf($this->_l10n->get('configuration save failed: %s'), midcom_connection::get_error_string()),
                'error'
            );
        }
    }

    /**
     * @param string $handler_id Name of the used handler
     * @param array &$data Data passed to the show method
     */
    public function _show_edit($handler_id, array &$data)
    {
        midcom_show_style('midgard_admin_asgard_component_configuration_edit');
    }

    private function _detect_schema($key, $value)
    {
        $result = array
        (
            'title'       => $key,
            'type'        => 'text',
            'widget'      => 'text',
        );

        $type = gettype($value);
        switch ($type) {
            case "boolean":
                $result['type'] = 'boolean';
                $result['widget'] = 'checkbox';
                break;
            case "array":
                $result['widget'] = 'textarea';

                if (isset($this->_request_data['folder'])) {
                    // Complex Array fields should be readonly for topics as we cannot store and read them properly with parameters
                    $result['readonly'] = true;
                }

                break;
            default:
                if (preg_match("/\n/", $value)) {
                    $result['widget'] = 'textarea';
                }
        }

        return $result;
    }

    private function _draw_array($array, $prefix = '', $type_array = null)
    {
        $data = '';
        foreach ($array as $key => $val) {
            $data .= $prefix;
            if (!is_numeric($key)) {
                $data .= "'{$key}' => ";
            }

            $type = gettype($val);
            if (   $type_array
                && isset($type_array[$key])) {
                $type = gettype($type_array[$key]);
            }

            if ($type === 'boolean') {
                $data .= ($val) ? 'true' : 'false';
            } elseif ($type === 'array') {
                if (empty($val)) {
                    $data .= 'array()';
                } else {
                    if (is_string($val)) {
                        eval("\$val = $val;");
                    }
                    $data .= "array\n{$prefix}(\n" . $this->_draw_array($val, "{$prefix}    ") . "{$prefix})";
                }
            } elseif (is_numeric($val)) {
                $data .= $val;
            } else {
                $data .= "'{$val}'";
            }

            $data .= ",\n";
        }
        return $data;
    }
}
