<?php
/**
 * @package midgard.admin.asgard
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Style helper methods
 *
 * @package midgard.admin.asgard
 */
class midgard_admin_asgard_stylehelper
{
    /**
     * The current request data
     *
     * @var array
     */
    private $_data;

    public function __construct(array &$data)
    {
        $this->_data =& $data;
        midcom::get()->head->enable_jquery_ui(['accordion']);
    }

    public function render_help()
    {
        $help_element = null;
        if (empty($this->_data['object']->id)) {
            return;
        }

        if (   midcom::get()->dbfactory->is_a($this->_data['object'], 'midgard_style')
            && (   $this->_data['handler_id'] !== 'object_create'
                || $this->_data['current_type'] == 'midgard_element')) {
            $help_element = $this->_get_help_style_elementnames($this->_data['object']);
        } elseif (   midcom::get()->dbfactory->is_a($this->_data['object'], 'midgard_element')
                  && $this->_data['handler_id'] !== 'object_create') {
            $help_element = $this->_get_help_element();
        }

        if ($help_element) {
            midcom_show_style('midgard_admin_asgard_stylehelper_' . $help_element);
        }
    }

    private function _get_help_element()
    {
        if (   empty($this->_data['object']->name)
            || empty($this->_data['object']->style)) {
            // We cannot help with empty elements
            return;
        }

        if ($this->_data['object']->name == 'ROOT') {
            $element_path = midcom::get()->componentloader->path_to_snippetpath('midgard.admin.asgard') . '/documentation/ROOT.php';
            $this->_data['help_style_element'] = [
                'component' => 'midcom',
                'default'   => file_get_contents($element_path),
            ];
            return 'element';
        }

        // Find the element we're looking for
        $style_elements = $this->_get_style_elements_and_nodes($this->_data['object']->style);
        foreach ($style_elements['elements'] as $component => $elements) {
            if (!empty($elements[$this->_data['object']->name])) {
                $element_path = $elements[$this->_data['object']->name];
                $this->_data['help_style_element'] = [
                    'component' => $component,
                    'default'   => file_get_contents($element_path),
                ];
                return 'element';
            }
        }
    }

    /**
     * Suggest element names to create under a style
     */
    private function _get_help_style_elementnames(midcom_db_style $style) : string
    {
        $this->_data['help_style_elementnames'] = $this->_get_style_elements_and_nodes($style->id);
        return 'elementnames';
    }

    private function _get_style_elements_and_nodes(int $style_id) : array
    {
        $results = [
            'elements' => [
                'midcom' => [
                    'style-init' => '',
                    'style-finish' => '',
                 ]
            ],
            'nodes' => [],
        ];

        if (!$style_id) {
            return $results;
        }
        $style_path = midcom_db_style::path_from_id($style_id);
        $style_nodes = $this->_get_nodes_using_style($style_path);

        foreach ($style_nodes as $node) {
            if (!isset($results['nodes'][$node->component])) {
                $results['nodes'][$node->component] = [];
                // Get the list of style elements for the component
                $results['elements'][$node->component] = $this->_get_component_default_elements($node->component);
            }

            $results['nodes'][$node->component][] = $node;
        }

        return $results;
    }

    /**
     * Get list of topics using a particular style
     *
     * @param string $style Style path
     * @return midcom_db_topic[] List of folders
     */
    private function _get_nodes_using_style($style) : array
    {
        $style_nodes = [];
        // Get topics directly using the style
        $qb = midcom_db_topic::new_query_builder();
        $qb->add_constraint('style', '=', $style);

        foreach ($qb->execute() as $node) {
            $style_nodes[] = $node;

            if ($node->styleInherit) {
                $child_nodes = $this->_get_nodes_inheriting_style($node);
                $style_nodes = array_merge($style_nodes, $child_nodes);
            }
        }

        return $style_nodes;
    }

    private function _get_nodes_inheriting_style($node) : array
    {
        $nodes = [];
        $qb = midcom_db_topic::new_query_builder();
        $qb->add_constraint('up', '=', $node->id);
        $qb->add_constraint('style', '=', '');

        foreach ($qb->execute() as $child_node) {
            $nodes[] = $child_node;
            $subnodes = $this->_get_nodes_inheriting_style($child_node);
            $nodes = array_merge($nodes, $subnodes);
        }

        return $nodes;
    }

    /**
     * List the default template elements shipped with a component
     *
     * @param string $component Component to look elements for
     * @return array List of elements found indexed by the element name
     */
    private function _get_component_default_elements(string $component) : array
    {
        $elements = [];

        // Path to the file system
        $path = midcom::get()->componentloader->path_to_snippetpath($component) . '/style';

        if (!is_dir($path)) {
            debug_add("Directory {$path} not found.");
            return $elements;
        }

        foreach (glob($path . '/*.php') as $filepath) {
            $file = basename($filepath);
            $elements[str_replace('.php', '', $file)] = $filepath;
        }

        return $elements;
    }
}
