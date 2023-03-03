<?php
/**
 * @package org.openpsa.invoices
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\schemadb;
use midcom\datamanager\datamanager;
use Symfony\Component\HttpFoundation\Request;
use midcom\datamanager\controller;

/**
 * Billing data handlers
 *
 * @package org.openpsa.invoices
 */
class org_openpsa_invoices_handler_billingdata extends midcom_baseclasses_components_handler
{
    use org_openpsa_invoices_handler;

    private midcom_core_dbaobject $_linked_object;

    private function load_controller(org_openpsa_invoices_billing_data_dba $bd) : controller
    {
        $schemadb = schemadb::from_path($this->_config->get('schemadb_billing_data'));
        $vat =& $schemadb->get('default')->get_field('vat');
        // Fill VAT select
        if ($options = $this->get_vat_options($this->_config->get('vat_percentages'))) {
            $vat['type_config']['options'] = $options;
        }

        $dummy_invoice = new org_openpsa_invoices_invoice_dba();
        //set the defaults for vat & due to the schema
        $schemadb->get('default')->get_field('due')['default'] = $dummy_invoice->get_default('due');
        $vat['default'] = $dummy_invoice->get_default('vat');

        $dm = new datamanager($schemadb);
        return $dm
            ->set_storage($bd)
            ->get_controller();
    }

    public function _handler_edit(Request $request, string $guid, array &$data)
    {
        midcom::get()->auth->require_valid_user();

        $this->_linked_object = midcom::get()->dbfactory->get_object_by_guid($guid);

        $qb_billing_data = org_openpsa_invoices_billing_data_dba::new_query_builder();
        $qb_billing_data->add_constraint('linkGuid', '=', $guid);
        $billing_data = $qb_billing_data->execute();
        if (!empty($billing_data)) {
            $mode = 'edit';
            $bd = $billing_data[0];
        } else {
            $mode = 'create';
            $bd = new org_openpsa_invoices_billing_data_dba;
            $bd->linkGuid = $guid;
        }

        $data['controller'] = $this->load_controller($bd);

        $workflow = $this->get_workflow('datamanager', ['controller' => $data['controller']]);
        if (   $mode == 'edit'
            && $bd->can_do('midgard:delete')) {
            $delete = $this->get_workflow('delete', [
                'object' => $bd,
                'label' => $this->_l10n->get('billing data')
            ]);
            $workflow->add_dialog_button($delete, $this->router->generate('billing_data_delete', ['guid' => $bd->guid]));
        }

        midcom::get()->head->set_pagetitle(sprintf($this->_l10n_midcom->get($mode . ' %s'), $this->_l10n->get('billing data')));
        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/' . $this->_component . '/invoices.js');

        return $workflow->run($request);
    }

    public function _handler_delete(Request $request, string $guid)
    {
        $billing_data = new org_openpsa_invoices_billing_data_dba($guid);
        $this->_linked_object = midcom::get()->dbfactory->get_object_by_guid($billing_data->linkGuid);

        $workflow = $this->get_workflow('delete', [
            'object' => $billing_data,
            'success_url' => $this->get_relocate_url()
        ]);
        return $workflow->run($request);
    }

    private function get_relocate_url() : string
    {
        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        $relocate = $siteconfig->get_node_full_url('org.openpsa.contacts');
        switch (true) {
            case $this->_linked_object instanceof org_openpsa_contacts_person_dba:
                $relocate .= 'person/' . $this->_linked_object->guid . '/';
                break;
            case $this->_linked_object instanceof org_openpsa_contacts_group_dba:
                $relocate .= 'group/' . $this->_linked_object->guid . '/';
                break;
            default:
                $relocate = midcom::get()->permalinks->create_permalink($this->_linked_object->guid);
                break;
        }
        return $relocate;
    }
}
