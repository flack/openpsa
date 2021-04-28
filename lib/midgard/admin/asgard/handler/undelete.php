<?php
/**
 * @package midgard.admin.asgard
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Symfony\Component\HttpFoundation\Request;
use midgard\portable\api\mgdobject;
use midcom\dba\softdelete;

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

        $data['type'] = $type;

        if ($guids = $request->request->get('undelete')) {
            return $this->process_request($guids, $request->request->has('purge'));
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

    private function process_request(array $guids, bool $purge) : midcom_response_relocate
    {
        if ($purge) {
            $size = softdelete::purge($guids, $this->type);
            $message = 'in total %s purged';
        } else {
            $size = softdelete::undelete($guids);
            $message = 'in total %s undeleted';
        }
        midcom::get()->uimessages->add($this->_l10n->get($this->_component), sprintf($this->_l10n->get($message), midcom_helper_misc::filesize_to_string($size)), 'info');
        return new midcom_response_relocate($this->router->generate('trash_type', ['type' => $this->_request_data['type']]));
    }

    public function show_type(mgdobject $object, int $indent = 0, string $prefix = '', bool $enable_undelete = true)
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
        echo "{$prefix}    <td class=\"label\" style=\"padding-left: {$indent}px\"><a href=\"" . $this->router->generate('object_deleted', ['guid' =>$object->guid]) . "\">{$icon} " . $object_label . "</a></td>\n";
        echo "{$prefix}    <td class=\"nowrap\">" . strftime('%x %X', strtotime($object->metadata->revised)) . "</td>\n";

        if (!empty($revisor->guid)) {
            echo "{$prefix}    <td><a href=\"{$url_prefix}__mfa/asgard/object/view/{$revisor->guid}/\">{$revisor->name}</a></td>\n";
        } else {
            echo "{$prefix}    <td>&nbsp;</td>\n";
        }
        echo "{$prefix}    <td>" . midcom_helper_misc::filesize_to_string($object->metadata->size) . "</td>\n";
        echo "{$prefix}</tr>\n";

        if ($child_types = midcom_helper_reflector_tree::get_child_objects($object, true)) {
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
                        echo "{$prefix}        <td class=\"label\" style=\"padding-left: {$child_indent}px\" colspan=\"5\"><a href=\"?show_children[{$object->guid}][{$type}]=1\">" . sprintf($this->_i18n->get_string('show %s %s children', 'midgard.admin.asgard'), count($children), midgard_admin_asgard_plugin::get_type_label($type)) . "</a></td>\n";
                        echo "{$prefix}    </tr>\n";
                    }
            }

            echo "{$prefix}</tbody>\n";
        }
        $shown[$object->guid] = true;
    }
}
