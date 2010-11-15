<?php
/**
 * @package midgard.admin.asgard
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: undelete.php 23025 2009-07-28 10:03:50Z flack $
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

    var $_undeleted_size = 0;

    /**
     * Simple default constructor.
     */
    function __construct()
    {
        $this->_component = 'midgard.admin.asgard';
        parent::__construct();
    }

    function _on_initialize()
    {
        // Ensure we get the correct styles
        $_MIDCOM->style->prepend_component_styledir('midgard.admin.asgard');
        $_MIDCOM->skip_page_style = true;

        $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . '/jQuery/jquery.tablesorter.pack.js');
        $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . '/midgard.admin.asgard/jquery.batch_process.js');
        $_MIDCOM->add_link_head
        (
            array
            (
                'rel' => 'stylesheet',
                'type' => 'text/css',
                'href' => MIDCOM_STATIC_URL . '/midgard.admin.asgard/tablewidget.css',
            )
        );

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
    function _handler_trash($handler_id, $args, &$data)
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
        $tmp = array();
        $tmp[] = array
        (
            MIDCOM_NAV_URL => '__mfa/asgard/',
            MIDCOM_NAV_NAME => $_MIDCOM->i18n->get_string('midgard.admin.asgard', 'midgard.admin.asgard'),
        );
        $tmp[] = array
        (
            MIDCOM_NAV_URL => '__mfa/asgard/trash/',
            MIDCOM_NAV_NAME => $_MIDCOM->i18n->get_string('trash', 'midgard.admin.asgard'),
        );
        $_MIDCOM->set_custom_context_data('midcom.helper.nav.breadcrumb', $tmp);

        return true;
    }

    /**
     * Shows the loaded object in editor.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    function _show_trash($handler_id, &$data)
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
    function _handler_trash_type($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_admin_user();
        $_MIDCOM->cache->content->no_cache();

        $this->type = $args[0];
        $root_types = midcom_helper_reflector_tree::get_root_classes();

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
        $tmp = array();
        $tmp[] = array
        (
            MIDCOM_NAV_URL => '__mfa/asgard/',
            MIDCOM_NAV_NAME => $_MIDCOM->i18n->get_string('midgard.admin.asgard', 'midgard.admin.asgard'),
        );
        $tmp[] = array
        (
            MIDCOM_NAV_URL => "__mfa/asgard/{$this->type}/",
            MIDCOM_NAV_NAME => $data['view_title'],
        );
        $tmp[] = array
        (
            MIDCOM_NAV_URL => "__mfa/asgard/trash/{$this->type}/",
            MIDCOM_NAV_NAME => sprintf($_MIDCOM->i18n->get_string('%s trash', 'midgard.admin.asgard'), midgard_admin_asgard_plugin::get_type_label($data['type'])),
        );
        $_MIDCOM->set_custom_context_data('midcom.helper.nav.breadcrumb', $tmp);

        return true;
    }

    /**
     * Shows the loaded object in editor.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    function _show_trash_type($handler_id, &$data)
    {
        midcom_show_style('midgard_admin_asgard_header');
        $data['current_type'] = $this->type;
        midcom_show_style('midgard_admin_asgard_middle');

        midcom_show_style('midgard_admin_asgard_trash_type');
        midcom_show_style('midgard_admin_asgard_footer');
    }
}
?>