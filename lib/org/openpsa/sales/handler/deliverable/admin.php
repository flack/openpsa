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
    private function _prepare_request_data()
    {
        $this->_request_data['deliverable'] = $this->_deliverable;
        $this->_request_data['controller'] = $this->_controller;

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
        $fields =& $this->_schemadb['subscription']->fields;

        $mc = new org_openpsa_relatedto_collector($this->_deliverable->guid, 'midcom_services_at_entry_dba');
        $mc->add_object_order('start', 'ASC');
        $mc->set_object_limit(1);
        $at_entries = $mc->get_related_objects();

        if (sizeof($at_entries) != 1)
        {
            if (   (   $this->_deliverable->continuous
                    || $this->_deliverable->end > time())
                && $this->_deliverable->state == org_openpsa_sales_salesproject_deliverable_dba::STATE_STARTED)
            {
                $fields['next_cycle']['hidden'] = false;
            }
            return;
        }
        $fields['next_cycle']['hidden'] = false;

        $entry = $at_entries[0];

        $fields['next_cycle']['default'] = array('next_cycle_date' => date('Y-m-d', $entry->start));
        $fields['at_entry']['default'] = $entry->id;
    }

    /**
     * Helper, updates the context so that we get a complete breadcrumb line towards the current
     * location.
     */
    private function _update_breadcrumb_line()
    {
        org_openpsa_sales_viewer::add_breadcrumb_path($this->_request_data['deliverable'], $this);
        $this->add_breadcrumb("deliverable/edit/{$this->_deliverable->guid}/", $this->_l10n_midcom->get('edit'));
    }

    /**
     * Displays a deliverable edit view.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
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
                $this->_process_at_entry($formdata);
                $this->_master->process_notify_date($formdata, $this->_deliverable);

                // Reindex the deliverable
                //$indexer = midcom::get()->indexer;
                //org_openpsa_sales_viewer::index($this->_controller->datamanager, $indexer, $this->_content_topic);

                // *** FALL-THROUGH ***

            case 'cancel':
                return new midcom_response_relocate("deliverable/{$this->_deliverable->guid}/");
        }

        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/' . $this->_component . '/sales.js');

        $this->_prepare_request_data($handler_id);
        $this->bind_view_to_object($this->_deliverable, $this->_request_data['controller']->datamanager->schema->name);
        $this->_update_breadcrumb_line();

        midcom::get()->head->set_pagetitle(sprintf($this->_l10n_midcom->get('edit %s'), $this->_deliverable->title));
    }

    private function _process_at_entry(array $formdata)
    {
        $entry = null;
        $next_cycle = 0;
        if (!empty($formdata['at_entry']->value))
        {
            $entry = new midcom_services_at_entry_dba((int) $formdata['at_entry']->value);
        }
        if (   isset($formdata['next_cycle'])
            && !$formdata['next_cycle']->is_empty())
        {
            $next_cycle = (int) $formdata['next_cycle']->value->format('U');
        }

        if (null !== $entry)
        {
            if ($next_cycle == 0)
            {
                $entry->delete();
                $this->_deliverable->end_subscription();
            }
            else if ($next_cycle != $entry->start)
            {
                //@todo If next_cycle is changed to be in the past, should we check if this would lead
                //to multiple runs immediately? i.e. if you set a monthly subscriptions next cycle to
                //one year in the past, this would trigger twelve consecutive runs and maybe
                //the user needs to be warned about that...

                $entry->start = $next_cycle;
                $entry->update();
            }
        }
        else if ($next_cycle > 0)
        {
            //TODO: This code is copied from scheduler, and should be merged into a separate method at some point
            $args = array
            (
                'deliverable' => $this->_deliverable->guid,
                'cycle'       => 2, //TODO: We might want to calculate the correct cycle number from start and unit at some point
            );
            $at_entry = new midcom_services_at_entry_dba();
            $at_entry->start = $next_cycle;
            $at_entry->component = $this->_component;
            $at_entry->method = 'new_subscription_cycle';
            $at_entry->arguments = $args;

            if (!$at_entry->create())
            {
                throw new midcom_error('AT registration failed, last midgard error was: ' . midcom_connection::get_error_string());
            }
            org_openpsa_relatedto_plugin::create($at_entry, 'midcom.services.at', $this->_deliverable, $this->_component);
        }
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
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_delete($handler_id, array $args, array &$data)
    {
        $deliverable = new org_openpsa_sales_salesproject_deliverable_dba($args[0]);
        $workflow = new midcom\workflow\delete($deliverable);
        $salesproject = $deliverable->get_parent();
        $workflow->success_url = "salesproject/{$salesproject->guid}/";
        return $workflow->run();
    }
}
