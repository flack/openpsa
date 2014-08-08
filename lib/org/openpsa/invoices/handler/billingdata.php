<?php
/**
 * @package org.openpsa.invoices
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Billing data handlers
 *
 * @package org.openpsa.invoices
 */
class org_openpsa_invoices_handler_billingdata extends midcom_baseclasses_components_handler
implements midcom_helper_datamanager2_interfaces_create
{
    /**
     * Contains the object the billing data is linked to
     *
     * @var object
     */
    private $_linked_object = null;

    /**
     * Contains the billing data object
     *
     * @var object
     */
    private $_billing_data = null;

    /**
     * Contains DM2 controller
     */
    private $_controller = null;

    public function load_schemadb()
    {
        $schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_billing_data'));

        $fields =& $schemadb[$this->get_schema_name()]->fields;
        // Fill VAT select
        $vat_array = explode(',', $this->_config->get('vat_percentages'));
        if (!empty($vat_array))
        {
            $vat_values = array();
            foreach ($vat_array as $vat)
            {
                $vat_values[$vat] = "{$vat}%";
            }
            $fields['vat']['type_config']['options'] = $vat_values;
        }

        $dummy_invoice = new org_openpsa_invoices_invoice_dba();
        //set the defaults for vat & due to the schema
        $fields['due']['default'] = $dummy_invoice->get_default('due');
        $fields['vat']['default'] = $dummy_invoice->get_default('vat');

        return $schemadb;
    }

    /**
     * Datamanager callback
     */
    public function & dm2_create_callback(&$datamanager)
    {
        $billing_data = new org_openpsa_invoices_billing_data_dba();
        $billing_data->linkGuid = $this->_linked_object->guid;
        if (! $billing_data->create())
        {
            debug_print_r('We operated on this object:', $billing_data);
            throw new midcom_error("Failed to create a new billing_data. Error: " . midcom_connection::get_error_string());
        }

        return $billing_data;
    }

    public function _handler_edit($handler_id, array $args, array &$data)
    {
        //get billing_data
        $this->_billing_data = new org_openpsa_invoices_billing_data_dba($args[0]);
        $this->_linked_object = midcom::get()->dbfactory->get_object_by_guid($this->_billing_data->linkGuid);

        $this->_controller = $this->get_controller('simple', $this->_billing_data);
        $this->_process_billing_form();

        $this->_prepare_output('edit');

        if ($this->_billing_data->can_do('midgard:delete'))
        {
            $toolbar = new org_openpsa_widgets_toolbar($this->_view_toolbar);
            $toolbar->add_delete_button("billingdata/delete/{$this->_billing_data->guid}/", $this->_l10n->get('billing data'));
        }

        $this->bind_view_to_object($this->_billing_data);
    }

    public function _show_edit($handler_id, array &$data)
    {
        midcom_show_style('show-billingdata');
    }

    public function _handler_create($handler_id, array $args, array &$data)
    {
        midcom::get()->auth->require_valid_user();

        try
        {
            $this->_linked_object = midcom::get()->dbfactory->get_object_by_guid($args[0]);
        }
        catch (midcom_error $e)
        {
            debug_print_r('Passed guid does not exist. GUID :', $args[0]);
        }

        $this->_controller = $this->get_controller('create');
        $this->_process_billing_form();

        $this->_prepare_output('create');
    }

    public function _show_create($handler_id, array &$data)
    {
        midcom_show_style('show-billingdata');
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_delete($handler_id, array $args, array &$data)
    {
        $this->_billing_data = new org_openpsa_invoices_billing_data_dba($args[0]);
        $this->_billing_data->require_do('midgard:delete');
        $this->_linked_object = midcom::get()->dbfactory->get_object_by_guid($this->_billing_data->linkGuid);

        $this->_controller = midcom_helper_datamanager2_handler::get_delete_controller();
        $this->_process_billing_form();
    }

    private function _prepare_output($mode)
    {
        midcom::get()->head->enable_jquery();
        midcom::get()->head->set_pagetitle(sprintf($this->_l10n_midcom->get($mode . " %s"), $this->_l10n->get("billing data")));

        $this->_update_breadcrumb();

        // Add toolbar items
        org_openpsa_helpers::dm2_savecancel($this);

        $this->_request_data['controller'] = $this->_controller;
    }

    /**
     * Helper function to process the form of the controller
     */
    private function _process_billing_form()
    {
        switch ($this->_controller->process_form())
        {
            case 'delete':
                $this->_billing_data->delete();
                midcom::get()->uimessages->add($this->_l10n->get($this->_component), sprintf($this->_l10n_midcom->get("%s deleted"), $this->_l10n->get('document')));

            case 'save':
            case 'cancel':
                $siteconfig = org_openpsa_core_siteconfig::get_instance();
                $relocate = $siteconfig->get_node_full_url('org.openpsa.contacts');
                switch (true)
                {
                    case is_a($this->_linked_object, 'org_openpsa_contacts_person_dba'):
                        $relocate .= 'person/' . $this->_linked_object->guid . '/';
                        break;
                    case is_a($this->_linked_object, 'org_openpsa_contacts_group_dba'):
                        $relocate .= 'group/' . $this->_linked_object->guid . '/';
                        break;
                    default:
                        $relocate = midcom::get()->permalinks->create_permalink($this->_linked_object->guid);
                        break;
                }
                midcom::get()->relocate($relocate);
                // This will exit.
        }
    }

    /**
     * Helper to update the breadcrumb
     */
    private function _update_breadcrumb()
    {
        $ref = midcom_helper_reflector::get($this->_linked_object);
        $object_label = $ref->get_object_label($this->_linked_object);

        $this->add_breadcrumb(midcom::get()->permalinks->create_permalink($this->_linked_object->guid), $object_label);
        $this->add_breadcrumb('', $this->_l10n->get('billing data') . " : " . $object_label);
    }
}
