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
class midgard_admin_asgard_navigation
{
    use midcom_baseclasses_components_base;

    /**
     * @var array
     */
    public $root_types = [];

    /**
     * @var midgard\portable\api\mgdobject
     */
    protected $_object;

    /**
     * Object path to the current object.
     *
     * @var array
     */
    private $_object_path = [];

    private $_request_data = [];
    private $expanded_root_types = [];
    protected $shown_objects = [];

    public function __construct(?object $object, array &$request_data)
    {
        $this->_component = 'midgard.admin.asgard';

        $this->_object = $object;
        $this->_request_data =& $request_data;

        $this->root_types = midcom_helper_reflector_tree::get_root_classes();

        if (array_key_exists('current_type', $request_data)) {
            $expanded_type = $request_data['current_type'];
            if (!in_array($expanded_type, $this->root_types)) {
                $expanded_type = midcom_helper_reflector_tree::get($expanded_type)->get_parent_class();
            }
            $this->expanded_root_types[] = $expanded_type;
        } elseif (isset($this->_object)) {
            $this->_object_path = array_column(midcom_helper_reflector_tree::resolve_path_parts($object), 'object');

            // we go through the path bottom up and show the first root type we find
            foreach (array_reverse($this->_object_path) as $node) {
                foreach ($this->root_types as $root_type) {
                    if (   is_a($node, $root_type)
                        || midcom_helper_reflector::is_same_class($root_type, $node->__midcom_class_name__)) {
                        $this->expanded_root_types[] = $root_type;
                        break;
                    }
                }
            }
        }
    }

    protected function _is_collapsed(string $type, int $total) : bool
    {
        return (   $total > $this->_config->get('max_navigation_entries')
                && empty($_GET['show_all_' . $type]));
    }

