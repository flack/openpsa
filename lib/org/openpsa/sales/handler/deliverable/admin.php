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
        $fields['next_cycle']['default'] = array('next_cycle_date' => date('Y-m-d', $entry->start));
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
    public function _handler_edit($handler_id, array $args, array &$data)
    {
        $this->_deliverable = new org_openpsa_sales_salesproject_deliverable_dba($args[0]);
        $this->_deliverable->require_do('midgard:update');

        $this->_load_controller();

        switch ($this->_controller->process_form())
        {
            case 'save':
                $formdata = $this->_controller->datamanager->types;

                if (!empty($formdata['at_entry']->value))
                {
                    $entry = new midcom_services_at_entry_dba((int) $formdata['at_entry']->value);

                    $next_cycle = 0;
                    if (   isset($formdata['next_cycle'])
                        && !$formdata['next_cycle']->is_empty())
                    {
                        $next_cycle =  (int) $formdata['next_cycle']->value->format('U');
                    }

                    //@todo If next_cycle is changed to be in the past, should we check if this would lead
                    //to multiple runs immediately? i.e. if you set a monthly subscriptions next cycle to
                    //one year in the past, this would trigger twelve consecutive runs and maybe
                    //the user needs to be warned about that...
                    if ($next_cycle != $entry->start)
                    {
                        $entry->start = $next_cycle;
                        $entry->update();
                    }
                }
                $this->_master->process_notify_date($formdata, $this->_deliverable);

                // Reindex the deliverable
                //$indexer = midcom::get('indexer');
                //org_openpsa_sales_viewer::index($this->_controller->datamanager, $indexer, $this->_content_topic);

                // *** FALL-THROUGH ***

            case 'cancel':
                midcom::get()->relocate("deliverable/{$this->_deliverable->guid}/");
                // This will exit.
        }

        // Add toolbar items
        org_openpsa_helpers::dm2_savecancel($this);

        $this->_prepare_request_data($handler_id);
        $this->bind_view_to_object($this->_deliverable, $this->_request_data['controller']->datamanager->schema->name);
        $this->_update_breadcrumb_line($handler_id);

        midcom::get('head')->set_pagetitle(sprintf($this->_l10n_midcom->get('edit %s'), $this->_deliverable->title));
    }


    /**
     * Shows the loaded deliverable.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_edit ($handler_id, array &$data)
    {
        midcom_show_style('show-deliverable-form');
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
    public function _handler_delete($handler_id, array $args, array &$data)
    {
        $this->_deliverable = new org_openpsa_sales_salesproject_deliverable_dba($args[0]);
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
            $indexer = midcom::get('indexer');
            $indexer->delete($this->_deliverable->guid);

            // Delete ok, relocating to welcome.
            midcom::get()->relocate('');
            // This will exit.
        }

        if (array_key_exists('org_openpsa_sales_deletecancel', $_REQUEST))
        {
            // Redirect to view page.
            midcom::get()->relocate("deliverable/{$this->_deliverable->guid}/");
            // This will exit()
        }

        $this->_prepare_request_data($handler_id);
        midcom::get('head')->set_pagetitle($this->_deliverable->title);
        $this->bind_view_to_object($this->_deliverable, $this->_request_data['controller']->datamanager->schema->title);
        $this->_update_breadcrumb_line($handler_id);
    }


    /**
     * Shows the loaded deliverable.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_delete ($handler_id, array &$data)
    {
        $data['deliverable_view'] = $this->_datamanager->get_content_html();

        midcom_show_style('show-deliverable-delete');
    }
}
?>