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
    private $type = '';

    public function _on_initialize()
    {
        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/jQuery/jquery.tablesorter.pack.js');
        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/midgard.admin.asgard/jquery.batch_process.js');
        $this->add_stylesheet(MIDCOM_STATIC_URL . '/midgard.admin.asgard/tablewidget.css');
    }

    /**
     * Trash view
     *
     * @param array &$data The local request data.
     */
    public function _handler_trash(array &$data)
    {
        midcom::get()->auth->require_admin_user();

        $data['view_title'] = $this->_l10n->get('trash');

        $data['types'] = [];
        foreach (midcom_connection::get_schema_types() as $type) {
            if (substr($type, 0, 2) == '__') {
                continue;
            }

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
        return new midgard_admin_asgard_response($this, '_show_trash');
    }

    /**
     * Shows the loaded object in editor.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_trash($handler_id, array &$data)
    {
        midcom_show_style('midgard_admin_asgard_trash');
    }

    /**
     * Trash view
     *
     * @param Request $request The request object
     * @param string $type The MgdSchema type
     * @param array &$data The local request data.
     */
    public function _handler_trash_type(Request $request, $type, array &$data)
    {
        midcom::get()->auth->require_admin_user();

        $this->type = $type;

        $data['view_title'] = midgard_admin_asgard_plugin::get_type_label($type);

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
        $this->add_breadcrumb($this->router->generate('trash_type', ['type' => $type]), $data['view_title']);
        $this->add_breadcrumb(
            "",
            sprintf($this->_l10n->get('%s trash'), midgard_admin_asgard_plugin::get_type_label($type))
        );
        return new midgard_admin_asgard_response($this, '_show_trash_type');
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

    /**
     * Shows the loaded object in editor.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_trash_type($handler_id, array &$data)
    {
        $data['current_type'] = $this->type;

        midcom_show_style('midgard_admin_asgard_trash_type');
    }
}
