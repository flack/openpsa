<?php
/**
 * @package midgard.admin.asgard
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Component display
 *
 * @package midgard.admin.asgard
 */
class midgard_admin_asgard_handler_components extends midcom_baseclasses_components_handler
{
    public function _on_initialize()
    {
        // Ensure we get the correct styles
        $_MIDCOM->style->prepend_component_styledir('midgard.admin.asgard');
        $_MIDCOM->skip_page_style = true;

        $this->add_stylesheet(MIDCOM_STATIC_URL . '/midgard.admin.asgard/components.css');

        $_MIDCOM->load_library('midcom.helper.datamanager2');
    }

    private function _load_component_data($name, $manifest)
    {
        $component_array = array();
        $component_array['name'] = $name;
        $component_array['title'] = $_MIDCOM->i18n->get_string($name, $name);
        $component_array['purecode'] = $manifest->purecode;
        $component_array['icon'] = $_MIDCOM->componentloader->get_component_icon($name);

        if (isset($manifest->_raw_data['package.xml']['description']))
        {
            $component_array['description'] = $manifest->_raw_data['package.xml']['description'];
        }
        else
        {
            $component_array['description'] = '';
        }

        $component_array['version'] = $manifest->_raw_data['version'];

        $component_array['maintainers'] = array();
        if (isset($manifest->_raw_data['package.xml']['maintainers']))
        {
            $component_array['maintainers'] = $manifest->_raw_data['package.xml']['maintainers'];
        }

        $component_array['toolbar'] = new midcom_helper_toolbar();
        $component_array['toolbar']->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "__mfa/asgard/components/configuration/{$name}/",
                MIDCOM_TOOLBAR_LABEL => $_MIDCOM->i18n->get_string('component configuration', 'midcom'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_folder-properties.png',
            )
        );

        if (isset($_MIDCOM->componentloader->manifests['midcom.admin.babel']))
        {
            $component_array['toolbar']->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "__mfa/asgard_midcom.admin.babel/edit/{$name}/" . $_MIDCOM->i18n->get_current_language() . "/",
                    MIDCOM_TOOLBAR_LABEL => $_MIDCOM->i18n->get_string('midcom.admin.babel', 'midcom.admin.babel'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/locale.png',
                )
            );
        }

        if (isset($_MIDCOM->componentloader->manifests['midcom.admin.help']))
        {
            $component_array['toolbar']->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "__ais/help/{$name}/",
                    MIDCOM_TOOLBAR_LABEL => $_MIDCOM->i18n->get_string('midcom.admin.help', 'midcom.admin.help'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_help-agent.png',
                )
            );
        }

        return $component_array;
    }

    private function _list_components()
    {
        $this->_request_data['core_components'] = array();
        $this->_request_data['components'] = array();
        $this->_request_data['libraries'] = array();

        foreach($_MIDCOM->componentloader->manifests as $name => $manifest)
        {
            if (!array_key_exists('package.xml', $manifest->_raw_data))
            {
                // This component is not yet packaged, skip
                continue;
            }

            $type = 'components';
            if ($manifest->purecode)
            {
                $type = 'libraries';
            }
            elseif ($_MIDCOM->componentloader->is_core_component($name))
            {
                $type = 'core_components';
            }

            $component_array = $this->_load_component_data($name, $manifest);

            $this->_request_data[$type][$name] = $component_array;
        }
    }

    /**
     * Component list view
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    public function _handler_list($handler_id, $args, &$data)
    {
        $data['view_title'] = $_MIDCOM->i18n->get_string('components', 'midgard.admin.asgard');
        $_MIDCOM->set_pagetitle($data['view_title']);

        $this->_list_components();

        // Set the breadcrumb data
        $this->add_breadcrumb('__mfa/asgard/', $this->_l10n->get('midgard.admin.asgard'));
        $this->add_breadcrumb('__mfa/asgard/components/', $this->_l10n->get('components'));

        return true;
    }

    /**
     * Shows the loaded components
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    public function _show_list($handler_id, &$data)
    {
        midgard_admin_asgard_plugin::asgard_header();

        $data['list_type'] = 'core_components';
        midcom_show_style('midgard_admin_asgard_components_header');
        foreach ($data['core_components'] as $component => $component_data)
        {
            $data['component_data'] = $component_data;
            midcom_show_style('midgard_admin_asgard_components_item');
        }
        midcom_show_style('midgard_admin_asgard_components_footer');

        $data['list_type'] = 'components';
        midcom_show_style('midgard_admin_asgard_components_header');
        foreach ($data['components'] as $component => $component_data)
        {
            $data['component_data'] = $component_data;
            midcom_show_style('midgard_admin_asgard_components_item');
        }
        midcom_show_style('midgard_admin_asgard_components_footer');

        $data['list_type'] = 'libraries';
        midcom_show_style('midgard_admin_asgard_components_header');
        foreach ($data['libraries'] as $component => $component_data)
        {
            $data['component_data'] = $component_data;
            midcom_show_style('midgard_admin_asgard_components_item');
        }
        midcom_show_style('midgard_admin_asgard_components_footer');

        midgard_admin_asgard_plugin::asgard_footer();
    }

    /**
     * Component display
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    public function _handler_component($handler_id, $args, &$data)
    {
        $data['component'] = $args[0];
        if (!$_MIDCOM->componentloader->is_installed($data['component']))
        {
            throw new midcom_error_notfound("Component {$data['component']} is not installed.");
        }

        $data['component_data'] = $this->_load_component_data($data['component'], $_MIDCOM->componentloader->manifests[$data['component']]);
        $data['component_dependencies'] = $_MIDCOM->componentloader->get_component_dependencies($data['component']);

        $data['view_title'] = $data['component_data']['title'];
        $_MIDCOM->set_pagetitle($data['view_title']);

        $data['asgard_toolbar']->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "__mfa/asgard/components/configuration/{$data['component']}",
                MIDCOM_TOOLBAR_LABEL => $_MIDCOM->i18n->get_string('component configuration', 'midcom'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_folder-properties.png',
            )
        );

        if (isset($_MIDCOM->componentloader->manifests['midcom.admin.babel']))
        {
            $data['asgard_toolbar']->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "__mfa/asgard_midcom.admin.babel/edit/{$data['component']}/" . $_MIDCOM->i18n->get_current_language() . "/",
                    MIDCOM_TOOLBAR_LABEL => $_MIDCOM->i18n->get_string('midcom.admin.babel', 'midcom.admin.babel'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/locale.png',
                )
            );
        }

        // Set the breadcrumb data
        $this->add_breadcrumb('__mfa/asgard/', $this->_l10n->get('midgard.admin.asgard'));
        $this->add_breadcrumb('__mfa/asgard/components/', $this->_l10n->get('components'));
        $this->add_breadcrumb("__mfa/asgard/components/{$data['component']}", $data['component_data']['title']);

        return true;
    }

    /**
     * Shows the loaded component
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    public function _show_component($handler_id, &$data)
    {
        midgard_admin_asgard_plugin::asgard_header();

        midcom_show_style('midgard_admin_asgard_components_component');

        midgard_admin_asgard_plugin::asgard_footer();
    }
}
?>