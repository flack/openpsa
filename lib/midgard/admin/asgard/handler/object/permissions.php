<?php
/**
 * @package midgard.admin.asgard
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Permissions interface
 *
 * @package midgard.admin.asgard
 */
class midgard_admin_asgard_handler_object_permissions extends midcom_baseclasses_components_handler
implements midcom_helper_datamanager2_interfaces_edit
{
    /**
     * The object whose permissions we handle
     *
     * @var mixed
     */
    private $_object = null;

    /**
     * The Controller of the object used for editing
     *
     * @var midcom_helper_datamanager2_controller_simple
     */
    private $_controller = null;

    /**
     * Privileges we're managing here
     *
     * @var Array
     */
    private $_privileges = array();

    /**
     * Table header
     *
     * @var String
     */
    private $_header = '';

    /**
     * Available row labels
     *
     * @var Array
     */
    private $_row_labels = array();

    /**
     * Rendered row labels
     *
     * @var Array
     */
    private $_rendered_row_labels = array();

    /**
     * Rendered row actions
     *
     * @var Array
     */
    private $_rendered_row_actions = array();

    public function _on_initialize()
    {
        $this->_privileges[] = 'midgard:read';
        $this->_privileges[] = 'midgard:create';
        $this->_privileges[] = 'midgard:update';
        $this->_privileges[] = 'midgard:delete';
        $this->_privileges[] = 'midgard:attachments';
        $this->_privileges[] = 'midgard:parameters';
        $this->_privileges[] = 'midgard:owner';

        if (midcom::get('config')->get('metadata_approval'))
        {
            $this->_privileges[] = 'midcom:approve';
        }

        $script = "function submit_privileges(form){jQuery('#submit_action',form).attr({name: 'midcom_helper_datamanager2_add'}).val('add');form.submit();};function applyRowClasses(){jQuery('.maa_permissions_items tr.maa_permissions_rows_row:odd').addClass('odd');jQuery('.maa_permissions_items tr.maa_permissions_rows_row:even').addClass('even');};";
        midcom::get('head')->add_jscript($script);

        $this->add_stylesheet(MIDCOM_STATIC_URL . '/midgard.admin.asgard/permissions/layout.css');
    }

    /**
     * Simple helper which references all important members to the request data listing
     * for usage within the style listing.
     */
    private function _prepare_request_data()
    {
        $this->_request_data['object'] =& $this->_object;
        $this->_request_data['controller'] =& $this->_controller;
    }

    /**
     * Load component-defined additional privileges
     */
    private function _load_component_privileges()
    {
        $component_loader = midcom::get('componentloader');

        // Store temporarily the requested object
        $tmp = $this->_object;

        $i = 0;
        while (   !empty($tmp->guid)
               && !midcom::get('dbfactory')->is_a($tmp, 'midgard_topic')
               && $i < 100)
        {
            // Get the parent; wishing eventually to get a topic
            $tmp = $tmp->get_parent();
            $i++;
        }

        // If the temporary object eventually reached a topic, fetch its manifest
        if (midcom::get('dbfactory')->is_a($tmp, 'midgard_topic'))
        {
            $current_manifest = $component_loader->manifests[$tmp->component];
        }
        else
        {
            $current_manifest = $component_loader->manifests[midcom_core_context::get()->get_key(MIDCOM_CONTEXT_COMPONENT)];
        }

        foreach ($current_manifest->privileges as $privilege => $default_value)
        {
            $this->_privileges[] = $privilege;
        }

        if (!empty($current_manifest->customdata['midgard.admin.asgard.acl']['extra_privileges']))
        {
            foreach ($current_manifest->customdata['midgard.admin.asgard.acl']['extra_privileges'] as $privilege)
            {
                if (!strpos($privilege, ':'))
                {
                    // Only component specified
                    // TODO: load components manifest and add privileges from there
                    continue;
                }
                $this->_privileges[] = $privilege;
            }
        }

        // In addition, give component configuration privileges if we're in topic
        if (midcom::get('dbfactory')->is_a($this->_object, 'midgard_topic'))
        {
            $this->_privileges[] = 'midcom.admin.folder:topic_management';
            $this->_privileges[] = 'midcom.admin.folder:template_management';
            $this->_privileges[] = 'midcom:component_config';
            $this->_privileges[] = 'midcom:urlname';
            if (midcom::get('config')->get('symlinks'))
            {
                $this->_privileges[] = 'midcom.admin.folder:symlinks';
            }
        }
    }

    /**
     * Static helper
     */
    public static function resolve_object_title($object)
    {
        $object_reflector = midcom_helper_reflector::get($object);
        return $object_reflector->get_object_label($object);
    }

    /**
     * Generates, loads and prepares the schema database.
     *
     * The operations are done on all available schemas within the DB.
     */
    public function load_schemadb()
    {
        $schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_permissions'));

        // Populate additional assignee selector
        $additional_assignees = array
        (
            '' => '',
            'EVERYONE' => $this->_l10n->get('EVERYONE'),
            'USERS' => $this->_l10n->get('USERS'),
            'ANONYMOUS' => $this->_l10n->get('ANONYMOUS')
        );

        // List groups as potential assignees
        $qb = midcom_db_group::new_query_builder();

        $groups = $qb->execute();
        foreach ($groups as $group)
        {
            $additional_assignees["group:{$group->guid}"] = $group->get_label();
        }

        $assignees = array();

        // Populate all resources having existing privileges
        $existing_privileges = $this->_object->get_privileges();

        foreach ($existing_privileges as $privilege)
        {
            if ($privilege->is_magic_assignee())
            {
                // This is a magic assignee
                $label = $this->_l10n->get($privilege->assignee);
            }
            else
            {
                //Inconsistent privilige base will mess here. Let's give a chance to remove ghosts
                $assignee = midcom::get('auth')->get_assignee($privilege->assignee);

                if (is_object($assignee))
                {
                    $label = $assignee->name;
                }
                else
                {
                    $label = $this->_l10n->get('ghost assignee for '. $privilege->assignee);
                }
            }

            $assignees[$privilege->assignee] = $label;

            $key = str_replace(':', '_', $privilege->assignee);
            if (!isset($this->_row_labels[$key]))
            {
                $this->_row_labels[$key] = $label;
            }

            // This one is already an assignee, remove from 'Add assignee' options
            if (array_key_exists($privilege->assignee, $additional_assignees))
            {
                unset($additional_assignees[$privilege->assignee]);
            }
        }

        asort($additional_assignees);

        // Add the 'Add assignees' choices to schema
        $schemadb['privileges']->fields['add_assignee']['type_config']['options'] = $additional_assignees;

        $header = '';

        $header_items = array();

        foreach ($assignees as $assignee => $label)
        {
            foreach ($this->_privileges as $privilege)
            {
                $privilege_components = explode(':', $privilege);
                if (   $privilege_components[0] == 'midcom'
                    || $privilege_components[0] == 'midgard')
                {
                    // This is one of the core privileges, we handle it
                    $privilege_label = $privilege;
                }
                else
                {
                    // This is a component-specific privilege, call component to localize it
                    $privilege_label = midcom::get('i18n')->get_string("privilege {$privilege_components[1]}", $privilege_components[0]);
                }

                if (! isset($header_items[$privilege_label]))
                {
                    $header_items[$privilege_label] = "        <th scope=\"col\" class=\"{$privilege_components[1]}\"><span>" . str_replace(' ', "\n", $this->_l10n->get($privilege_label)) . "</span></th>\n";
                }

                $schemadb['privileges']->append_field(str_replace(':', '_', $assignee) . '_' . str_replace(':', '_', str_replace('.', '_', $privilege)), Array
                    (
                        'title' => $privilege_label,
                        'helptext'    => sprintf($this->_l10n->get('sets privilege %s'), $privilege),
                        'storage' => null,
                        'type' => 'privilege',
                        'type_config' => Array
                        (
                            'privilege_name' => $privilege,
                            'assignee'       => $assignee,
                        ),
                        'widget' => 'privilegeselection',
                    )
                );
            }
        }

        $header .= "        <th align=\"left\" scope=\"col\" class=\"assignee_name\"><span>&nbsp;</span></th>\n";
        foreach ($header_items as $key => $item)
        {
            $header .= $item;
        }
        $header .= "        <th scope=\"col\" class=\"row_actions\"><span>&nbsp;</span></th>\n";

        $this->_header = $header;
        return $schemadb;
    }

    public function get_schema_name()
    {
        return 'privileges';
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
        $this->_object = midcom::get('dbfactory')->get_object_by_guid($args[0]);
        $this->_object->require_do('midgard:privileges');
        midcom::get('auth')->require_user_do('midgard.admin.asgard:manage_objects', null, 'midgard_admin_asgard_plugin');

        $script = "
            applyRowClasses();
        ";
        midcom::get('head')->add_jquery_state_script($script);

        // Load possible additional component privileges
        $this->_load_component_privileges();

        // Load the datamanager controller
        $this->_controller = $this->get_controller('simple', $this->_object);

        if (   isset($_POST['midcom_helper_datamanager2_add'])
            && !empty($_POST['add_assignee']))
        {
            $this->_object->set_privilege('midgard:read', $_POST['add_assignee']);
            return new midcom_response_relocate("__mfa/asgard/object/permissions/{$this->_object->guid}/");
        }

        switch ($this->_controller->process_form())
        {
            case 'save':
                //Fall-through
            case 'cancel':
                return new midcom_response_relocate("__mfa/asgard/object/view/{$this->_object->guid}/");
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
        $this->_generate_editor($data);

        midcom_show_style('midgard_admin_asgard_object_permissions');
    }

    private function _generate_editor(&$data)
    {
        $data['editor_rows'] = '';

        $form_start = "<form ";
        foreach ($this->_controller->formmanager->form->_attributes as $key => $value)
        {
            $form_start .= "{$key}=\"{$value}\" ";
        }
        $form_start .= "/>\n";

        $data['editor_header_form_start'] = $form_start;
        $data['editor_header_form_end'] = "</form>\n";

        $data['editor_header_titles'] = $this->_header;

        $data['editor_header'] = '';

        $priv_item_cnt = count($this->_privileges);

        $s = 0;
        foreach ($this->_controller->formmanager->form->_elements as $row)
        {
            if (is_a($row, 'HTML_QuickForm_hidden'))
            {
                $html = "<input  ";
                foreach ($row->_attributes as $key => $value)
                {
                    $html .= "{$key}=\"{$value}\" ";
                }
                $html .= "/>\n";

                $data['editor_header_form_start'] .= $html;
            }

            if (is_a($row, 'HTML_QuickForm_select'))
            {
                $html = "  <div class=\"assignees\">\n";
                $html .= "    <label for=\"{$row->_attributes['id']}\">\n<span class=\"field_text\">{$row->_label}</span>\n";
                $html .= $this->_render_select($row);
                $html .= "    </label>\n";
                $html .= "  </div>\n";

                $data['editor_header_assignees'] = $html;

                $html = '';
            }

            if (is_a($row, 'HTML_QuickForm_group'))
            {
                if ($row->_name == 'form_toolbar')
                {
                    $form_toolbar_html = "  <div class=\"actions\">\n";
                    foreach ($row->_elements as $k => $element)
                    {
                        if (is_a($element, 'HTML_QuickForm_submit'))
                        {
                            $form_toolbar_html .= $this->_render_button($element);
                        }
                    }
                    $form_toolbar_html .= "  </div>\n";
                    continue;
                }

                $html = $this->_render_row_label($row->_name);

                foreach ($row->_elements as $k => $element)
                {
                    if (is_a($element, 'HTML_QuickForm_select'))
                    {
                        $html .= $this->_render_select($element);
                    }
                    if (is_a($element, 'HTML_QuickForm_static'))
                    {
                        if (strpos($element->_attributes['name'], 'holder_start') !== false)
                        {
                            $priv_class = $this->_get_row_value_class($row->_name);
                            $html .= "      <td class=\"row_value {$priv_class}\">\n";
                        }

                        $html .= $this->_render_static($element);
                        if (strpos($element->_attributes['name'], 'initscripts') !== false)
                        {
                            $html .= "      </td>\n";
                        }
                    }
                }

                $s++;

                if ($s == $priv_item_cnt)
                {
                    $s = 0;
                    $html .= $this->_render_row_actions($row->_name);
                    $html .= "    </tr>\n";
                }

                $data['editor_rows'] .= $html;
            }
        }

        $footer = "  <input type=\"hidden\" name=\"\" value=\"\" id=\"submit_action\"/>\n";
        $footer .= $form_toolbar_html;

        $data['editor_footer'] = $footer;
    }

    private function _render_select($object)
    {
        $html = '';
        $element_name = '';

        $html .= "<select ";
        foreach ($object->_attributes as $key => $value)
        {
            $html .= "{$key}=\"{$value}\" ";
            if ($key == 'name')
            {
                $element_name = $value;
            }
        }
        $html .= ">\n";

        $selected_val = '';
        if (isset($this->_controller->formmanager->form->_defaultValues[$element_name]))
        {
            $selected_val = $this->_controller->formmanager->form->_defaultValues[$element_name];
        }
        if (isset($this->_controller->formmanager->form->_submitValues[$element_name]))
        {
            $selected_val = $this->_controller->formmanager->form->_submitValues[$element_name];
        }

        foreach ($object->_options as $item)
        {
            $selected = '';
            if (   $selected_val != ''
                && $selected_val == $item['attr']['value'])
            {
                $selected = 'selected="selected"';
            }

            $html .= "<option value=\"{$item['attr']['value']}\" {$selected}>{$item['text']}</option>\n";
        }

        $html .= "</select>\n";

        return $html;
    }

    private function _render_button($object)
    {
        $html = "    <input ";
        foreach ($object->_attributes as $key => $value)
        {
            $html .= "{$key}=\"{$value}\" ";
        }
        $html .= " />\n";

        return $html;
    }

    private function _render_static($object)
    {
        $html = $object->_text;

        return $html;
    }

    private function _render_row_label($row_name)
    {
        foreach ($this->_row_labels as $key => $label)
        {
            if (   strpos($row_name, $key) !== false
                && !isset($this->_rendered_row_labels[$key]))
            {
                $this->_rendered_row_labels[$key] = true;

                $html = "    <tr id=\"privilege_row_{$key}\" class=\"maa_permissions_rows_row\">\n";
                $html .= "      <td class=\"row_value assignee_name\"><span>{$label}</span></td>\n"; //<div class=\"draghandle\"></div>

                return $html;
            }
        }

        return '';
    }

    private function _render_row_actions($row_name)
    {
        foreach ($this->_row_labels as $key => $label)
        {
            if (strpos($row_name, $key) !== false)
            {
                $this->_rendered_row_actions[$key] = true;

                $actions = "<div class=\"actions\" id=\"privilege_row_actions_{$key}\">";
                $actions .= "</div>";

                midcom::get('head')->add_jquery_state_script("jQuery('#privilege_row_{$key}').privilege_actions('{$key}');");

                $html = "      <td class=\"row_value row_actions\">{$actions}</td>\n";

                return $html;
            }
        }

        return '';
    }

    private function _get_row_value_class($row_name)
    {
        foreach ($this->_row_labels as $key => $label)
        {
            if (strpos($row_name, $key) !== false)
            {
                $tmp_priv = str_replace($key . '_', '', $row_name);
                $tmp_priv_arr = explode('_', $tmp_priv);
                $priv_class = "{$tmp_priv_arr[1]}";
                if (count($tmp_priv_arr) > 2)
                {
                    $priv_class = "{$tmp_priv_arr[1]}_{$tmp_priv_arr[2]}";
                }
                return $priv_class;
            }
        }
        return '';
    }
}
?>