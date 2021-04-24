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
    use midgard_admin_asgard_handler;

    private $components = [];

    private $libraries = [];

    public function _on_initialize()
    {
        $this->add_stylesheet(MIDCOM_STATIC_URL . '/midgard.admin.asgard/components.css');
    }

    private function _load_component_data(midcom_core_manifest $manifest) : array
    {
        $data = [
            'name' => $manifest->name,
            'title' => $manifest->get_name_translated(),
            'purecode' => $manifest->purecode,
            'icon' => midcom::get()->componentloader->get_component_icon($manifest->name),
            'description' => $manifest->description,
            'toolbar' => new midcom_helper_toolbar()
        ];

        $data['toolbar']->add_item([
            MIDCOM_TOOLBAR_URL => $this->router->generate('components_configuration', ['component' => $manifest->name]),
            MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('component configuration'),
            MIDCOM_TOOLBAR_GLYPHICON => 'wrench',
        ]);
        $data['toolbar']->add_help_item($manifest->name);

        return $data;
    }

    private function _list_components()
    {
        foreach (midcom::get()->componentloader->get_manifests() as $manifest) {
            $type = ($manifest->purecode) ? 'libraries' : 'components';
            $this->$type[$manifest->name] = $this->_load_component_data($manifest);
        }
    }

    /**
     * Component list view
     */
    public function _handler_list(array &$data)
    {
        $data['view_title'] = $this->_l10n->get('components');

        $this->_list_components();

        // Set the breadcrumb data
        $this->add_breadcrumb($this->router->generate('welcome'), $this->_l10n->get($this->_component));
        $this->add_breadcrumb($this->router->generate('components'), $this->_l10n->get('components'));
        return $this->get_response();
    }

    /**
     * Shows the loaded components
     */
    public function _show_list(string $handler_id, array &$data)
    {
        $this->_show_lists('components');
        $this->_show_lists('libraries');
    }

    private function _show_lists(string $type)
    {
        $this->_request_data['list_type'] = $type;
        midcom_show_style('midgard_admin_asgard_components_header');
        foreach ($this->$type as $component_data) {
            $this->_request_data['component_data'] = $component_data;
            midcom_show_style('midgard_admin_asgard_components_item');
        }
        midcom_show_style('midgard_admin_asgard_components_footer');
    }

    /**
     * Component display
     */
    public function _handler_component(string $component, array &$data)
    {
        $data['component'] = $component;
        if (!midcom::get()->componentloader->is_installed($component)) {
            throw new midcom_error_notfound("Component {$component} is not installed.");
        }

        $data['component_data'] = $this->_load_component_data(midcom::get()->componentloader->get_manifest($component));

        $data['view_title'] = $data['component_data']['title'];

        $data['asgard_toolbar']->add_item([
            MIDCOM_TOOLBAR_URL => $this->router->generate('components_configuration', ['component' => $component]),
            MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('component configuration'),
            MIDCOM_TOOLBAR_GLYPHICON => 'wrench',
        ]);

        // Set the breadcrumb data
        $this->add_breadcrumb($this->router->generate('welcome'), $this->_l10n->get($this->_component));
        $this->add_breadcrumb($this->router->generate('components'), $this->_l10n->get('components'));
        $this->add_breadcrumb('', $data['component_data']['title']);
        return $this->get_response('midgard_admin_asgard_components_component');
    }
}
