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
        midcom::get()->auth->require_valid_user();

        $this->_linked_object = midcom::get()->dbfactory->get_object_by_guid($args[0]);

        $qb_billing_data = org_openpsa_invoices_billing_data_dba::new_query_builder();
        $qb_billing_data->add_constraint('linkGuid', '=', $this->_linked_object->guid);
        $billing_data = $qb_billing_data->execute();
        if (count($billing_data) > 0)
        {
            $mode = 'edit';
            $data['controller'] = $this->get_controller('simple', $billing_data[0]);
        }
        else
        {
            $mode = 'create';
            $data['controller'] = $this->get_controller('create');
        }

        $workflow = $this->get_workflow('datamanager2', array('controller' => $data['controller']));
        if (   $mode == 'edit'
            && $billing_data[0]->can_do('midgard:delete'))
        {
            $delete = $this->get_workflow('delete', array
            (
                'object' => $billing_data[0],
                'label' => $this->_l10n->get('billing data')
            ));
            $workflow->add_dialog_button($delete, "billingdata/delete/{$billing_data[0]->guid}/");
        }

        midcom::get()->head->set_pagetitle(sprintf($this->_l10n_midcom->get($mode . ' %s'), $this->_l10n->get('billing data')));
        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/' . $this->_component . '/invoices.js');

        return $workflow->run();
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_delete($handler_id, array $args, array &$data)
    {
        $billing_data = new org_openpsa_invoices_billing_data_dba($args[0]);
        $billing_data->require_do('midgard:delete');
        $this->_linked_object = midcom::get()->dbfactory->get_object_by_guid($billing_data->linkGuid);

        $workflow = $this->get_workflow('delete', array
        (
            'object' => $billing_data,
            'success_url' => $this->get_relocate_url()
        ));
        return $workflow->run();
    }

    private function get_relocate_url()
    {
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
        return $relocate;
    }
}
