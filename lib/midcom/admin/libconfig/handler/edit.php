<?php
/**
 * @package midcom.admin.libconfig
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: edit.php 26508 2010-07-06 13:35:23Z rambo $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Listing libraries handler class
 *
 * @package midcom.admin.libconfig
 */
class midcom_admin_libconfig_handler_edit extends midcom_baseclasses_components_handler
{
    function _on_initialize()
    {
        $this->_l10n = $_MIDCOM->i18n->get_l10n('midcom.admin.libconfig');
        $this->_request_data['l10n'] = $this->_l10n;

        $_MIDCOM->add_link_head
        (
            array
            (
                'rel' => 'stylesheet',
                'type' => 'text/css',
                'href' => MIDCOM_STATIC_URL . '/midcom.admin.libconfig/style.css',
            )
        );

        midgard_admin_asgard_plugin::prepare_plugin($this->_l10n->get('midcom.admin.libconfig'),$this->_request_data);
    }

    private function _update_breadcrumb($name)
    {
        // Populate breadcrumb
        $label = $_MIDCOM->i18n->get_string($name,$name);
        $tmp = Array();
        $tmp[] = Array
        (
            MIDCOM_NAV_URL => "__mfa/asgard_midcom.admin.libconfig/",
            MIDCOM_NAV_NAME => $this->_request_data['view_title'],
        );
        $tmp[] = Array
        (
            MIDCOM_NAV_URL => "__mfa/asgard_midcom.admin.libconfig/view/{$name}",
            MIDCOM_NAV_NAME => $label,
        );
        $tmp[] = Array
        (
            MIDCOM_NAV_URL => "__mfa/asgard_midcom.admin.libconfig/edit/{$name}",
            MIDCOM_NAV_NAME => $_MIDCOM->i18n->get_string('edit','midcom'),
        );

        $_MIDCOM->set_custom_context_data('midcom.helper.nav.breadcrumb', $tmp);
    }

