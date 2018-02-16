<?php
/**
 * @package midgard.admin.asgard
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Navigation class for Asgard
 *
 * @package midgard.admin.asgard
 */
class midgard_admin_asgard_navigation extends midcom_baseclasses_components_purecode
{
    /**
     * Root types
     *
     * @var array
     */
    public $root_types = [];

    /**
     * Some object
     *
     * @var midgard_object
     */
    protected $_object = null;

    /**
     * Object path to the current object.
     *
     * @var Array
     */
    private $_object_path = [];

    private $_reflectors = [];
    private $_request_data = [];
    private $expanded_root_types = [];
    protected $shown_objects = [];

    public function __construct($object, &$request_data)
    {
        parent::__construct();

        $this->_object = $object;
        $this->get_object_path();
        $this->_request_data =& $request_data;

        $this->root_types = midcom_helper_reflector_tree::get_root_classes();

        if (array_key_exists('current_type', $this->_request_data)) {
            $expanded_type = $this->_request_data['current_type'];
            if (!in_array($expanded_type, $this->root_types)) {
                $expanded_type = midcom_helper_reflector_tree::get($expanded_type)->get_parent_class();
            }
            $this->expanded_root_types[] = $expanded_type;
        } elseif (isset($this->_object)) {
            // we go through the path bottom up and show the first root type we find
            foreach (array_reverse($this->_object_path) as $object) {
                foreach ($this->root_types as $root_type) {
                    if (    is_a($object, $root_type)
                        || midcom_helper_reflector::is_same_class($root_type, $object->__midcom_class_name__)) {
                        $this->expanded_root_types[] = $root_type;
                        break;
                    }
                }
            }
        }
    }

    /**
     *
     * @param midgard_object $object
     * @return midcom_helper_reflector_tree
     */
    protected function _get_reflector($object)
    {
        if (is_string($object)) {
            $classname = $object;
        } else {
            $classname = get_class($object);
        }
        if (!isset($this->_reflectors[$classname])) {
            $this->_reflectors[$classname] = midcom_helper_reflector_tree::get($object);
        }

        return $this->_reflectors[$classname];
    }

    private function get_object_path()
    {
        if (is_object($this->_object)) {
            foreach (midcom_helper_reflector_tree::resolve_path_parts($this->_object) as $part) {
                $this->_object_path[] = $part['object'];
            }
        }
    }

    protected function _is_collapsed($type, $total)
    {
        return (   $total > $this->_config->get('max_navigation_entries')
                && empty($_GET['show_all_' . $type]));
    }

    protected function _list_child_elements($object, $level = 0)
    {
        if ($level > 25) {
            debug_add('Recursion level 25 exceeded, aborting', MIDCOM_LOG_ERROR);
            return;
        }
        $ref = $this->_get_reflector($object);

        $child_types = [];
        foreach ($ref->get_child_classes() as $class) {
            $qb = $ref->_child_objects_type_qb($class, $object, false);

            if (   !$qb
                || !($count = $qb->count_unchecked())) {
                continue;
            }
            midcom_helper_reflector_tree::add_schema_sorts_to_qb($qb, $class);
            if ($this->_is_collapsed($class, $count)) {
                $qb->set_limit($this->_config->get('max_navigation_entries'));
            }
            $child_types[$class] = ['total' => $count, 'qb' => $qb];
        }

        if (!empty($child_types)) {
            echo "<ul>\n";
            foreach ($child_types as $type => $data) {
                $children = $data['qb']->execute();
                $label_mapping = [];
                foreach ($children as $i => $child) {
                    if (isset($this->shown_objects[$child->guid])) {
                        continue;
                    }

                    $ref = $this->_get_reflector($child);
                    $label_mapping[$i] = htmlspecialchars($ref->get_object_label($child));
                }

                asort($label_mapping);

                foreach ($label_mapping as $index => $label) {
                    $child = $children[$index];
                    $this->_draw_element($child, $label, $level);
                }
                if ($this->_is_collapsed($type, $data['total'])) {
                    $this->_draw_collapsed_element($level, $type, $data['total']);
                }
            }
            echo "</ul>\n";
        }
    }

    /**
     * Renders the given root objects to HTML and calls _list_child_elements()
     *
     * @param midcom_helper_reflector_tree $ref Reflector singleton
     */
    private function _list_root_elements(midcom_helper_reflector_tree $ref)
    {
        $qb = $ref->_root_objects_qb(false);

        if (   !$qb
            || !($total = $qb->count_unchecked())) {
            return;
        }
        midcom_helper_reflector_tree::add_schema_sorts_to_qb($qb, $ref->mgdschema_class);
        if ($this->_is_collapsed($ref->mgdschema_class, $total)) {
            $qb->set_limit($this->_config->get('max_navigation_entries'));
        }

        echo "<ul class=\"midgard_admin_asgard_navigation\">\n";

        $root_objects = $qb->execute();

        $label_mapping = [];
        foreach ($root_objects as $i => $object) {
            $label_mapping[$i] = htmlspecialchars($ref->get_object_label($object));
        }

        asort($label_mapping);
        $autoexpand = (sizeof($root_objects) == 1);
        foreach ($label_mapping as $index => $label) {
            $object = $root_objects[$index];
            $this->_draw_element($object, $label, 1, $autoexpand);
        }
        if ($this->_is_collapsed($ref->mgdschema_class, $total)) {
            $this->_draw_collapsed_element(0, $ref->mgdschema_class, $total);
        }

        echo "</ul>\n";
    }

