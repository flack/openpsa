<?php
/**
 * @package org.openpsa.sales
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Projects edit/delete deliverable handler
 *
 * Originally copied from net.nehmer.blog
 *
 * @package org.openpsa.sales
 */
class org_openpsa_sales_handler_deliverable_admin extends midcom_baseclasses_components_handler
{
    /**
     * The deliverable to operate on
     *
     * @var org_openpsa_sales_salesproject_deliverable_dba
     */
    private $_deliverable = null;

    /**
     * The Datamanager of the deliverable to display (for delete mode)
     *
     * @var midcom_helper_datamanager2_datamanager
     */
    private $_datamanager = null;

    /**
     * The Controller of the deliverable used for editing
     *
     * @var midcom_helper_datamanager2_controller_simple
     */
    private $_controller = null;

    /**
     * The schema database in use, available only while a datamanager is loaded.
     *
     * @var Array
     */
    private $_schemadb = null;

    /**
     * Schema to use for deliverable display
     *
     * @var string
     */
    private $_schema = null;

    /**
     * Simple helper which references all important members to the request data listing
     * for usage within the style listing.
     */
    private function _prepare_request_data($handler_id)
    {
        $this->_request_data['deliverable'] =& $this->_deliverable;
        $this->_request_data['datamanager'] =& $this->_datamanager;
        $this->_request_data['controller'] =& $this->_controller;

        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "deliverable/edit/{$this->_deliverable->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('edit'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/edit.png',
                MIDCOM_TOOLBAR_ENABLED => $this->_deliverable->can_do('midgard:update'),
                MIDCOM_TOOLBAR_ACCESSKEY => 'e',
            )
        );

        /*$this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "deliverable/delete/{$this->_deliverable->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('delete'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/trash.png',
                MIDCOM_TOOLBAR_ENABLED => $this->_deliverable->can_do('midgard:delete'),
                MIDCOM_TOOLBAR_ACCESSKEY => 'd',
            )
        );*/

        switch ($handler_id)
        {
            case 'deliverable_edit':
                $this->_view_toolbar->disable_item("deliverable/edit/{$this->_deliverable->guid}/");
                break;
            case 'deliverable_delete':
                $this->_view_toolbar->disable_item("deliverable/delete/{$this->_deliverable->guid}/");
                break;
        }
    }

    /**
     * Maps the content topic from the request data to local member variables.
     */
    public function _on_initialize()
    {
        $_MIDCOM->load_library('midcom.helper.datamanager2');

        $this->add_stylesheet(MIDCOM_STATIC_URL."/midcom.helper.datamanager2/legacy.css");
    }

    /**
     * Loads and prepares the schema database.
     *
     * The operations are done on all available schemas within the DB.
     */
    private function _load_schemadb()
    {
        $this->_schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_deliverable'));
    }

    /**
     * Internal helper, loads the datamanager for the current deliverable. Any error triggers a 500.
     */
    private function _load_datamanager()
    {
        $this->_load_schemadb();
        $this->_datamanager = new midcom_helper_datamanager2_datamanager($this->_schemadb);

        if (! $this->_datamanager->autoset_storage($this->_deliverable))
        {
            throw new midcom_error("Failed to create a DM2 instance for deliverable {$this->_deliverable->id}.");
        }
    }

    /**
     * Internal helper, loads the controller for the current deliverable. Any error triggers a 500.
     */
    private function _load_controller()
    {
        $this->_load_schemadb();
        $this->_modify_schema();
        $this->_controller = midcom_helper_datamanager2_controller::create('simple');
        $this->_controller->schemadb =& $this->_schemadb;
        $this->_controller->set_storage($this->_deliverable, $this->_schema);
        if (! $this->_controller->initialize())
        {
            throw new midcom_error("Failed to initialize a DM2 controller instance for deliverable {$this->_deliverable->id}.");
        }
    }

    /**
     * Helper function to alter the schema based on the current operation
     */
    private function _modify_schema()
    {
        $_MIDCOM->load_library('midcom.services.at');

        $mc = new org_openpsa_relatedto_collector($this->_deliverable->guid, 'midcom_services_at_entry_dba');
        $mc->add_object_order('start', 'ASC');
        $mc->set_object_limit(1);
        $at_entries = $mc->get_related_objects();

        if (sizeof($at_entries) != 1)
        {
            return;
        }

        $entry = $at_entries[0];
        $fields =& $this->_schemadb['subscription']->fields;

        $fields['next_cycle']['hidden'] = false;
        $fields['next_cycle']['default'] = date('Y-m-d', $entry->start);
        $fields['at_entry']['default'] = $entry->id;
    }

    /**
     * Helper, updates the context so that we get a complete breadcrumb line towards the current
     * location.
     *
     * @param string $handler_id
     */
    private function _update_breadcrumb_line($handler_id)
    {
        org_openpsa_sales_viewer::add_breadcrumb_path($this->_request_data['deliverable'], $this);

        switch ($handler_id)
        {
            case 'deliverable_edit':
                $this->add_breadcrumb("deliverable/edit/{$this->_deliverable->guid}/", $this->_l10n_midcom->get('edit'));
                break;
            case 'deliverable_delete':
                $this->add_breadcrumb("deliverable/delete/{$this->_deliverable->guid}/", $this->_l10n_midcom->get('delete'));
                break;
        }
    }

    /**
     * Displays a deliverable edit view.
     *
     * Note, that the deliverable for non-index mode operation is automatically determined in the can_handle
     * phase.
     *
     * If create privileges apply, we relocate to the index creation deliverable
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_edit($handler_id, $args, &$data)
    {
        $this->_deliverable = $this->load_object('org_openpsa_sales_salesproject_deliverable_dba', $args[0]);
        $this->_deliverable->require_do('midgard:update');

        $this->_load_controller();

        switch ($this->_controller->process_form())
        {
            case 'save':
                $formdata = $this->_controller->datamanager->types;
                $entry = isset($formdata['at_entry']) ? (int) $formdata['at_entry']->value : 0;
                $next_cycle = isset($formdata['next_cycle']) ? $formdata['next_cycle']->value->getTime() : 0;
                if (   $entry != 0
                    && $next_cycle > time())
                {
                    $entry = new midcom_services_at_entry_dba($entry);

                    if ($next_cycle != $entry->start)
                    {
                        $entry->start = $next_cycle;
                        $entry->update();
                    }
                }
                $this->_process_notify_date($formdata);

                // Reindex the deliverable
                //$indexer = $_MIDCOM->get_service('indexer');
                //org_openpsa_sales_viewer::index($this->_controller->datamanager, $indexer, $this->_content_topic);

                // *** FALL-THROUGH ***

            case 'cancel':
                $_MIDCOM->relocate("deliverable/{$this->_deliverable->guid}/");
                // This will exit.
        }

        // Add toolbar items
        org_openpsa_helpers::dm2_savecancel($this);

        $this->_prepare_request_data($handler_id);
        $_MIDCOM->set_pagetitle($this->_deliverable->title);
        $_MIDCOM->bind_view_to_object($this->_deliverable, $this->_request_data['controller']->datamanager->schema->name);
        $this->_update_breadcrumb_line($handler_id);

        $_MIDCOM->set_pagetitle(sprintf($this->_l10n_midcom->get('edit %s'), $this->_deliverable->title));
    }


    /**
     * Shows the loaded deliverable.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    public function _show_edit ($handler_id, &$data)
    {
        midcom_show_style('show-deliverable-edit');
    }

    /**
     * Displays a deliverable delete confirmation view.
     *
     * Note, that the deliverable for non-index mode operation is automatically determined in the can_handle
     * phase.
     *
     * If create privileges apply, we relocate to the index creation deliverable
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_delete($handler_id, $args, &$data)
    {
        $this->_deliverable = $this->load_object('org_openpsa_sales_salesproject_deliverable_dba', $args[0]);
        $this->_deliverable->require_do('midgard:delete');

        $this->_load_datamanager();

        if (array_key_exists('org_openpsa_sales_deleteok', $_REQUEST))
        {
            // Deletion confirmed.
            if (! $this->_deliverable->delete())
            {
                throw new midcom_error("Failed to delete deliverable {$args[0]}, last Midgard error was: " . midcom_connection::get_error_string());
            }

            // Update the index
            $indexer = $_MIDCOM->get_service('indexer');
            $indexer->delete($this->_deliverable->guid);

            // Delete ok, relocating to welcome.
            $_MIDCOM->relocate('');
            // This will exit.
        }

        if (array_key_exists('org_openpsa_sales_deletecancel', $_REQUEST))
        {
            // Redirect to view page.
            $_MIDCOM->relocate("deliverable/{$this->_deliverable->guid}/");
            // This will exit()
        }

        $this->_prepare_request_data($handler_id);
        $_MIDCOM->set_pagetitle($this->_deliverable->title);
        $_MIDCOM->bind_view_to_object($this->_deliverable, $this->_request_data['controller']->datamanager->schema->title);
        $this->_update_breadcrumb_line($handler_id);
    }


    /**
     * Shows the loaded deliverable.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    public function _show_delete ($handler_id, &$data)
    {
        $data['deliverable_view'] = $this->_datamanager->get_content_html();

        midcom_show_style('show-deliverable-delete');
    }
    /**
     * function to process the notify date in the passed formdata of the datamanger
     * creates/edits/deletes the corresponding at_entry if needed
     *
     * @param object $formdata The Formdata of the datamanager containing the notify_date
     */
    private function _process_notify_date($formdata)
    {
        //get the time of passed notify date
        $unix_time = mktime($formdata['notify']->value->hour, $formdata['notify']->value->minute, 1, $formdata['notify']->value->month, $formdata['notify']->value->day, $formdata['notify']->value->year);

        //check if there is already an at_entry
        $mc_entry = org_openpsa_relatedto_dba::new_collector('toGuid', $this->_deliverable->guid);
        $mc_entry->set_key_property('guid');
        $mc_entry->add_value_property('fromGuid');
        $mc_entry->add_constraint('fromClass', '=', 'midcom_services_at_entry_dba');
        $mc_entry->add_constraint('toClass', '=', 'org_openpsa_sales_salesproject_deliverable_dba');
        $mc_entry->add_constraint('toExtra', '=', 'notify_at_entry');
        $mc_entry->execute();
        $entry_keys = $mc_entry->list_keys();
        //check date
        if ($formdata['notify']->value->year != '0000' )//&& $unix_time > time())
        {
            $notification_entry = null;

            if (count($entry_keys) == 0)
            {
                $notification_entry = new midcom_services_at_entry_dba();
                $notification_entry->create();
                //relatedto from notifcation to deliverable
                org_openpsa_relatedto_plugin::create($notification_entry, 'midcom.services.at', $this->_deliverable, 'org.openpsa.sales', false, array('toExtra' => 'notify_at_entry'));
            }
            else
            {
                //get guid of at_entry
                foreach ($entry_keys as $key => $empty)
                {
                    $notification_entry = new midcom_services_at_entry_dba($mc_entry->get_subkey($key, 'fromGuid'));
                    //check if related at_entry exists
                    if (empty($notification_entry->guid))
                    {
                        //relatedto links to a non-existing at_entry - so create a new one an link to it
                        $notification_entry = new midcom_services_at_entry_dba();
                        $notification_entry->create();
                        $relatedto = new org_openpsa_relatedto_dba($key);
                        $relatedto->fromGuid = $notification_entry->guid;
                        $relatedto->update();
                    }
                    break;
                }
            }
            $notification_entry->start = $unix_time;
            $notification_entry->method = 'new_notification_message';
            $notification_entry->component = 'org.openpsa.sales';
            $notification_entry->arguments = array('deliverable' => $this->_deliverable->guid);
            $notification_entry->update();
        }
        else if ($formdata['notify']->value->year == '0000')
        {
            //void date - so delete existing at_entrys for this notify_date
            foreach($entry_keys as $key => $empty)
            {
                $notification_entry = new midcom_services_at_entry_dba($mc_entry->get_subkey($key, 'fromGuid'));
                //check if related at_entry exists & delete it
                if (!empty($notification_entry->guid))
                {
                    $notification_entry->delete();
                }
            }
        }
    }
}
?>