<?php
/**
 * @package fi.protie.navigation
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Versatile class for drawing dynamically navigation elements according to
 * user preferences.
 *
 * <code>
 * // Initializes the class
 * $navigation = new fi_protie_navigation();
 *
 * // Display only nodes (folders)
 * $navigation->list_leaves = false;
 *
 * // Expand the whole site tree instead of the active path
 * $navigation->follow_all = true;
 *
 * // Skip 1 level from the beginning of the active path
 * $navigation->skip_levels = 1;
 *
 * // Finally draw the navigation
 * $navigation->draw();
 * </code>
 *
 * See the attributes of this class for additional customizing options.
 *
 * @package fi.protie.navigation
 */
class fi_protie_navigation
{
    /**
     * MidCOM helper class for navigation subsystem. Uses class 'midcom.helper.nav'
     */
    private midcom_helper_nav $_nap;

    /**
     * Stores the navigation access point history or in other words path to the current point.
     */
    private array $node_path = [];

    /**
     * ID for the folder to get the navigation
     */
    public ?int $root_id = null;

    /**
     * Number of the parsed level
     */
    private int $_level = 1;

    /**
     * The amount of lowest level elements to be skipped.
     */
    public int $skip_levels = 0;

    /**
     * Switch to determine if navigation should display leaves or pages.
     */
    public bool $list_leaves = true;

    /**
     * List only the leaf elements or pages
     */
    public bool $list_nodes = true;

    /**
     * Switch to determine if navigation should follow node path (on true) or stop on the
     * spot.
     */
    public bool $follow_selected = true;

    /**
     * Switch to determine if navigation should follow all the nodes or only the current
     */
    public bool $follow_all = false;

    /**
     * Switch to determine if navigation should show only the information of the currently selected node.
     */
    public bool $show_only_current = false;

    /**
     * Restrict the amount of levels listed.
     */
    public int $list_levels = 0;

    /**
     * ID of the root level list object
     */
    public ?string $root_object_id = null;

    /**
     * CSS class for styling the lists
     */
    public string $css_list_style = 'fi_protie_navigation';

    /**
     * Add component name to list item ul class name
     */
    public bool $component_name_to_class = false;

    /**
     * Check if item has children and if so, add node/leaf class to list item
     */
    public bool $has_children_to_class = false;

    /**
     * Should the object's status be added to list item ul class names
     * Since this forces us to load the entire object, set it to false if you don't need it
     */
    public bool $object_status_to_class = false;

    /**
     * CSS class for nodes
     */
    public string $css_node = 'node';

    /**
     * CSS class for leaves
     */
    public string $css_leaf = 'leaf';

    /**
     * CSS class for the elements in node path. All the elements in node path will have this class.
     */
    public string $css_selected = 'selected';

    /**
     * CSS class for the current, active node or leaf. There can be only one active element.
     */
    public string $css_active = 'active';

    /**
     * CSS class for links
     */
    public string $css_link = 'link';

    /**
     * Here we initialize the classes and variables needed through the class.
     */
    public function __construct(int $id = null)
    {
        $this->_nap = new midcom_helper_nav();
        $this->get_node_path();

        if ($id !== null) {
            $this->root_id = $id;
        }
    }

    /**
     * Traverses through the node path to fetch the location of the current navigation access point.
     */
    private function get_node_path()
    {
        // Get nodes
        $this->node_path = $this->_nap->get_node_path();

        // If NAP offers a leaf it should be stored in the node path
        if ($leaf = $this->_nap->get_current_leaf()) {
            $this->node_path[] = $leaf;
        }
    }

    /**
     * Traverse the child nodes starting from the requested node id
     */
    private function _list_child_nodes(int $id)
    {
        $children = $this->_nap->get_nodes($id);

        // Stop traversing the path if there are no children
        if (empty($children)) {
            return;
        }

        // Add ID property to the first unordered list ever called
        $element_id = '';
        if ($this->root_object_id) {
            $element_id = " id=\"{$this->root_object_id}\"";
            $this->root_object_id = null;
        }

        echo "<ul class=\"{$this->css_list_style} node-{$id}\"{$element_id}>";

        // Draw each child element
        foreach ($children as $child) {
            $this->_display_element($child);
        }
        echo "</ul>";
    }

