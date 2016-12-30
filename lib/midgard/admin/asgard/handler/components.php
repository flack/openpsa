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
        $this->add_stylesheet(MIDCOM_STATIC_URL . '/midgard.admin.asgard/components.css');
    }

    private function _load_component_data($name, midcom_core_manifest $manifest)
    {
        $component_array = array(
            'name' => $name,
            'title' => $this->_i18n->get_string($name, $name),
            'purecode' => $manifest->purecode,
            'icon' => midcom::get()->componentloader->get_component_icon($name),
            'description' => $manifest->description,
            'toolbar' => new midcom_helper_toolbar()
        );
        $component_array['toolbar']->add_item(
            array(
                MIDCOM_TOOLBAR_URL => "__mfa/asgard/components/configuration/{$name}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('component configuration'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_folder-properties.png',
            )
        );

        $component_array['toolbar']->add_item(
            array(
                MIDCOM_TOOLBAR_URL => "__ais/help/{$name}/",
                MIDCOM_TOOLBAR_LABEL => $this->_i18n->get_string('midcom.admin.help', 'midcom.admin.help'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_help-agent.png',
            )
        );

        return $component_array;
    }

    private function _list_components()
    {
        $this->_request_data['core_components'] = array();
        $this->_request_data['components'] = array();
        $this->_request_data['libraries'] = array();

        foreach (midcom::get()->componentloader->manifests as $name => $manifest) {
            $type = 'components';
            if ($manifest->purecode) {
                $type = 'libraries';
            } elseif (midcom::get()->componentloader->is_core_component($name)) {
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
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_list($handler_id, array $args, array &$data)
    {
        $data['view_title'] = $this->_l10n->get('components');

        $this->_list_components();

        // Set the breadcrumb data
        $this->add_breadcrumb('__mfa/asgard/', $this->_l10n->get($this->_component));
        $this->add_breadcrumb('__mfa/asgard/components/', $this->_l10n->get('components'));
        return new midgard_admin_asgard_response($this, '_show_list');
    }

    /**
     * Shows the loaded components
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_list($handler_id, array &$data)
    {
        $this->_show_lists('core_components');
        $this->_show_lists('components');
        $this->_show_lists('libraries');
    }

    private function _show_lists($type)
    {
        $this->_request_data['list_type'] = $type;
        midcom_show_style('midgard_admin_asgard_components_header');
        foreach ($this->_request_data[$type] as $component_data) {
            $this->_request_data['component_data'] = $component_data;
            midcom_show_style('midgard_admin_asgard_components_item');
        }
        midcom_show_style('midgard_admin_asgard_components_footer');
    }

    /**
     * Component display
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_component($handler_id, array $args, array &$data)
    {
        $data['component'] = $args[0];
        if (!midcom::get()->componentloader->is_installed($data['component'])) {
            throw new midcom_error_notfound("Component {$data['component']} is not installed.");
        }

        $data['component_data'] = $this->_load_component_data($data['component'], midcom::get()->componentloader->manifests[$data['component']]);

        $data['view_title'] = $data['component_data']['title'];

        $data['asgard_toolbar']->add_item(
            array(
                MIDCOM_TOOLBAR_URL => "__mfa/asgard/components/configuration/{$data['component']}",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('component configuration'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_folder-properties.png',
            )
        );

        // Set the breadcrumb data
        $this->add_breadcrumb('__mfa/asgard/', $this->_l10n->get($this->_component));
        $this->add_breadcrumb('__mfa/asgard/components/', $this->_l10n->get('components'));
        $this->add_breadcrumb("__mfa/asgard/components/{$data['component']}", $data['component_data']['title']);
        return new midgard_admin_asgard_response($this, '_show_component');
    }

    /**
     * Shows the loaded component
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_component($handler_id, array &$data)
    {
        midcom_show_style('midgard_admin_asgard_components_component');
    }
}
