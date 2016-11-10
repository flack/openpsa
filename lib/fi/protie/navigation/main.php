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
     *
     * @var midcom_helper_nav
     */
    private $_nap = null;

    /**
     * Stores the navigation access point history or in other words path to the current point.
     *
     * @var Array
     */
    private $node_path = array();

    /**
     * ID for the folder to get the navigation
     *
     * @var integer
     */
    public $root_id = null;

    /**
     * Number of the parsed level
     *
     * @var integer
     */
    private $_level = 1;

    /**
     * The amount of lowest level elements to be skipped.
     *
     * @var integer
     */
    public $skip_levels = 0;

    /**
     * Switch to determine if navigation should display leaves or pages.
     *
     * @var boolean
     */
    public $list_leaves = true;

    /**
     * List only the leaf elements or pages
     *
     * @var boolean
     */
    public $list_nodes = true;

    /**
     * Switch to determine if navigation should follow node path (on true) or stop on the
     * spot.
     *
     * @var boolean
     */
    public $follow_selected = true;

    /**
     * Switch to determine if navigation should follow all the nodes or only the current
     *
     * @var boolean
     */
    public $follow_all = false;

    /**
     * Switch to determine if navigation should show only the information of the currently selected node.
     *
     * @var boolean
     */
    public $show_only_current = false;

    /**
     * Should the CSS class be in the link as well
     *
     * @var boolean
     */
    public $class_to_link = false;

    /**
     * Restrict the amount of levels listed.
     *
     * @var integer
     */
    public $list_levels = 0;

    /**
     * ID of the root level list object
     *
     * @var integer
     */
    public $root_object_id = null;

    /**
     * CSS class for styling the lists
     *
     * @var string
     */
    public $css_list_style = 'fi_protie_navigation';

    /**
     * Add URL name to list item class name
     *
     * @var boolean
     */
    public $url_name_to_class = false;

    /**
     * Add component name to list item ul class name
     *
     * @var boolean
     */
    public $component_name_to_class = false;

    /**
     * Add first and last-class names to list item ul class name
     *
     * @var boolean
     */
    public $first_and_last_to_class = false;

    /**
     * CSS class for first
     *
     * @var string
     */
    public $css_first = 'first';

    /**
     * CSS class for last
     *
     * @var string
     */
    public $css_last = 'last';

    /**
     * CSS class for first and last together
     *
     * @var string
     */
    public $css_first_last = 'first_last';

    /**
     * Check if item has children and if so, add children-class to list item ul class name
     *
     * @var boolean
     */
    public $has_children_to_class = false;

    /**
     * Should the object's status be added to list item ul class names
     * Since this forces us to load the entire object, set it to false if you don't need it
     *
     * @var boolean
     */
    public $object_status_to_class = false;

    /**
     * CSS class for has children
     *
     * @var string
     */
    public $css_has_children = 'children';

    /**
     * CSS class for nodes
     *
     * @var string
     */
    public $css_node = 'node';

    /**
     * CSS class for leaves
     *
     * @var string
     */
    public $css_leaf = 'leaf';

    /**
     * CSS class for the elements in node path. All the elements in node path will have this class.
     *
     * @var string
     */
    public $css_selected = 'selected';

    /**
     * CSS class for the current, active node or leaf. There can be only one active element.
     *
     * @var string
     */
    public $css_active = 'active';

    /**
     * parameter listening enabled
     *
     * @var boolean
     */
    private $_listen_params = false;

    /**
     * Registered get -parameters for listening
     *
     * @var array
     */
    private $_get_params = array();

    /**
     * Cache for parameters to be listened
     *
     * @var string
     */
    private $_params_cache = false;

    /**
     * Here we initialize the classes and variables needed through the class.
     */
    public function __construct($id = null)
    {
        $this->_nap = new midcom_helper_nav();
        $this->get_node_path();

        if (!is_null($id)) {
            $this->root_id = $id;
        }
    }

    function listen_parameter($name, $value = false)
    {
        if (empty($name)) {
            return;
        }

        if (   isset($this->_get_params[$name])
            && $this->_get_params[$name] == $value) {
            return;
        }
        $this->_get_params[$name] = $value;

        $this->_listen_params = true;
    }

    private function _get_parameter_string()
    {
        if (false !== $this->_params_cache) {
            return $this->_params_cache;
        }

        $this->_params_cache = '';
        $registered_params = array_intersect_key($this->_get_params, $_GET);
        if (empty($registered_params)) {
            return $this->_params_cache;
        }

        $params = array();
        foreach ($registered_params as $key => $value) {
            if ($value) {
                if ($_GET[$key] == $value) {
                    $params[$key] = $value;
                }
            } elseif (!$_GET[$key]) {
                $params[$key] = '';
            }
        }

        $this->_params_cache = '?' . http_build_query($params);

        return $this->_params_cache;
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
    private function _list_child_nodes($id)
    {
        $children = $this->_nap->list_nodes($id);

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

        $item_count = count($children);

        // Draw each child element
        foreach ($children as $i => $child) {
            $item = $this->_nap->get_node($child);

            $classes = $this->_get_css_classes($child, $item, $i, $item_count);

            $this->_display_element($item, $classes);
        }
        echo "</ul>";
    }

    /**
     * Traverse the child elements starting from the requested node id
     */
    private function _list_child_elements($id)
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

        $item_count = count($children);

        // Draw each child element
        foreach ($children as $i => $child) {
            if ($child[MIDCOM_NAV_TYPE] === 'node') {
                // If the listing of nodes is set to false, skip this item and proceed to the next
                if ($this->list_nodes === false) {
                    continue;
                }
                $item = $this->_nap->get_node($child[MIDCOM_NAV_ID]);
            } else {
                $item = $this->_nap->get_leaf($child[MIDCOM_NAV_ID]);
            }
            $classes = $this->_get_css_classes($child, $item, $i, $item_count);

            $this->_display_element($item, $classes);
        }

        echo "</ul>";
    }

    private function _get_css_classes($child, $item, $item_counter, $item_count)
    {
        $classes = array();

        if ($child[MIDCOM_NAV_TYPE] === 'node') {
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
        } else {
            // Place the corresponding css class for the currently active leaf)
            if ($item[MIDCOM_NAV_ID] === $this->_nap->get_current_leaf()) {
                $classes[] = $this->css_active;
                $classes[] = $this->css_selected;
            }
        }

        // Check if the URL name is supposed to be drawn
        if ($this->url_name_to_class) {
            $classes[] = str_replace('/', '', $item[MIDCOM_NAV_URL]);
        }

        if ($this->first_and_last_to_class) {
            if ($item_count == 1) {
                $classes[] = $this->css_first_last;
            } elseif ($item_counter == 1) {
                $classes[] = $this->css_first;
            } elseif ($item_counter == $item_count) {
                $classes[] = $this->css_last;
            }
        }

        if ($this->has_children_to_class) {
            if (!$this->list_leaves) {
                $children = $this->_nap->list_nodes($child[MIDCOM_NAV_ID]);
            } else {
                $children = $this->_nap->list_child_elements($child[MIDCOM_NAV_ID]);
            }
            if (!empty($children)) {
                $classes[] = $this->css_has_children;
            }
        }

        // Add information about the object's status
        if (   $this->object_status_to_class
            && isset($item[MIDCOM_NAV_OBJECT])
            && $css_status_class = midcom::get()->metadata->get_object_classes($item[MIDCOM_NAV_OBJECT])) {
            $classes[] = $css_status_class;
        }

        return implode(' ', $classes);
    }

    private function _display_element($item, $css_classes)
    {
        // Finalize the class naming
        $class = ($css_classes !== '') ? ' class="' . $css_classes . '"' : '';
        $link_class = ($this->class_to_link) ? $class : '';

        $get_params = $this->_get_parameter_string();

        echo "<li{$class}>";
        echo "<a href=\"{$item[MIDCOM_NAV_ABSOLUTEURL]}{$get_params}\"{$link_class}>{$item[MIDCOM_NAV_NAME]}</a>";
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
     * @param int $id root ul id
     */
    public function set_root_element_id($id)
    {
        $this->root_object_id = $id;
    }
}