    private function _prepare_toolbar(&$data)
    {
        midgard_admin_asgard_plugin::get_common_toolbar($data);
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_edit($handler_id, $args, &$data)
    {
        if (!array_key_exists($args[0],$_MIDCOM->componentloader->manifests))
        {
            return false;
        }

        $componentpath = MIDCOM_ROOT . $_MIDCOM->componentloader->path_to_snippetpath($args[0]);

        // Load and parse the global config
        $cfg = midcom_baseclasses_components_configuration::read_array_from_file("{$componentpath}/config/config.inc");
        if (! $cfg)
        {
            // hmmm... that should never happen
            $cfg = array();
        }

        $this->_libconfig = new midcom_helper_configuration($cfg);

        // Go for the sitewide default
        $cfg = midcom_baseclasses_components_configuration::read_array_from_file("/etc/midgard/midcom/{$args[0]}/config.inc");
        if ($cfg !== false)
        {
            $this->_libconfig->store($cfg, false);
        }

        // Finally, check the sitegroup config
        $cfg = midcom_baseclasses_components_configuration::read_array_from_snippet("{$GLOBALS['midcom_config']['midcom_sgconfig_basedir']}/{$args[0]}/config");
        if ($cfg !== false)
        {
            $this->_libconfig->store($cfg, false);
        }

        //schemadb
        if (isset($this->_libconfig->_global['schemadb_config']))
        {
            // We rely on config schema. Hope that schema covers all fields
            $schemadb = midcom_helper_datamanager2_schema::load_database($this->_libconfig->_global['schemadb_config']);
        }
        else
        {
            // Create dummy schema. Naughty component would not provide config schema.
            $schemadb = midcom_helper_datamanager2_schema::load_database("file:/midcom/admin/libconfig/config/schemadb_template.inc");
            $schemadb['default']->l10n_schema = $args[0];
        }

        foreach ($this->_libconfig->_global as $key => $value)
        {
            // try to sniff what fields are missing in schema
            if (!array_key_exists($key, $schemadb['default']->fields))
            {
                $schemadb['default']->append_field
                (
                    $key,
                    $this->_detect_schema($key,$value)
                );
            }

            if (   !isset($this->_libconfig->_local[$key])
                || !$this->_libconfig->_local[$key])
            {
                $schemadb['default']->fields[$key]['static_prepend'] = "<div class='global'><span>Global value</span>";
                $schemadb['default']->fields[$key]['static_append'] = "</div>";
            }
        }

        //prepare values
        foreach ($this->_libconfig->_merged as $key => $value)
        {
            if (is_array($value))
            {
                $defaults[$key] = $this->_draw_array($value);
            }
            else
            {
                $defaults[$key] = $value;
            }
        }

        $this->_controller = midcom_helper_datamanager2_controller::create('nullstorage');
        $this->_controller->schemadb =& $schemadb;
        $this->_controller->defaults = $defaults;
        if (! $this->_controller->initialize())
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Failed to initialize a DM2 controller instance for configuration.");
        // This will exit.
        }

        switch ($this->_controller->process_form())
        {
            case 'save':
                $sg_snippetdir = new midcom_db_snippetdir();
                $sg_snippetdir->get_by_path($GLOBALS['midcom_config']['midcom_sgconfig_basedir']);
                if ($sg_snippetdir->id == false )
                {
                    $sd = new midcom_db_snippetdir();
                    $sd->up = 0;
                    $sd->name = $GLOBALS['midcom_config']['midcom_sgconfig_basedir'];
                    if (!$sd->create())
                    {
                        $_MIDCOM->generate_error(MIDCOM_ERRCRIT,"Failed to create {$GLOBALS['midcom_config']['midcom_sgconfig_basedir']}".midcom_connection::get_error_string());
                    }
                    $sg_snippetdir = new midcom_db_snippetdir($sd->guid);
                    unset($sd);
                }

                $lib_snippetdir = new midcom_db_snippetdir();
                $lib_snippetdir->get_by_path($GLOBALS['midcom_config']['midcom_sgconfig_basedir']."/".$args[0]);
                if ($lib_snippetdir->id == false )
                {
                    $sd = new midcom_db_snippetdir();
                    $sd->up = $sg_snippetdir->id;
                    $sd->name = $args[0];
                    if (!$sd->create())
                    {
                        $_MIDCOM->generate_error(MIDCOM_ERRCRIT,"Failed to create {$args[0]}".midcom_connection::get_error_string());
                    }
                    $lib_snippetdir = new midcom_db_snippetdir($sd->guid);
                    unset($sd);
                }

                $snippet = new midcom_db_snippet();
                $snippet->get_by_path($GLOBALS['midcom_config']['midcom_sgconfig_basedir']."/".$args[0]."/config");
                if ($snippet->id == false )
                {
                    $sn = new midcom_db_snippet();
                    $sn->up = $lib_snippetdir->id;
                    $sn->name = "config";
                    if (!$sn->create())
                    {
                        $_MIDCOM->generate_error(MIDCOM_ERRCRIT,"Failed to create config snippet".midcom_connection::get_error_string());
                    }
                    $snippet = new midcom_db_snippet($sn->id);
                }

                $snippet->code = $this->_get_config($this->_controller);

                if (   $snippet->code == ''
                    || !$snippet->code)
                {
                    $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                        "code-init content generation failed.");
                    // This will exit.
                }

                $rst = $snippet->update();

                if ($rst)
                {
                    mgd_cache_invalidate();
                    $_MIDCOM->uimessages->add($_MIDCOM->i18n->get_string('host configuration', 'midcom.admin.settings'),
                    $_MIDCOM->i18n->get_string('settings saved successfully', 'midcom.admin.settings')
                    . $this->_codeinit->id,
                                                'ok');
                }
                else
                {
                    $_MIDCOM->uimessages->add($_MIDCOM->i18n->get_string('host configuration', 'midcom.admin.settings'),
                      sprintf($_MIDCOM->i18n->get_string('failed to save settings, reason %s', 'midc')),
                                                'error');
                }
                // *** FALL-THROUGH ***

            case 'cancel':
                $_MIDCOM->relocate('__mfa/asgard_midcom.admin.libconfig/edit/'.$args[0]);
                // This will exit.
        }


        $data['controller'] =& $this->_controller;

        $this->_update_breadcrumb($args[0]);
        $this->_prepare_toolbar($data);
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

        midcom_show_style('midcom-admin-libs-edit');
        midgard_admin_asgard_plugin::asgard_footer();

    }

    function _get_config()
    {
        $post = $this->_controller->formmanager->form->_submitValues;
        foreach ($this->_libconfig->_global as $key => $val)
        {
            $newval = $post[$key];

            switch(gettype($this->_libconfig->_global[$key]))
            {
                case "boolean":
                    $data .= ($newval)?"'{$key}' => true,\n":"'{$key}' => false,\n";
                    break;
                case "array":
                    break;
                default:
                    if ($newval)
                    {
                        $data .= "'{$key}' => '{$newval}',\n";
                    }
            }
        }

        return $data;
    }

    function _detect_schema($key,$value)
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
                break;
            default:
                if (strpos($value, "\n") !== false)
                {
                    $result['widget'] = 'textarea';
                }
        }

        return $result;
    }

    function _draw_array($array)
    {
        foreach ($array as $key => $val)
        {
            switch(gettype($val))
            {
                case "boolean":
                    $data .= ($val)?"    '{$key}' => true,\n":"'{$key}' => false,\n";
                    break;
                case "array":
                    $data .= $this->_draw_array($val);
                    break;

                default:
                    $data = '';
                    if (is_numeric($val))
                    {
                        $data .= "    '{$key}' => {$val},\n";
                    }
                    else
                    {
                        $data .= "    '{$key}' => '{$val}',\n";
                    }
            }

        }
        $result = "array(\n{$data}),\n";
        return $result;
    }

}
?>