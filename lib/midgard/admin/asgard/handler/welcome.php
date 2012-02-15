<?php
/**
 * @package midgard.admin.asgard
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Welcome interface
 *
 * @package midgard.admin.asgard
 */
class midgard_admin_asgard_handler_welcome extends midcom_baseclasses_components_handler
{
    /**
     * Reflectors
     *
     * @var Array
     */
    private $_reflectors = array();

    /**
     * Startup routines
     */
    public function _on_initialize()
    {
        // Ensure we get the correct styles
        midcom::get('style')->prepend_component_styledir('midgard.admin.asgard');
        $_MIDCOM->skip_page_style = true;
    }

    /**
     * Simple helper which references all important members to the request data listing
     * for usage within the style listing.
     */
    private function _prepare_request_data()
    {
    }

    private function _list_revised($since, $review_by = null, $type = null, $only_mine = false)
    {
        $classes = array();
        $revised = array();
        $skip = $this->_config->get('skip_in_filter');

        // List installed MgdSchema types and convert to DBA classes
        foreach (midcom_connection::get_schema_types() as $schema_type)
        {
            if (in_array($schema_type, $skip))
            {
                // Skip
                continue;
            }

            if (   !is_null($type)
                && $schema_type != $type)
            {
                // Skip
                continue;
            }

            $mgdschema_class = midcom_helper_reflector::class_rewrite($schema_type);
            $dummy_object = new $mgdschema_class();
            $midcom_dba_classname = midcom::get('dbclassloader')->get_midcom_class_name_for_mgdschema_object($dummy_object);
            if (empty($midcom_dba_classname))
            {
                continue;
            }

            $classes[] = $midcom_dba_classname;
        }

        // List all revised objects
        foreach ($classes as $class)
        {
            if (!midcom::get('dbclassloader')->load_mgdschema_class_handler($class))
            {
                // Failed to load handling component, skip
                continue;
            }
            $qb_callback = array($class, 'new_query_builder');
            if (!is_callable($qb_callback))
            {
                continue;
            }
            $qb = call_user_func($qb_callback);

            if ($since != 'any')
            {
                $qb->add_constraint('metadata.revised', '>=', $since);
            }

            if (   $only_mine
                && midcom::get('auth')->user)
            {
                $qb->add_constraint('metadata.authors', 'LIKE', "|{midcom::get('auth')->user->guid}|");
            }

            $qb->add_order('metadata.revision', 'DESC');
            $objects = $qb->execute();

            if (count($objects) > 0)
            {
                if (!isset($this->_reflectors[$class]))
                {
                    $this->_reflectors[$class] = new midcom_helper_reflector($objects[0]);
                }
            }

            foreach ($objects as $object)
            {
                if (!is_null($review_by))
                {
                    $object_review_by = (int) $object->get_parameter('midcom.helper.metadata', 'review_date');
                    if ($object_review_by > $review_by)
                    {
                        // Skip
                        continue;
                    }
                }

                $revised["{$object->metadata->revised}_{$object->guid}_{$object->metadata->revision}"] = $object;
            }
        }

        krsort($revised);

        return $revised;
    }

