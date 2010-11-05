<?php
/**
* @package fi.protie.navigation
* @author The Midgard Project, http://www.midgard-project.org
* @version $Id: viewer.php 3975 2006-09-06 17:36:03Z bergie $
* @copyright The Midgard Project, http://www.midgard-project.org
* @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
*/

/**
 * Versatile class for drawing dynamically navigation elements according to
 * user preferences.
 *
 * <code>
 * // Loads the component for the first time
 * $_MIDCOM->componentloader->load('fi.protie.navigation');
 *
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
     * @access private
     * @var midcom_helper_nav
     */
    var $_nap = null;

    /**
     * Stores the navigation access point history or in other words path to the current point.
     *
     * @access private
     * @var Array
     */
    var $node_path = array();

    /**
     * ID for the folder to get the navigation
     *
     * @access public
     * @var integer
     */
    var $root_id = null;

    /**
     * ID for the first printed list. Defined only in method $this->css_dropdown_headers();
     *
     * @access private
     * @var string
     */
    var $_root_element_id = null;

    /**
     * Number of the parsed level
     *
     * @access private
     * @var integer
     */
    var $_level = 1;

    /**
     * The amount of lowest level elements to be skipped.
     *
     * @access public
     * @var integer
     */
    var $skip_levels = 0;

    /**
     * Switch to determine if navigation should display leaves or pages.
     *
     * @access public
     * @var boolean
     */
    var $list_leaves = true;

    /**
     * List only the leaf elements or pages
     *
     * @access public
     * @var boolean
     */
    var $list_nodes = true;

    /**
     * Switch to determine if navigation should follow node path (on true) or stop on the
     * spot.
     *
     * @access public
     * @var boolean
     */
    var $follow_selected = true;

    /**
     * Switch to determine if navigation should follow all the nodes or only the current
     *
     * @access public
     * @var boolean
     */
    var $follow_all = false;

    /**
     * Switch to determine if navigation should show only the information of the currently selected node.
     *
     * @access public
     * @var boolean
     */
    var $show_only_current = false;

    /**
     * Should the CSS class be in the link as well
     *
     * @access public
     * @var boolean
     */
    var $class_to_link = false;

    /**
     * Restrict the amount of levels listed.
     *
     * @access public
     * @var integer
     */
    var $list_levels = 0;

    /**
     * ID of the root level list object
     *
     * @access public
     * @var integer
     */
    var $root_object_id = null;

    /**
     * CSS class for styling the lists
     *
     * @access public
     * @var string
     */
    var $css_list_style = 'fi_protie_navigation';

    /**
     * Add URL name to list item class name
     *
     * @access public
     * @var boolean
     */
    var $url_name_to_class = false;

    /**
     * Add component name to list item ul class name
     *
     * @access public
     * @var boolean
     */
    var $component_name_to_class = false;

    /**
     * Add first and last-class names to list item ul class name
     *
     * @access public
     * @var boolean
     */
    var $first_and_last_to_class = false;

    /**
     * CSS class for first
     *
     * @access public
     * @var string
     */
    var $css_first = 'first';

    /**
     * CSS class for last
     *
     * @access public
     * @var string
     */
    var $css_last = 'last';

    /**
     * CSS class for first and last together
     *
     * @access public
     * @var string
     */
    var $css_first_last = 'first_last';

    /**
     * Check if item has children and if so, add children-class to list item ul class name
     *
     * @access public
     * @var boolean
     */
    var $has_children_to_class = false;

    /**
     * Should the object's status be added to list item ul class names
     * Since this forces us to load the entire object, set it to false if you don't need it
     *
     * @access public
     * @var boolean
     */
    var $object_status_to_class = true;

    /**
     * CSS class for has children
     *
     * @access public
     * @var string
     */
    var $css_has_children = 'children';

    /**
     * CSS class for nodes
     *
     * @access public
     * @var string
     */
    var $css_node = 'node';

    /**
     * CSS class for leaves
     *
     * @access public
     * @var string
     */
    var $css_leaf = 'leaf';

    /**
     * CSS class for the elements in node path. All the elements in node path will have this class.
     *
     * @access public
     * @var string
     */
    var $css_selected = 'selected';

    /**
     * CSS class for the current, active node or leaf. There can be only one active element.
     *
     * @access public
     * @var string
     */
    var $css_active = 'active';

    /**
     * parameter listening enabled
     *
     * @access private
     * @var boolean
     */
    var $_listen_params = false;

    /**
     * Registered get -parameters for listening
     *
     * @access private
     * @var array
     */
    var $_get_params = array();

    /**
     * Registered post -parameters for listening
     * Not supported yet.
     *
     * @access private
     * @var array
     */
    var $_post_params = array();

    /**
     * Cache for parameters to be listened
     *
     * @access private
     * @var string
     */
    var $_params_cache = false;

    /**
     * Constructor method. Here we initialize the classes and variables
     * needed through the class.
     *
     * @access protected
     */
    function fi_protie_navigation ($id = null)
    {
        $this->_nap = new midcom_helper_nav();
        $this->node_path = $this->get_node_path();

        if (!is_null($id))
        {
            $this->root_id = $id;
        }
    }

    function listen_parameter($name, $value=false, $type='get')
    {
        if (empty($name))
        {
            return;
        }

        $type = strtolower($type);

        switch($type)
        {
            case 'post':
                if (   isset($this->_post_params[$name])
                    && $this->_post_params[$name] == $value)
                {
                    return;
                }
                $this->_post_params[$name] = $value;
            break;
            case 'get':
            default:
                if (   isset($this->_get_params[$name])
                    && $this->_get_params[$name] == $value)
                {
                    return;
                }
                $this->_get_params[$name] = $value;
        }

        $this->_listen_params = true;
    }

    function _collect_parameters()
    {
        if (empty($this->_get_params))
        {
            $this->_params_cache = '';
            return;
        }

        $_prefix = '?';
        $this->_params_cache = '';

        foreach ($this->_get_params as $key => $value)
        {
            if (isset($_GET[$key]))
            {
                if ($value)
                {
                    if ($_GET[$key] == $value)
                    {
                        $this->_params_cache .= "{$_prefix}{$key}={$value}";
                        $_prefix = '&';
                    }
                }
                elseif (! $_GET[$key])
                {
                    $this->_params_cache .= "{$_prefix}{$key}";
                    $_prefix = '&';
                }
            }
        }
    }

    function _get_parameter_string()
    {
        if (! $this->_params_cache)
        {
            $this->_collect_parameters();
        }

        return $this->_params_cache;
    }

    /**
     * Traverses through the node path to fetch the location of the current navigation access point.
     *
     * @access public
     * @static
     */
    function get_node_path()
    {
        // Initialize variables
        $node_path = array ();

        // Initialize the `midcom_helper_nav` class
        $nap = new midcom_helper_nav();

        // Get nodes
        $node_path = $nap->get_node_path();

        $leaf = $nap->get_current_leaf();

        // If NAP offers a leaf it should be stored in the node path
        if ($leaf)
        {
            $node_path[] = $leaf;
        }

        return $node_path;
    }

    /**
     * This method prints out links to CSS file when using CSS-based dropdown navigation.
     *
     * @access public
     */
    function css_dropdown_headers()
    {
        // Print the link for external CSS file
        $_MIDCOM->add_link_head(
            array
            (
                'rel'   => 'stylesheet',
                'type'  => 'text/css',
                'href'  => MIDCOM_STATIC_URL . '/fi.protie.navigation/dropdown.css',
                'media' => 'screen',
            )
        );

        $this->_first_level_navigation_id = 'fi_protie_navigation_root_element';

        // When using the CSS dropdown navigation we should always list the whole site tree
        $this->follow_all = true;
    }

    /**
     * Traverse the child nodes starting from the requested node id
     *
     * @access private
     */
    function _list_child_nodes($id, $indent = '')
    {
        $children = $this->_nap->list_nodes($id);

        // Stop traversing the path if there are no children
        if (   !$children
            || count($children) === 0)
        {
            return;
        }

        // Add ID property to the first unordered list ever called
        $element_id = '';
        if ($this->root_object_id)
        {
            $element_id = " id=\"{$this->root_object_id}\"";
            $this->root_object_id = null;
        }

        echo "{$indent}<ul class=\"{$this->css_list_style} node-{$id}\"{$element_id}>\n";

        $item_count = count($children);
        $item_counter = 0;

        // Draw each child element
        foreach ($children as $child)
        {
            $item_counter++;

            $item = $this->_nap->get_node($child);


            $classes = $this->_get_css_classes($child, $item, $item_counter, $item_count);

            $this->_display_element($item, $indent, $classes);
        }
        echo "{$indent}</ul>\n";
    }

    /**
     * Traverse the child elements starting from the requested node id
     *
     * @access private
     */
    function _list_child_elements($id, $indent = '')
    {

        // If only nodes are to be listed use the appropriate NAP call
        if (!$this->list_leaves)
        {
            $this->_list_child_nodes($id, $indent);
            return;
        }

        $children = $this->_nap->list_child_elements($id);

        // Stop traversing the path if there are no children
        if (   !$children
            || count($children) === 0)
        {
            return;
        }

        // Add ID property to the first unordered list ever called
        $element_id = '';
        if ($this->root_object_id)
        {
            $element_id = " id=\"{$this->root_object_id}\"";
            $this->root_object_id = null;
        }

        echo "{$indent}<ul class=\"{$this->css_list_style} node-{$id}\"{$element_id}>\n";

        $item_count = count($children);
        $item_counter = 0;

        // Draw each child element
        foreach ($children as $child)
        {
            $item_counter++;

            if ($child[MIDCOM_NAV_TYPE] === 'node')
            {
                $item = $this->_nap->get_node($child[MIDCOM_NAV_ID]);
            }
            else
            {
                $item = $this->_nap->get_leaf($child[MIDCOM_NAV_ID]);
            }
            $classes = $this->_get_css_classes($child, $item, $item_counter, $item_count);

            $this->_display_element($item, $indent, $classes);
        }

        echo "{$indent}</ul>\n";
    }

    private function _get_css_classes($child, $item, $item_counter, $item_count)
    {
        $classes = array();

        if ($child[MIDCOM_NAV_TYPE] === 'node')
        {
            // If the listing of nodes is set to false, skip this item and proceed to the next
            if ($this->list_nodes === false)
            {
                continue;
            }

            if (   $item[MIDCOM_NAV_ID] === $this->_nap->get_current_node()
                && (   !$this->_nap->get_current_leaf()
                    || !$this->_nap->get_leaf($this->_nap->get_current_leaf())
                   )
               )
            {
                $classes[] = $this->css_active;
            }

            if (in_array($item[MIDCOM_NAV_ID], $this->node_path, true))
            {
                $classes[] = $this->css_selected;
            }

            if ($this->component_name_to_class)
            {
                $classes[] = str_replace('.', '_', $item[MIDCOM_NAV_COMPONENT]);
            }
        }
        else
        {
            // Place the corresponding css class for the currently active leaf)
            if ($item[MIDCOM_NAV_ID] === $this->_nap->get_current_leaf())
            {
                $classes[] = $this->css_active;
                $classes[] = $this->css_selected;
            }
        }

        // Check if the URL name is supposed to be drawn
        if ($this->url_name_to_class)
        {
            $classes[] = str_replace('/', '', $item[MIDCOM_NAV_URL]);
        }

        if ($this->first_and_last_to_class)
        {
            if ($item_count == 1)
            {
                $classes[] = $this->css_first_last;
            }
            else if($item_counter == 1)
            {
                $classes[] = $this->css_first;
            }
            else if($item_counter == $item_count)
            {
                $classes[] = $this->css_last;
            }
        }

        if ($this->has_children_to_class)
        {
            if (!$this->list_leaves)
            {
                $children = $this->_nap->list_nodes($child[MIDCOM_NAV_ID]);
            }
            else
            {
                $children = $this->_nap->list_child_elements($child[MIDCOM_NAV_ID]);
            }
            if (is_array($children) && count($children) > 0)
            {
                $classes[] = $this->css_has_children;
            }
        }

        // Add information about the object's status
        if (   $this->object_status_to_class
            && isset($item[MIDCOM_NAV_OBJECT])
            && $css_status_class = $_MIDCOM->metadata->get_object_classes($item[MIDCOM_NAV_OBJECT]))
        {
            $classes[] = $css_status_class;
        }

        return implode(' ', $classes);

    }

    function _display_element($item, $indent, $css_classes)
    {

        // Finalize the class naming
        if ($css_classes !== '')
        {
            $class = " class=\"{$css_classes}\"";
        }
        else
        {
            $class = '';
        }

        $get_params = $this->_get_parameter_string();

        if ($this->class_to_link)
        {
            $link_class = $class;
        }
        else
        {
            $link_class = '';
        }

        echo "{$indent}    <li{$class}>\n";
        echo "{$indent}        <a href=\"{$item[MIDCOM_NAV_FULLURL]}{$get_params}\"{$link_class}>{$item[MIDCOM_NAV_NAME]}</a>\n";
        // If either of the follow nodes switches is on, follow all the nodes

        if (   $item[MIDCOM_NAV_TYPE] === 'node'
            && !$this->show_only_current
            && (   $this->list_levels === 0
                || $this->_level < $this->list_levels))
        {
            if (   $this->follow_all
                || (   $this->follow_selected
                    && in_array($item[MIDCOM_NAV_ID], $this->node_path, true)))
            {
                $this->_level++;
                $this->_list_child_elements($item[MIDCOM_NAV_ID], "{$indent}        ");
                $this->_level--;
            }
        }

        echo "{$indent}    </li>\n";
    }

    /**
     * Method for drawing the navigation.
     *
     * @access public
     */
    function draw()
    {
        if (!$this->root_id)
        {
            $this->root_id = $this->_nap->get_root_node();
        }

        if ($this->skip_levels !== 0)
        {
            if (!array_key_exists($this->skip_levels, $this->node_path))
            {
                return;
            }

            $this->root_id = $this->node_path[$this->skip_levels];
        }

        if ($this->show_only_current)
        {
            $this->root_id = $this->_nap->get_current_node();
        }

        $this->_list_child_elements ($this->root_id);
    }

    /**
     * Set the root element id
     *
     * @access public
     * @param string $id root ul id
     */
    function set_root_element_id($id)
    {
        $this->root_object_id = $id;
    }
}
?>
