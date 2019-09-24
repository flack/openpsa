<?php
/**
 * @package midgard.admin.asgard
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Symfony\Component\HttpFoundation\Request;

/**
 * Undelete/purge interface
 *
 * @package midgard.admin.asgard
 */
class midgard_admin_asgard_handler_undelete extends midcom_baseclasses_components_handler
{
    use midgard_admin_asgard_handler;

    private $type = '';

    public function _on_initialize()
    {
        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/jQuery/jquery.tablesorter.pack.js');
        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/midgard.admin.asgard/jquery.batch_process.js');
        $this->add_stylesheet(MIDCOM_STATIC_URL . '/midgard.admin.asgard/tablewidget.css');
    }

    /**
     * Trash view
     */
    public function _handler_trash(array &$data)
    {
        midcom::get()->auth->require_admin_user();

        $data['view_title'] = $this->_l10n->get('trash');

        $data['types'] = [];
        foreach (midcom_connection::get_schema_types() as $type) {
            // Objects that don't have metadata should not be shown in trash.
            if (!midgard_reflector_object::has_metadata_class($type)) {
                debug_add("{$type} has no metadata, skipping");
                continue;
            }

            $qb = new midgard_query_builder($type);
            $qb->include_deleted();
            $qb->add_constraint('metadata.deleted', '=', true);
            $data['types'][$type] = $qb->count();
        }

        // Set the breadcrumb data
        $this->add_breadcrumb($this->router->generate('welcome'), $this->_l10n->get('midgard.admin.asgard'));
        $this->add_breadcrumb($this->router->generate('trash'), $this->_l10n->get('trash'));
        return $this->get_response('midgard_admin_asgard_trash');
    }

    /**
     * Trash view
     */
    public function _handler_trash_type(Request $request, string $type, array &$data)
    {
        midcom::get()->auth->require_admin_user();

        $this->type = $type;

        $data['view_title'] = sprintf($this->_l10n->get('%s trash'), midgard_admin_asgard_plugin::get_type_label($type));

        $dummy = new $type;
        $data['midcom_dba_classname'] = midcom::get()->dbclassloader->get_midcom_class_name_for_mgdschema_object($dummy);
        $data['type'] = $type;
        $data['reflector'] = midcom_helper_reflector::get($data['type']);
        $data['label_property'] = $data['reflector']->get_label_property();

        if ($request->request->has('undelete')) {
            $guids = $request->request->get('undelete');
            if ($request->request->has('purge')) {
                $this->_purge($guids);
            } else {
                $this->_undelete($guids);
            }
            return new midcom_response_relocate($this->router->generate('trash_type', ['type' => $type]));
        }

        $qb = new org_openpsa_qbpager_direct($data['type'], "{$data['type']}_trash");
        $qb->include_deleted();
        $qb->add_constraint('metadata.deleted', '=', true);
        $qb->add_order('metadata.revised', 'DESC');
        $data['qb'] = $qb;
        $data['trash'] = $qb->execute_unchecked();

        // Set the breadcrumb data
        $this->add_breadcrumb($this->router->generate('welcome'), $this->_l10n->get($this->_component));
        $this->add_breadcrumb($this->router->generate('trash'), $this->_l10n->get('trash'));
        $this->add_breadcrumb($this->router->generate('trash_type', ['type' => $type]), midgard_admin_asgard_plugin::get_type_label($type));

        $data['current_type'] = $this->type;
        $data['handler'] = $this;

        return $this->get_response('midgard_admin_asgard_trash_type');
    }

