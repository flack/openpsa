<?php
/**
 * @package midgard.admin.asgard
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Undelete/purge interface
 *
 * @package midgard.admin.asgard
 */
class midgard_admin_asgard_handler_undelete extends midcom_baseclasses_components_handler
{
    var $type = '';

    /**
     * Simple default constructor.
     */
    public function __construct()
    {
        $this->_component = 'midgard.admin.asgard';
    }

    public function _on_initialize()
    {
        // Ensure we get the correct styles
        $_MIDCOM->style->prepend_component_styledir('midgard.admin.asgard');
        $_MIDCOM->skip_page_style = true;

        $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . '/jQuery/jquery.tablesorter.pack.js');
        $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . '/midgard.admin.asgard/jquery.batch_process.js');
        $this->add_stylesheet(MIDCOM_STATIC_URL . '/midgard.admin.asgard/tablewidget.css');

        $_MIDCOM->load_library('midcom.helper.datamanager2');
    }

    /**
     * Trash view
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    public function _handler_trash($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_admin_user();
        $_MIDCOM->cache->content->no_cache();

        $data['view_title'] = $this->_l10n->get('trash');
        $_MIDCOM->set_pagetitle($data['view_title']);

        $data['asgard_toolbar'] = new midcom_helper_toolbar();
        midgard_admin_asgard_plugin::get_common_toolbar($data);

        $data['types'] = array();
        foreach (midcom_connection::get_schema_types() as $type)
        {
            if (substr($type, 0, 2) == '__')
            {
                continue;
            }
            $qb = new midgard_query_builder($type);
            $qb->include_deleted();
            $qb->add_constraint('metadata.deleted', '=', true);
            $data['types'][$type] = $qb->count();
        }

        // Set the breadcrumb data
        $this->add_breadcrumb('__mfa/asgard/', $this->_l10n->get('midgard.admin.asgard'));
        $this->add_breadcrumb('__mfa/asgard/trash/', $this->_l10n->get('trash'));

        return true;
    }

    /**
     * Shows the loaded object in editor.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    public function _show_trash($handler_id, &$data)
    {
        midcom_show_style('midgard_admin_asgard_header');
        midcom_show_style('midgard_admin_asgard_middle');
        midcom_show_style('midgard_admin_asgard_trash');
        midcom_show_style('midgard_admin_asgard_footer');
    }

    /**
     * Trash view
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    public function _handler_trash_type($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_admin_user();
        $_MIDCOM->cache->content->no_cache();

        $this->type = $args[0];

        $data['view_title'] = midgard_admin_asgard_plugin::get_type_label($this->type);
        $_MIDCOM->set_pagetitle($data['view_title']);

        $data['asgard_toolbar'] = new midcom_helper_toolbar();

        midgard_admin_asgard_plugin::get_common_toolbar($data);

        $dummy = new $this->type;
        $data['midcom_dba_classname'] = $_MIDCOM->dbclassloader->get_midcom_class_name_for_mgdschema_object($dummy);
        $data['type'] = $this->type;
        $data['reflector'] = midcom_helper_reflector::get($data['type']);
        $data['label_property'] = $data['reflector']->get_label_property();

        if (   isset($_POST['undelete'])
            && !isset($_POST['purge'])
            && is_array($_POST['undelete']))
        {
            static $undeleted_size = 0;
            if (!$data['midcom_dba_classname'])
            {
                // No DBA class for the type, use plain Midgard undelete API
                foreach ($_POST['undelete'] as $guid)
                {
                    $qb = new midgard_query_builder($this->type);
                    $qb->add_constraint('guid', '=', $guid);
                    $qb->include_deleted();
                    $results = $qb->execute();
                    foreach ($results as $object)
                    {
                        $object->purge();
                    }
                }
            }
            else
            {
                // Delegate undeletion to DBA
                midcom_baseclasses_core_dbobject::undelete($_POST['undelete'], $this->type);
            }

            if ($undeleted_size > 0)
            {
                $_MIDCOM->uimessages->add($this->_l10n->get('midgard.admin.asgard'), sprintf($this->_l10n->get('in total %s undeleted'), midcom_helper_filesize_to_string($undeleted_size)), 'info');
            }
            $_MIDCOM->relocate("__mfa/asgard/trash/{$this->type}/");
        }

        if (   isset($_POST['purge'])
            && is_array($_POST['undelete']))
        {
            $purged_size = 0;

            if (!$data['midcom_dba_classname'])
            {
                // No DBA class for the type, use plain Midgard undelete API
                foreach ($_POST['undelete'] as $guid)
                {
                    $qb = new midgard_query_builder($this->type);
                    $qb->add_constraint('guid', '=', $guid);
                    $qb->include_deleted();
                    $results = $qb->execute();
                    foreach ($results as $object)
                    {
                        if ($object->purge())
                        {
                            $purged_size += $object->metadata->size;
                        }
                    }
                }
            }
            else
            {
                // Delegate purging to DBA
                $purged_size = midcom_baseclasses_core_dbobject::purge($_POST['undelete'], $this->type);
            }

            if ($purged_size)
            {
                $_MIDCOM->uimessages->add($this->_l10n->get('midgard.admin.asgard'), sprintf($this->_l10n->get('in total %s purged'), midcom_helper_filesize_to_string($purged_size)), 'info');
            }

            $_MIDCOM->relocate("__mfa/asgard/trash/{$this->type}/");
        }

        $_MIDCOM->load_library('org.openpsa.qbpager');
        $qb = new org_openpsa_qbpager_direct($data['type'], "{$data['type']}_trash");
        $qb->include_deleted();
        $qb->add_constraint('metadata.deleted', '=', true);
        $qb->add_order('metadata.revised', 'DESC');
        $data['qb'] =& $qb;
        $data['trash'] = $qb->execute_unchecked();

        // Set the breadcrumb data
        $this->add_breadcrumb('__mfa/asgard/', $this->_l10n->get('midgard.admin.asgard'));
        $this->add_breadcrumb("__mfa/asgard/{$this->type}/", $data['view_title']);
        $this->add_breadcrumb
        (
            "__mfa/asgard/trash/{$this->type}/",
            sprintf($this->_l10n->get('%s trash'), midgard_admin_asgard_plugin::get_type_label($data['type']))
        );

        return true;
    }

    /**
     * Shows the loaded object in editor.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    public function _show_trash_type($handler_id, &$data)
    {
        midcom_show_style('midgard_admin_asgard_header');
        $data['current_type'] = $this->type;
        midcom_show_style('midgard_admin_asgard_middle');

        midcom_show_style('midgard_admin_asgard_trash_type');
        midcom_show_style('midgard_admin_asgard_footer');
    }
}
?>