    protected function _list_child_elements(object $object, int $level = 0)
    {
        if ($level > 25) {
            debug_add('Recursion level 25 exceeded, aborting', MIDCOM_LOG_ERROR);
            return;
        }
        $ref = midcom_helper_reflector_tree::get($object);

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

                    $ref = midcom_helper_reflector_tree::get($child);
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
     */
    private function _list_root_elements(midcom_helper_reflector_tree $ref)
    {
        $qb = $ref->_root_objects_qb();

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
        $autoexpand = (count($root_objects) == 1);
        foreach ($label_mapping as $index => $label) {
            $object = $root_objects[$index];
            $this->_draw_element($object, $label, 1, $autoexpand);
        }
        if ($this->_is_collapsed($ref->mgdschema_class, $total)) {
            $this->_draw_collapsed_element(0, $ref->mgdschema_class, $total);
        }

        echo "</ul>\n";
    }

    private function _draw_collapsed_element(int $level, string $type, int $total)
    {
        $ref = midcom_helper_reflector::get($type);
        if (!empty($this->_object_path[$level])) {
            if ($this->_object_path[$level]->__mgdschema_class_name__ == $type) {
                $object = $this->_object_path[$level];
            } elseif ($level == 0) {
                // this is the case where our object has parents, but we're in its type view directly
                foreach ($this->_object_path as $candidate) {
                    if ($candidate->__mgdschema_class_name__ == $type) {
                        $object = $candidate;
                        break;
                    }
                }
            }
            if (!empty($object)) {
                $label = htmlspecialchars($ref->get_object_label($object));
                $this->_draw_element($object, $label, $level);
            }
        }
        $icon = midcom_helper_reflector::get_object_icon(new $type);
        echo '<li><a class="expand-type-children" href="?show_all_' . $type . '=1">' . $icon . ' ' . sprintf($this->_l10n->get('show all %s %s entries'), $total, $ref->get_class_label()) . '</a></li>';
    }

    protected function _draw_element(object $object, string $label, int $level, bool $autoexpand = false)
    {
        $ref = midcom_helper_reflector_tree::get($object);

        $selected = $this->_is_selected($object);
        $css_class = $this->get_css_classes($object, $ref->mgdschema_class);

        $mode = $this->_request_data['default_mode'];
        if (str_contains($css_class, 'readonly')) {
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
            $this->_request_data['section_name'] = $this->_i18n->get_string($component, $component);
            $class = $plugin_config['class'];

            if (!midcom::get()->auth->can_user_do("{$component}:access", null, $class)) {
                // Disabled plugin
                continue;
            }

            if (   method_exists($class, 'navigation')
                && ($this->_request_data['plugin_name'] == "asgard_{$component}")) {
                $this->_request_data['expanded'] = true;
                midcom_show_style('midgard_admin_asgard_navigation_section_header');
                $class::navigation();
            } else {
                $this->_request_data['expanded'] = false;
                midcom_show_style('midgard_admin_asgard_navigation_section_header');
            }

            midcom_show_style('midgard_admin_asgard_navigation_section_footer');
        }
    }

    private function _is_selected(object $object) : bool
    {
        foreach ($this->_object_path as $path_object) {
            if ($object->guid == $path_object->guid) {
                return true;
            }
        }
        return false;
    }

    protected function get_css_classes(object $object, string $mgdschema_class) : string
    {
        $css_class = get_class($object) . " {$mgdschema_class}";

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
        return $css_class;
    }

    /**
     * Apply visibility restrictions from various sources
     *
     * @return array Alphabetically sorted list of class => title pairs
     */
    private function _process_root_types() : array
    {
        // Get the types that might have special display conditions
        // @TODO: Should this just include to the configuration selection, although it would break the consistency
        // of other similar preference sets, which simply override the global settings?
        $selected = midgard_admin_asgard_plugin::get_preference('midgard_types');

        // Get the inclusion/exclusion model
        $model = midgard_admin_asgard_plugin::get_preference('midgard_types_model');
        $exclude = ($model == 'exclude');

        $label_mapping = midgard_admin_asgard_plugin::get_root_classes();

        if (preg_match_all('/\|([a-z0-9\.\-_]+)/', $selected, $regs)) {
            $types = array_flip($regs[1]);
            if ($exclude) {
                $label_mapping = array_diff_key($label_mapping, $types);
            } else {
                $label_mapping = array_intersect_key($label_mapping, $types);
            }
        }

        // Get the possible regular expression
        $regexp = midgard_admin_asgard_plugin::get_preference('midgard_types_regexp');

        // "Convert" quickly to PERL regular expression
        if (!preg_match('/^[\/|]/', $regexp)) {
            $regexp = "/{$regexp}/";
        }

        // If the regular expression has been set, check which types should be shown
        if ($regexp !== '//') {
            $label_mapping = array_filter($label_mapping, function ($root_type) use ($regexp, $exclude) {
                return preg_match($regexp, $root_type) == $exclude;
            }, ARRAY_FILTER_USE_KEY);
        }

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
        if (    !empty($expanded_types)
             && midgard_admin_asgard_plugin::get_preference('navigation_type') === 'dropdown') {
            $this->_draw_select_navigation();
            $types_shown = true;
        }

        foreach ($expanded_types as $root_type) {
            $this->_request_data['section_url'] = midcom_connection::get_url('self') . "__mfa/asgard/{$root_type}";
            $this->_request_data['section_name'] = $label_mapping[$root_type];
            $this->_request_data['expanded'] = true;
            midcom_show_style('midgard_admin_asgard_navigation_section_header');
            $ref = midcom_helper_reflector_tree::get($root_type);
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
            $url = midcom_connection::get_url('self') . "__mfa/asgard/{$type}/";
            echo "    <li class=\"mgdschema-type\">";

            if ($dbaclass = midcom::get()->dbclassloader->get_midcom_class_name_for_mgdschema_object($type)) {
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

        $this->_request_data['label_mapping'] = midgard_admin_asgard_plugin::get_root_classes();
        $this->_request_data['expanded_root_types'] = $this->expanded_root_types;

        midcom_show_style('midgard_admin_asgard_navigation_sections');
    }
}
