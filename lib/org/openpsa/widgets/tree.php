<?php
/**
 * @package org.openpsa.widgets
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * fancytree-based tree widget
 *
 * @package org.openpsa.widgets
 */
class org_openpsa_widgets_tree
{
    /**
     * The tree's root node, if any
     */
    public int $root_node = 0;

    /**
     * The object fields to use for link titles
     */
    public array $title_fields = ['title'];

    /**
     * Callback for rendering object links. It receives the GUID as parameter
     *
     * @var callable
     */
    public $link_callback;

    /**
     * The constraints for the children MC
     */
    public array $constraints = [];

    private string $_object_class;

    private string $_parent_field;

    private static bool $_head_elements_added = false;

    /**
     * Adds head elements and initializes some variables
     *
     * @param string $parent_field Where to look for children
     */
    public function __construct(string $classname, string $parent_field)
    {
        $this->_object_class = $classname;
        $this->_parent_field = $parent_field;

        self::add_head_elements();
    }

    public function render(array $items = [])
    {
        if (empty($items)) {
            $items = $this->_list_items($this->root_node);
            if (empty($items)) {
                return;
            }
        }

        $identifier = 't_' . md5('org_openpsa_widgets_treemenu_' . $this->_object_class);

        echo '<div class="openpsa-fancytree-container" id="' . $identifier . "\">\n";
        $this->_render_items($items);
        echo "\n</div>\n";

        echo <<<JSINIT
<script type="text/javascript">
    org_openpsa_tree.setup("{$identifier}");
</script>
JSINIT;
    }

    /**
     * Internal helper for loading the items recursively
     *
     * @param int $id The parent object ID
     */
    private function _list_items(int $id) : array
    {
        $data = [];

        $value_properties = ['id'];
        $mc = midcom::get()->dbfactory->new_collector($this->_object_class, $this->_parent_field, $id);
        foreach ($this->constraints as [$field, $operator, $value]) {
            $mc->add_constraint($field, $operator, $value);
        }
        foreach ($this->title_fields as $field) {
            $value_properties[] = $field;
            $mc->add_order($field);
        }

        foreach ($mc->get_rows($value_properties) as $guid => $values) {
            $entry = ['guid' => $guid];

            foreach ($this->title_fields as $field) {
                if (!empty($values[$field])) {
                    $entry['title'] = $values[$field];
                    break;
                }
            }
            if (empty($entry['title'])) {
                $entry['title'] = $entry['guid'];
            }

            $entry['children'] = $this->_list_items($values['id']);
            $data[] = $entry;
        }
        return $data;
    }

    private function _render_items(array $items)
    {
        if (empty($items)) {
            return;
        }
        $prefix = midcom::get()->get_host_prefix();
        echo "<ul>\n";
        foreach ($items as $item) {
            if (is_callable($this->link_callback)) {
                $url = call_user_func($this->link_callback, $item['guid']);
            } else {
                $url = $prefix . 'midcom-permalink-' . $item['guid'];
            }
            echo '<li id="g_' . $item['guid'] . '"><a href="' . $url . '">' . $item['title'] . "</a>\n";
            if (!empty($item['children'])) {
                $this->_render_items($item['children']);
            }
            echo "</li>\n";
        }
        echo "</ul>\n";
    }

    /**
     * Add necessary head elements
     */
    public static function add_head_elements()
    {
        if (self::$_head_elements_added) {
            return;
        }

        $head = midcom::get()->head;
        $head->enable_jquery_ui();
        $head->add_jsfile(MIDCOM_STATIC_URL . '/jQuery/fancytree-2.38.5/jquery.fancytree-all.min.js');
        $head->add_stylesheet(MIDCOM_STATIC_URL . "/stock-icons/font-awesome-4.7.0/css/font-awesome.min.css");
        $head->add_stylesheet(MIDCOM_STATIC_URL . "/jQuery/fancytree-2.38.5/skin-awesome/ui.fancytree.min.css");
        $head->add_stylesheet(MIDCOM_STATIC_URL . "/org.openpsa.widgets/fancytree.custom.css");
        $head->add_jsfile(MIDCOM_STATIC_URL . '/org.openpsa.widgets/fancytree.custom.js');
        self::$_head_elements_added = true;
    }
}
