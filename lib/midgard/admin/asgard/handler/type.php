<?php
/**
 * @package midgard.admin.asgard
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Symfony\Component\HttpFoundation\Request;

/**
 * Welcome interface
 *
 * @package midgard.admin.asgard
 */
class midgard_admin_asgard_handler_type extends midcom_baseclasses_components_handler
{
    use midgard_admin_asgard_handler;

    private $type;

    private function _prepare_qb(string $object_class) : ?midcom_core_querybuilder
    {
        // Figure correct MidCOM DBA class to use and get midcom QB
        $midcom_dba_classname = midcom::get()->dbclassloader->get_midcom_class_name_for_mgdschema_object($object_class);
        if (empty($midcom_dba_classname)) {
            debug_add("MidCOM DBA does not know how to handle " . $object_class, MIDCOM_LOG_ERROR);
            return null;
        }

        return $midcom_dba_classname::new_query_builder();
    }

    private function _search(string $term) : array
    {
        $type_class = $this->type;
        $resolver = new midcom_helper_reflector_tree($this->type);
        $search_results = $this->_search_type_qb($this->type, $term);

        foreach ($resolver->get_child_classes() as $child_class) {
            if ($child_class != $type_class) {
                $results = $this->_search_type_qb($child_class, $term);
                $search_results = array_merge($search_results, $results);
            }
        }

        return $search_results;
    }

    private function _search_type_qb(string $object_class, string $term) : array
    {
        $mgd_reflector = new midgard_reflection_property($object_class);

        $qb = $this->_prepare_qb($object_class);
        if (!$qb) {
            return [];
        }
        $type_fields = midcom_helper_reflector::get($object_class)->get_search_properties();

        $constraints = 0;
        $qb->begin_group('OR');
        foreach ($type_fields as $key) {
            $field_type = $mgd_reflector->get_midgard_type($key);
            switch ($field_type) {
                case MGD_TYPE_STRING:
                case MGD_TYPE_LONGTEXT:
                    $qb->add_constraint($key, 'LIKE', "%{$term}%");
                    $constraints++;
                    break;
                case MGD_TYPE_UINT:
                case MGD_TYPE_INT:
                    $qb->add_constraint($key, '=', (int) $term);
                    $constraints++;
                    break;
            }
        }
        $qb->end_group();
        if (!$constraints) {
            return [];
        }

        return $qb->execute();
    }

    private function _find_component()
    {
        // Figure out the component
        $dummy = new $this->type;
        $midcom_dba_classname = midcom::get()->dbclassloader->get_midcom_class_name_for_mgdschema_object($dummy);
        if (!$midcom_dba_classname) {
            throw new midcom_error("Failed to load DBA class for type {$this->type}.");
        }
        $component = midcom::get()->dbclassloader->get_component_for_class($midcom_dba_classname);
        $help_component = $component;
        if ($component == 'midcom') {
            $component = 'midgard';
            $help_component = 'midgard.admin.asgard';
        }

        $help = new midcom_admin_help_help();
        $this->_request_data['help'] = $help->get_help_contents('asgard_'.$this->type, $help_component);
        $this->_request_data['component'] = $component;
    }

    /**
     * Object editing view
     */
    public function _handler_type(Request $request, string $type, array &$data)
    {
        $this->type = $type;
        midcom::get()->auth->require_user_do('midgard.admin.asgard:manage_objects', null, 'midgard_admin_asgard_plugin');
        if (!midcom::get()->dbclassloader->get_midcom_class_name_for_mgdschema_object($this->type)) {
            throw new midcom_error_notfound("MgdSchema type '{$type}' not installed.");
        }

        if ($request->query->has('search')) {
            $data['search_results'] = $this->_search($request->query->get('search'));

            // If there is exactly one result, go there directly
            if (count($data['search_results']) == 1) {
                $url = $this->router->generate('object_' . $data['default_mode'], ['guid' => $data['search_results'][0]->guid]);
                return new midcom_response_relocate($url);
            }
            midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/jQuery/jquery.tablesorter.pack.js');
            $this->add_stylesheet(MIDCOM_STATIC_URL . '/midgard.admin.asgard/tablewidget.css');
        }

        $data['view_title'] = midgard_admin_asgard_plugin::get_type_label($this->type);

        $this->_find_component();
        $this->_prepare_toolbar($data);

        // Set the breadcrumb data
        $this->add_breadcrumb($this->router->generate('welcome'), $this->_l10n->get('midgard.admin.asgard'));
        $this->add_breadcrumb($this->router->generate('type', ['type' => $type]), $data['view_title']);
        return $this->get_response();
    }

