<?php
/**
 * @package midcom.admin.libconfig
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Listing libraries handler class
 *
 * @package midcom.admin.libconfig
 */
class midcom_admin_libconfig_handler_view extends midcom_baseclasses_components_handler
{
    public function _on_initialize()
    {
        $this->add_stylesheet(MIDCOM_STATIC_URL . '/midcom.admin.libconfig/style.css');

        midgard_admin_asgard_plugin::prepare_plugin($this->_l10n->get('midcom.admin.libconfig'), $this->_request_data);
    }

    /**
     * Populate breadcrumb
     */
    private function _update_breadcrumb($name)
    {
        $label = midcom::get('i18n')->get_string($name, $name);

        $this->add_breadcrumb("__mfa/asgard_midcom.admin.libconfig/", $this->_request_data['view_title']);
        $this->add_breadcrumb("__mfa/asgard_midcom.admin.libconfig/view/{$name}", $label);
    }

    private function _prepare_toolbar(&$data)
    {
        $data['asgard_toolbar']->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "__mfa/asgard_midcom.admin.libconfig/edit/{$data['name']}",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('edit'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/edit.png',
            )
        );
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     * @return boolean Indicating success.
     */
    public function _handler_view($handler_id, array $args, array &$data)
    {
        $data['name'] = $args[0];
        if (!midcom::get('componentloader')->is_installed($data['name']))
        {
            throw new midcom_error_notfound("Component {$data['name']} is not installed.");
        }

        $componentpath = midcom::get('componentloader')->path_to_snippetpath($data['name']);

        // Load and parse the global config
        $cfg = midcom_baseclasses_components_configuration::read_array_from_file("{$componentpath}/config/config.inc");
        if (! $cfg)
        {
            // hmmm... that should never happen
            $cfg = array();
        }

        $config = new midcom_helper_configuration($cfg);

        // Go for the sitewide default
        $cfg = midcom_baseclasses_components_configuration::read_array_from_snippet("conf:/{$data['name']}/config.inc");
        if ($cfg !== false)
        {
            $config->store($cfg, false);
        }

        // Finally, check the sitegroup config
        $cfg = midcom_baseclasses_components_configuration::read_array_from_snippet(midcom::get('config')->get('midcom_sgconfig_basedir') . "/{$data['name']}/config");
        if ($cfg !== false)
        {
            $config->store($cfg, false);
        }

        $data['config'] =& $config;

        $this->_update_breadcrumb($data['name']);
        $this->_prepare_toolbar($data);
        midcom::get('head')->set_pagetitle($data['view_title']);
    }

    /**
     * Show list of the style elements for the currently edited topic component
     *
     * @param string $handler_id Name of the used handler
     * @param mixed &$data Data passed to the show method
     */
    public function _show_view($handler_id, array &$data)
    {
        midgard_admin_asgard_plugin::asgard_header();

        midcom_show_style('midcom-admin-libs-view-header');
        $data['even'] = false;
        foreach ($data['config']->_global as $key => $value)
        {
            $data['key'] = midcom::get('i18n')->get_string($key, $data['name']);
            $data['global'] = $this->_detect($data['config']->_global[$key]);

            if (isset($data['config']->_local[$key]))
            {
                $data['local'] = $this->_detect($data['config']->_local[$key]);
            }
            else
            {
                $data['local'] = $this->_detect(null);
            }

            midcom_show_style('midcom-admin-libs-view-item');
            $data['even'] = !$data['even'];
        }
        midcom_show_style('midcom-admin-libs-view-footer');
        midgard_admin_asgard_plugin::asgard_footer();
    }

    private function _detect($value)
    {
        $type = gettype($value);

        switch ($type)
        {
            case "boolean":

                $src = MIDCOM_STATIC_URL . '/stock-icons/16x16/cancel.png';
                $result = "<img src='{$src}'/>";

                if ($value === true)
                {
                    $src = MIDCOM_STATIC_URL . '/stock-icons/16x16/ok.png';
                    $result = "<img src='{$src}'/>";
                }

                break;
            case "array":
                $content = '';
                foreach ($value as $key => $val)
                {
                    $content .= "<li>{$key} => ".$this->_detect($val).",</li>";
                }
                $result = "<ul>array<br />(<br />{$content}),</ul>";


                break;
            case "object":
                $result = "<strong>Object</strong>";
                break;
            case "NULL":
                $src = MIDCOM_STATIC_URL . '/stock-icons/16x16/cancel.png';
                $result = "<img src='{$src}'/>";
                $result = "<strong>N/A</strong>";
                break;
            default:
                $result = $value;
        }

        return $result;
    }
}
?>