    /**
     * Traverse the child elements starting from the requested node id
     */
    private function _list_child_elements(int $id)
    {
        // If only nodes are to be listed use the appropriate NAP call
        if (!$this->list_leaves) {
            $this->_list_child_nodes($id);
            return;
        }

        $children = $this->_nap->list_child_elements($id);

        // Stop traversing the path if there are no children
        if (empty($children)) {
            return;
        }

        // Add ID property to the first unordered list ever called
        $element_id = '';
        if ($this->root_object_id) {
            $element_id = " id=\"{$this->root_object_id}\"";
            $this->root_object_id = null;
        }

        echo "<ul class=\"{$this->css_list_style} node-{$id}\"{$element_id}>";

        // Draw each child element
        foreach ($children as $child) {
            if ($child[MIDCOM_NAV_TYPE] === 'node' && $this->list_nodes === false) {
                // If the listing of nodes is set to false, skip this item and proceed to the next
                continue;
            }
            $this->_display_element($child);
        }

        echo "</ul>";
    }

    private function _get_css_classes(array $item) : string
    {
        $classes = [];

        if ($item[MIDCOM_NAV_TYPE] === 'node') {
            if (   $item[MIDCOM_NAV_ID] === $this->_nap->get_current_node()
                && (   !$this->_nap->get_current_leaf()
                    || !$this->_nap->get_leaf($this->_nap->get_current_leaf()))) {
                $classes[] = $this->css_active;
            }

            if (in_array($item[MIDCOM_NAV_ID], $this->node_path, true)) {
                $classes[] = $this->css_selected;
            }

            if ($this->component_name_to_class) {
                $classes[] = str_replace('.', '_', $item[MIDCOM_NAV_COMPONENT]);
            }
        } elseif ($item[MIDCOM_NAV_ID] === $this->_nap->get_current_leaf()) {
            // Place the corresponding css class for the currently active leaf)
            $classes[] = $this->css_active;
            $classes[] = $this->css_selected;
        }

        if ($this->has_children_to_class) {
            if (!$this->list_leaves) {
                $children = $this->_nap->get_nodes($item[MIDCOM_NAV_ID]);
            } elseif ($item[MIDCOM_NAV_TYPE] == 'node') {
                $children = $this->_nap->list_child_elements($item[MIDCOM_NAV_ID]);
            } else {
                $children = false;
            }
            $classes[] = $children ? $this->css_node : $this->css_leaf;
        }

        // Add information about the object's status
        if (   $this->object_status_to_class
            && isset($item[MIDCOM_NAV_OBJECT])
            && $css_status_class = midcom::get()->metadata->get_object_classes($item[MIDCOM_NAV_OBJECT])) {
            $classes[] = $css_status_class;
        }

        return implode(' ', $classes);
    }

    private function _display_element(array $item)
    {
        $css_classes = $this->_get_css_classes($item);
        // Finalize the class naming
        $class = ($css_classes !== '') ? ' class="' . $css_classes . '"' : '';
        $link_class = $this->css_link ? ' class="' . $this->css_link . '"' : '';

        echo "<li{$class}>";
        echo "<a href=\"{$item[MIDCOM_NAV_ABSOLUTEURL]}\"{$link_class}>" . htmlspecialchars($item[MIDCOM_NAV_NAME]) . "</a>";
        // If either of the follow nodes switches is on, follow all the nodes

        if (   $item[MIDCOM_NAV_TYPE] === 'node'
            && !$this->show_only_current
            && (   $this->list_levels === 0
                || $this->_level < $this->list_levels)) {
            if (   $this->follow_all
                || (   $this->follow_selected
                    && in_array($item[MIDCOM_NAV_ID], $this->node_path, true))) {
                $this->_level++;
                $this->_list_child_elements($item[MIDCOM_NAV_ID]);
                $this->_level--;
            }
        }

        echo "</li>";
    }

    /**
     * Draw the navigation.
     */
    public function draw()
    {
        if (!$this->root_id) {
            $this->root_id = $this->_nap->get_root_node();
        }

        if ($this->skip_levels !== 0) {
            if (!array_key_exists($this->skip_levels, $this->node_path)) {
                return;
            }

            $this->root_id = $this->node_path[$this->skip_levels];
        }

        if ($this->show_only_current) {
            $this->root_id = $this->_nap->get_current_node();
        }

        $this->_list_child_elements($this->root_id);
    }

    /**
     * Set the root element id
     *
     * @param string $id root ul id
     */
    public function set_root_element_id(string $id)
    {
        $this->root_object_id = $id;
    }
}