    private function _prepare_toolbar(array $data)
    {
        $buttons = [];
        if (midcom::get()->auth->can_user_do('midgard:create', null, $this->type)) {
            $buttons[] = [
                MIDCOM_TOOLBAR_URL => $this->router->generate('object_create_toplevel', ['type' => $this->type]),
                MIDCOM_TOOLBAR_LABEL => sprintf($this->_l10n_midcom->get('create %s'), midgard_admin_asgard_plugin::get_type_label($this->type)),
                MIDCOM_TOOLBAR_GLYPHICON => midcom_helper_reflector_tree::get_create_icon($this->type),
            ];
        }

        if (midcom::get()->auth->admin) {
            $qb = new midgard_query_builder($this->type);
            $qb->include_deleted();
            $qb->add_constraint('metadata.deleted', '=', true);
            $deleted = $qb->count();
            if ($deleted > 0) {
                $buttons[] = [
                    MIDCOM_TOOLBAR_URL => $this->router->generate('trash_type', ['type' => $this->type]),
                    MIDCOM_TOOLBAR_LABEL => sprintf($this->_l10n->get('%s deleted items'), $deleted),
                    MIDCOM_TOOLBAR_GLYPHICON => 'trash',
                ];
            } else {
                $buttons[] = [
                    MIDCOM_TOOLBAR_URL => $this->router->generate('trash_type', ['type' => $this->type]),
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('trash is empty'),
                    MIDCOM_TOOLBAR_GLYPHICON => 'trash-o',
                ];
            }
        }
        if ($data['component'] != 'midgard') {
            $buttons[] = [
                MIDCOM_TOOLBAR_URL => $this->router->generate('components_component', ['component' => $data['component']]),
                MIDCOM_TOOLBAR_LABEL => $this->_i18n->get_string($data['component'], $data['component']),
                MIDCOM_TOOLBAR_GLYPHICON => 'puzzle-piece',
            ];
            $documentation_component = $data['component'];
        } else {
            $documentation_component = 'midcom';
        }

        $buttons[] = [
            MIDCOM_TOOLBAR_URL => "__ais/help/{$documentation_component}/mgdschemas/#{$this->type}",
            MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('type documentation'),
            MIDCOM_TOOLBAR_OPTIONS => ['target' => '_blank'],
            MIDCOM_TOOLBAR_GLYPHICON => 'question',
        ];

        $data['asgard_toolbar']->add_items($buttons);
    }

    /**
     * Shows the loaded object in editor.
     */
    public function _show_type(string $handler_id, array &$data)
    {
        $data['current_type'] = $this->type;

        // Show the garbage bins of child types
        $data['type'] = $this->type;
        $reflector = new midcom_helper_reflector_tree($this->type);
        $data['type_name'] = $this->type;
        $data['type_translated'] = $reflector->get_class_label();
        $data['parent_type'] = $reflector->get_parent_class();
        $data['prefix'] = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);

        midcom_show_style('midgard_admin_asgard_type');

        $types = $reflector->get_child_classes();

        if (!empty($types)) {
            midcom_show_style('midgard_admin_asgard_type_children_start');
            $this->show_child_types($types, $data);
            midcom_show_style('midgard_admin_asgard_type_children_end');
        }
    }

    private function show_child_types(array $types, array &$data)
    {
        midcom_show_style('midgard_admin_asgard_type_children_header');

        foreach ($types as $child_type) {
            $reflector = new midcom_helper_reflector_tree($child_type);
            $data['type_name'] = $child_type;
            $data['type_translated'] = $reflector->get_class_label();

            midcom_show_style('midgard_admin_asgard_type_children_item');
        }

        midcom_show_style('midgard_admin_asgard_type_children_footer');
    }
}