    private function _draw_collapsed_element($level, $type, $total)
    {
        $ref = midcom_helper_reflector::get($type);
        if (!empty($this->_object_path[$level])) {
            if (   $this->_object_path[$level]->__mgdschema_class_name__ == $type
                && !array_key_exists($this->_object_path[$level]->guid, $this->shown_objects)) {
                $label = htmlspecialchars($ref->get_object_label($this->_object_path[$level]));
                $this->_draw_element($this->_object_path[$level], $label, $level);
            }
        }
        $icon = midcom_helper_reflector::get_object_icon(new $type);
        echo '<li><a class="expand-type-children" href="?show_all_' . $type . '=1">' . $icon . ' ' . sprintf($this->_l10n->get('show all %s %s entries'), $total, $ref->get_class_label()) . '</a></li>';
    }

    protected function _draw_element($object, $label, $level = 0, $autoexpand = false)
    {
        $ref = $this->_get_reflector($object);

        $selected = $this->_is_selected($object);
        $css_class = get_class($object);
        $this->_common_css_classes($object, $ref, $css_class);

        $mode = $this->_request_data['default_mode'];
        if (strpos($css_class, 'readonly')) {
            $mode = 'view';
        }

        $this->shown_objects[$object->guid] = true;

        echo "    <li class=\"{$css_class}\">";

        $icon = $ref->get_object_icon($object);

        if (trim($label) == '') {
            $label = $ref->get_class_label() . ' #' . $object->id;
        }

        echo "<a href=\"" . midcom_connection::get_url('self') . "__mfa/asgard/object/{$mode}/{$object->guid}/\" title=\"GUID: {$object->guid}, ID: {$object->id}\">{$icon}{$label}</a>\n";
        if (   $selected
            || $autoexpand) {
            $this->_list_child_elements($object, $level + 1);
        }
        echo "    </li>\n";
    }

    private function _draw_plugins()
    {
        $customdata = midcom::get()->componentloader->get_all_manifest_customdata('asgard_plugin');
        foreach ($customdata as $component => $plugin_config) {
            $this->_request_data['section_url'] = midcom_connection::get_url('self') . "__mfa/asgard_{$component}/";
            $this->_request_data['section_name'] = $this->_i18n->get_string($plugin_config['name'], $component);
            $class = $plugin_config['class'];

            if (!midcom::get()->auth->can_user_do("{$component}:access", null, $class)) {
                // Disabled plugin
                continue;
            }

            if (   method_exists($class, 'navigation')
                && ($this->_request_data['plugin_name'] == "asgard_{$component}")) {
                $this->_request_data['expanded'] = true;
                midcom_show_style('midgard_admin_asgard_navigation_section_header');
                call_user_func([$class, 'navigation']);
            } else {
                $this->_request_data['expanded'] = false;
                midcom_show_style('midgard_admin_asgard_navigation_section_header');
            }

            midcom_show_style('midgard_admin_asgard_navigation_section_footer');
        }
    }

    private function _is_selected($object)
    {
        foreach ($this->_object_path as $path_object) {
            if ($object->guid == $path_object->guid) {
                return true;
            }
        }
        return false;
    }

    protected function _common_css_classes($object, $ref, &$css_class)
    {
        $css_class .= " {$ref->mgdschema_class}";

        // Populate common properties
        $css_class = midcom::get()->metadata->get_object_classes($object, $css_class);

        if ($this->_is_selected($object)) {
            $css_class .= ' selected';
        }
        if (   is_object($this->_object)
            && (   $object->guid == $this->_object->guid
                || (   is_a($this->_object, midcom_db_parameter::class)
                    && $object->guid == $this->_object->parentguid))) {
            $css_class .= ' current';
        }
        if ( !$object->can_do('midgard:update')) {
            $css_class .= ' readonly';
        }
    }