    private function _purge(array $guids)
    {
        $purged_size = 0;

        if (!$this->_request_data['midcom_dba_classname']) {
            // No DBA class for the type, use plain Midgard undelete API
            foreach ($guids as $guid) {
                $qb = new midgard_query_builder($this->type);
                $qb->add_constraint('guid', '=', $guid);
                $qb->include_deleted();
                foreach ($qb->execute() as $object) {
                    if ($object->purge()) {
                        $purged_size += $object->metadata->size;
                    }
                }
            }
        } else {
            // Delegate purging to DBA
            $purged_size = midcom_baseclasses_core_dbobject::purge($guids, $this->type);
        }

        if ($purged_size) {
            midcom::get()->uimessages->add($this->_l10n->get('midgard.admin.asgard'), sprintf($this->_l10n->get('in total %s purged'), midcom_helper_misc::filesize_to_string($purged_size)), 'info');
        }
    }

    private function _undelete(array $guids)
    {
        // Delegate undeletion to DBA
        $undeleted_size = midcom_baseclasses_core_dbobject::undelete($guids);
        if ($undeleted_size > 0) {
            midcom::get()->uimessages->add($this->_l10n->get('midgard.admin.asgard'), sprintf($this->_l10n->get('in total %s undeleted'), midcom_helper_misc::filesize_to_string($undeleted_size)), 'info');
        }
    }

    public function show_type($object, $indent = 0, $prefix = '', $enable_undelete = true)
    {
        static $shown = [];
        static $url_prefix = '';
        if (!$url_prefix) {
            $url_prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
        }

        if (isset($shown[$object->guid])) {
            return;
        }

        $revisor = midcom::get()->auth->get_user($object->metadata->revisor);

        $reflector = midcom_helper_reflector_tree::get($object);
        $icon = $reflector->get_object_icon($object);

        echo "{$prefix}<tr>\n";

        $disabled = '';
        if (!$enable_undelete) {
            $disabled = ' disabled="disabled"';
        }

        $object_label = $reflector->get_object_label($object);
        if (empty($object_label)) {
            $object_label = $object->guid;
        }
        echo "{$prefix}    <td class=\"checkbox\"><input type=\"checkbox\" name=\"undelete[]\"{$disabled} value=\"{$object->guid}\" id=\"guid_{$object->guid}\" /></td>\n";
        echo "{$prefix}    <td class=\"label\" style=\"padding-left: {$indent}px\"><a href=\"". $this->router->generate('object_deleted', ['guid' =>$object->guid]) . "\">{$icon} " . $object_label . "</a></td>\n";
        echo "{$prefix}    <td class=\"nowrap\">" . strftime('%x %X', strtotime($object->metadata->revised)) . "</td>\n";

        if (!empty($revisor->guid)) {
            echo "{$prefix}    <td><a href=\"{$url_prefix}__mfa/asgard/object/view/{$revisor->guid}/\">{$revisor->name}</a></td>\n";
        } else {
            echo "{$prefix}    <td>&nbsp;</td>\n";
        }
        echo "{$prefix}    <td>" . midcom_helper_misc::filesize_to_string($object->metadata->size) . "</td>\n";
        echo "{$prefix}</tr>\n";

        $child_types = midcom_helper_reflector_tree::get_child_objects($object, true);
        if (!empty($child_types)) {
            $child_indent = $indent + 20;
            echo "{$prefix}<tbody class=\"children\">\n";
            foreach ($child_types as $type => $children) {
                if (   count($children) < 10
                    || isset($_GET['show_children'][$object->guid][$type])) {
                        foreach ($children as $child) {
                            $this->show_type($child, $child_indent, "{$prefix}    ", false);
                        }
                    } else {
                        echo "{$prefix}    <tr>\n";
                        echo "{$prefix}        <td class=\"label\" style=\"padding-left: {$child_indent}px\" colspan=\"5\"><a href=\"?show_children[{$object->guid}][{$type}]=1\">" . sprintf(midcom::get()->i18n->get_string('show %s %s children', 'midgard.admin.asgard'), count($children), midgard_admin_asgard_plugin::get_type_label($type)) . "</a></td>\n";
                        echo "{$prefix}    </tr>\n";
                    }
            }

            echo "{$prefix}</tbody>\n";
        }
        $shown[$object->guid] = true;
    }
}