    /**
     * Object editing view
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    public function _handler_welcome($handler_id, array $args, array &$data)
    {
        $this->_prepare_request_data();

        $data['view_title'] = $this->_l10n->get('asgard');
        midcom::get('head')->set_pagetitle($data['view_title']);

        if (isset($_POST['execute_mass_action']))
        {
            if (   isset($_POST['selections'])
                && !empty($_POST['selections'])
                && isset($_POST['mass_action']))
            {
                $method_name = "_mass_{$_POST['mass_action']}";
                $this->$method_name($_POST['selections']);
            }
        }

        $data['revised'] = array();
        if (isset($_REQUEST['revised_after']))
        {
            $data['revised_after'] = $_REQUEST['revised_after'];
            if ($data['revised_after'] != 'any')
            {
                $data['revised_after'] = date('Y-m-d H:i:s\Z', $_REQUEST['revised_after']);
            }

            $data['review_by'] = null;
            if (   $this->_config->get('enable_review_dates')
                && isset($_REQUEST['review_by'])
                && $_REQUEST['review_by'] != 'any')
            {
                $data['review_by'] = (int) $_REQUEST['review_by'];
            }

            $data['type_filter'] = null;
            if (   isset($_REQUEST['type_filter'])
                && $_REQUEST['type_filter'] != 'any')
            {
                $data['type_filter'] = $_REQUEST['type_filter'];
            }

            $data['only_mine'] = false;
            if (   isset($_REQUEST['only_mine'])
                && $_REQUEST['only_mine'] == 1)
            {
                $data['only_mine'] = $_REQUEST['only_mine'];
            }

            $data['revised'] = $this->_list_revised($data['revised_after'], $data['review_by'], $data['type_filter'], $data['only_mine']);
        }
        // else
        // {
        //     $data['revised_after'] = date('Y-m-d H:i:s\Z', mktime(0, 0, 0, date('m'), date('d') - 1, date('Y')));
        // }
        midcom::get('head')->add_jsfile(MIDCOM_STATIC_URL . '/jQuery/jquery.tablesorter.pack.js');
        midcom::get('head')->add_jsfile(MIDCOM_STATIC_URL . '/midgard.admin.asgard/jquery.batch_process.js');
        $this->add_stylesheet(MIDCOM_STATIC_URL . '/midgard.admin.asgard/tablewidget.css');

        $this->_populate_toolbar();
    }

    private function _populate_toolbar()
    {
        $this->_request_data['asgard_toolbar']->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => '__mfa/asgard/preferences/',
                MIDCOM_TOOLBAR_LABEL => midcom::get('i18n')->get_string('user preferences', 'midgard.admin.asgard'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/configuration.png',
            )
        );

        if (midcom::get('auth')->admin)
        {
            $this->_request_data['asgard_toolbar']->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => '__mfa/asgard/trash/',
                    MIDCOM_TOOLBAR_LABEL => midcom::get('i18n')->get_string('trash', 'midgard.admin.asgard'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/trash-full.png',
                )
            );
        }

        $this->_request_data['asgard_toolbar']->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => '__mfa/asgard/components/',
                MIDCOM_TOOLBAR_LABEL => midcom::get('i18n')->get_string('components', 'midgard.admin.asgard'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/component.png',
            )
        );

        // Add link to site
        $this->_request_data['asgard_toolbar']->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX),
                MIDCOM_TOOLBAR_LABEL => midcom::get('i18n')->get_string('back to site', 'midgard.admin.asgard'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/gohome.png',
            )
        );

        $this->_request_data['asgard_toolbar']->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX) . "midcom-logout-",
                MIDCOM_TOOLBAR_LABEL => midcom::get('i18n')->get_string('logout', 'midcom'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/exit.png',
            )
        );
    }

    private function _mass_delete($guids)
    {
        foreach ($guids as $guid)
        {
            try
            {
                $object =& midcom::get('dbfactory')->get_object_by_guid($guid);
            }
            catch (midcom_error $e)
            {
                continue;
            }

            if ($object->can_do('midgard:delete'))
            {
                $label = $object->guid;
                if ($object->delete())
                {
                    midcom::get('uimessages')->add($this->_l10n->get('midgard.admin.asgard'), sprintf($this->_l10n->get('object %s removed'), $label));
                }
            }
        }
    }

    private function _mass_approve($guids)
    {
        foreach ($guids as $guid)
        {
            try
            {
                $object =& midcom::get('dbfactory')->get_object_by_guid($guid);
            }
            catch (midcom_error $e)
            {
                continue;
            }

            if (   $object->can_do('midgard:update')
                && $object->can_do('midcom:approve'))
            {
                //$label = $object->get_label();
                $label = $object->guid;
                $metadata = $object->metadata;
                $metadata->approve();
                midcom::get('uimessages')->add($this->_l10n->get('midgard.admin.asgard'), sprintf($this->_l10n->get('object %s approved'), $label));
            }
        }
    }

    /**
     * Shows the loaded object in editor.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_welcome($handler_id, array &$data)
    {
        $data['reflectors'] = $this->_reflectors;
        $data['config'] = $this->_config;
        midcom_show_style('midgard_admin_asgard_header');
        midcom_show_style('midgard_admin_asgard_middle');

        if (midcom::get('auth')->can_user_do('midgard.admin.asgard:manage_objects', null, 'midgard_admin_asgard_plugin'))
        {
            midcom_show_style('midgard_admin_asgard_welcome');
        }
        midcom_show_style('midgard_admin_asgard_footer');
    }
}
?>