    /**
     * Appliy visibility restrictions from various sources
     *
     * @return array Alphabetically sorted list of class => title pairs
     */
    private function _process_root_types()
    {
        // Included or excluded types
        $types = [];

        // Get the types that might have special display conditions
        if (   $this->_config->get('midgard_types')
            && preg_match_all('/\|([a-z0-9\.\-_]+)/', $this->_config->get('midgard_types'), $regs)) {
            $types = $regs[1];
        }

        // Override with user selected
        // @TODO: Should this just include to the configuration selection, although it would break the consistency
        // of other similar preference sets, which simply override the global settings?
        if (   midgard_admin_asgard_plugin::get_preference('midgard_types')
            && preg_match_all('/\|([a-z0-9\.\-_]+)/', midgard_admin_asgard_plugin::get_preference('midgard_types'), $regs)) {
            $types = $regs[1];
        }

        // Get the inclusion/exclusion model
        $model = $this->_config->get('midgard_types_model');
        if (midgard_admin_asgard_plugin::get_preference('midgard_types_model')) {
            $model = midgard_admin_asgard_plugin::get_preference('midgard_types_model');
        }
        $exclude = ($model == 'exclude');

        // Get the possible regular expression
        $regexp = $this->_config->get('midgard_types_regexp');
        if (midgard_admin_asgard_plugin::get_preference('midgard_types_regexp')) {
            $regexp = midgard_admin_asgard_plugin::get_preference('midgard_types_regexp');
        }

        // "Convert" quickly to PERL regular expression
        if (!preg_match('/^[\/|]/', $regexp)) {
            $regexp = "/{$regexp}/";
        }

        if ($exclude) {
            $types = array_diff($this->root_types, $types);
        } elseif (!empty($types)) {
            $types = array_intersect($this->root_types, $types);
        }

        $label_mapping = [];
        foreach ($types as $root_type) {
            // If the regular expression has been set, check which types should be shown
            if (   $regexp !== '//'
                && (boolean) preg_match($regexp, $root_type) == $exclude) {
                continue;
            }

            $ref = $this->_get_reflector($root_type);
            $label_mapping[$root_type] = $ref->get_class_label();
        }
        asort($label_mapping);

        return $label_mapping;
    }

    public function draw()
    {
        $this->_request_data['chapter_name'] = midcom::get()->config->get('midcom_site_title');
        midcom_show_style('midgard_admin_asgard_navigation_chapter');

        $this->_draw_plugins();

        if (!midcom::get()->auth->can_user_do('midgard.admin.asgard:manage_objects', null, 'midgard_admin_asgard_plugin')) {
            return;
        }

        $label_mapping = $this->_process_root_types();

        $expanded_types = array_intersect(array_keys($label_mapping), $this->expanded_root_types);

        /*
         * Use a dropdown for displaying the navigation if at least one type is expanded
         * and the user has the corresponding preference set. That way, you expanded types
         * can take up the maximum available space while all types are still accessible with one
         * click if nothing is expanded
         */
        $types_shown = false;
        if (    sizeof($expanded_types) > 0
             && midgard_admin_asgard_plugin::get_preference('navigation_type') === 'dropdown') {
            $this->_draw_select_navigation();
            $types_shown = true;
        }

        foreach ($expanded_types as $root_type) {
            $this->_request_data['section_url'] = midcom_connection::get_url('self') . "__mfa/asgard/{$root_type}";
            $this->_request_data['section_name'] = $label_mapping[$root_type];
            $this->_request_data['expanded'] = true;
            midcom_show_style('midgard_admin_asgard_navigation_section_header');
            $ref = $this->_get_reflector($root_type);
            $this->_list_root_elements($ref);

            midcom_show_style('midgard_admin_asgard_navigation_section_footer');
        }

        if (!$types_shown) {
            $this->_request_data['section_name'] = $this->_l10n->get('midgard objects');
            $this->_request_data['section_url'] = null;
            $this->_request_data['expanded'] = true;
            midcom_show_style('midgard_admin_asgard_navigation_section_header');
            $collapsed_types = array_diff_key($label_mapping, array_flip($expanded_types));

            $this->_draw_type_list($collapsed_types);

            midcom_show_style('midgard_admin_asgard_navigation_section_footer');
        }
    }

    private function _draw_type_list(array $types)
    {
        echo "<ul class=\"midgard_admin_asgard_navigation\">\n";

        foreach ($types as $type => $label) {
            $url = midcom_connection::get_url('self') . "__mfa/asgard/{$type}";
            echo "    <li class=\"mgdschema-type\">";

            $dbaclass = midcom::get()->dbclassloader->get_midcom_class_name_for_mgdschema_object($type);
            if (   $dbaclass
                && class_exists($dbaclass)) {
                $object = new $dbaclass;
            } else {
                $object = new $type;
            }
            $icon = midcom_helper_reflector::get_object_icon($object);

            echo "<a href=\"" . $url . "\" title=\"{$label}\">{$icon}{$label}</a>\n";

            echo "    </li>\n";
        }

        echo "</ul>\n";
    }

    private function _draw_select_navigation()
    {
        if (!empty($this->_object_path)) {
            $this->_request_data['root_object'] = $this->_object_path[0];
            $this->_request_data['navigation_type'] = $this->_object_path[0]->__mgdschema_class_name__;
        } elseif (isset($this->expanded_root_types[0])) {
            $this->_request_data['navigation_type'] = $this->expanded_root_types[0];
        } else {
            $this->_request_data['navigation_type'] = '';
        }

        $label_mapping = [];

        foreach ($this->root_types as $root_type) {
            $ref = $this->_get_reflector($root_type);
            $label_mapping[$root_type] = $ref->get_class_label();
        }
        asort($label_mapping);

        $this->_request_data['label_mapping'] = $label_mapping;
        $this->_request_data['expanded_root_types'] = $this->expanded_root_types;

        midcom_show_style('midgard_admin_asgard_navigation_sections');
    }
}
