<?php
/**
 * @package org.openpsa.invoices
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Doctrine\ORM\Query\Expr\Join;
use midcom\datamanager\schemadb;
use midcom\datamanager\schema;
use midcom\datamanager\datamanager;
use midcom\datamanager\controller;

/**
 * Invoice create/update/delete handler
 *
 * @package org.openpsa.invoices
 */
class org_openpsa_invoices_handler_invoice_crud extends midcom_baseclasses_components_handler
{
    /**
     * @var org_openpsa_invoices_invoice_dba
     */
    private $invoice;

    /**
     * @var org_openpsa_contacts_group_dba
     */
    private $customer;

    /**
     * @var int
     */
    private $contact_id;

    /**
     * Generates an object creation view.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_create($handler_id, array $args, array &$data)
    {
        $this->mode = 'create';
        midcom::get()->auth->require_user_do('midgard:create', null, 'org_openpsa_invoices_invoice_dba');

        if (count($args) == 1) {
            // We're creating invoice for chosen customer
            try {
                $this->customer = new org_openpsa_contacts_group_dba($args[0]);
            } catch (midcom_error $e) {
                $contact = new org_openpsa_contacts_person_dba($args[0]);
                $this->contact_id = $contact->id;
            }
        }
        $this->invoice = new org_openpsa_invoices_invoice_dba();

        midcom::get()->head->set_pagetitle($this->_l10n->get('create invoice'));
        $workflow = $this->get_workflow('datamanager', [
            'controller' => $this->load_controller(),
            'save_callback' => [$this, 'save_callback']
        ]);
        return $workflow->run();
    }

    /**
     * Generates an object update view.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_update($handler_id, array $args, array &$data)
    {
        $this->invoice = new org_openpsa_invoices_invoice_dba($args[0]);
        $this->invoice->require_do('midgard:update');

        midcom::get()->head->set_pagetitle(sprintf($this->_l10n_midcom->get('edit %s'), $this->_l10n->get('invoice')));
        $workflow = $this->get_workflow('datamanager', [
            'controller' => $this->load_controller(),
            'save_callback' => [$this, 'save_callback']
        ]);
        return $workflow->run();
    }

    public function save_callback(controller $controller)
    {
        $indexer = new org_openpsa_invoices_midcom_indexer($this->_topic);
        $indexer->index($controller->get_datamanager());

        if ($this->mode === 'create') {
            return 'invoice/' . $this->invoice->guid . '/';
        }
    }

    /**
     * Displays an object delete confirmation view.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_delete($handler_id, array $args, array &$data)
    {
        $this->invoice = new org_openpsa_invoices_invoice_dba($args[0]);

        $workflow = $this->get_workflow('delete', ['object' => $this->invoice]);
        return $workflow->run();
    }

    private function load_controller()
    {
        $schemadb = schemadb::from_path($this->_config->get('schemadb'));
        $this->modify_schema($schemadb->get('default'));

        $defaults = ($this->invoice->id) ? [] : $this->load_defaults();
        $dm = new datamanager($schemadb);

        return $dm
            ->set_defaults($defaults)
            ->set_storage($this->invoice)
            ->get_controller();
    }

    /**
     * Alter the schema based on the current operation
     */
    private function modify_schema(schema $schema)
    {
        $vat_field =& $schema->get_field('vat');
        $pdf_field =& $schema->get_field('pdf_file');
        $due_field =& $schema->get_field('due');
        $sent_field =& $schema->get_field('sent');
        $paid_field =& $schema->get_field('paid');
        $customer_field =& $schema->get_field('customer');
        $contact_field =& $schema->get_field('customerContact');

        // Fill VAT select
        $vat_array = explode(',', $this->_config->get('vat_percentages'));
        if (!empty($vat_array)) {
            $vat_values = [];
            foreach ($vat_array as $vat) {
                $vat_values[$vat] = "{$vat}%";
            }
            $vat_field['type_config']['options'] = $vat_values;
        }

        if ($this->_config->get('invoice_pdfbuilder_class')) {
            $pdf_field['hidden'] = false;
        }
        $due_field['hidden'] = empty($this->invoice->sent);
        $sent_field['hidden'] = empty($this->invoice->sent);
        $paid_field['hidden'] = empty($this->invoice->paid);

        $contact = $this->invoice->customerContact ? $this->invoice->customerContact : $this->contact_id;
        if (!empty($contact)) {
            $customer_field['type_config']['options'] = $this->get_customers_for_contact($contact);
        } else {
            if (!empty($this->invoice->customer)) {
                try {
                    $this->customer = org_openpsa_contacts_group_dba::get_cached($this->invoice->customer);
                } catch (midcom_error $e) {
                    $customer_field['hidden'] = true;
                    $e->log();
                }
            }
            if ($this->customer) {
                $this->populate_schema_for_customer($customer_field, $contact_field);
            } else {
                // We don't know company, present customer contact as chooser and hide customer field
                $customer_field['hidden'] = true;
            }
        }
    }

    /**
     * List customer contact's groups
     *
     * @return array
     */
    private function get_customers_for_contact($contact_id)
    {
        $organizations = [0 => ''];

        $qb = org_openpsa_contacts_group_dba::new_query_builder();
        $qb->get_doctrine()
            ->leftJoin('org_openpsa_member', 'm', Join::WITH, 'm.gid = c.id')
            ->where('m.uid = :uid')
            ->setParameter('uid', $contact_id);

        $qb->add_constraint('orgOpenpsaObtype', '>', org_openpsa_contacts_group_dba::MYCONTACTS);
        $qb->add_order('official');
        $qb->add_order('name');
        foreach ($qb->execute() as $group) {
            $organizations[$group->id] = $group->official;
        }
        return $organizations;
    }

    private function populate_schema_for_customer(array &$customer_field, array &$contact_field)
    {
        // We know the customer company, present contact as a select widget
        $persons_array = [];
        $qb = org_openpsa_contacts_person_dba::new_query_builder();
        $qb->get_doctrine()
            ->leftJoin('midgard_member', 'm', Join::WITH, 'm.uid = c.id')
            ->where('m.gid = :gid')
            ->setParameter('gid', $this->customer->id);

        foreach ($qb->execute() as $person) {
            $persons_array[$person->id] = $person->rname;
        }
        asort($persons_array);

        $contact_field['widget'] = 'select';
        $contact_field['type_config']['options'] = $persons_array;

        $customer_field['widget'] = 'select';
        $customer_field['type_config']['options'] = [
            $this->customer->id => $this->customer->official
        ];
    }

    private function load_defaults()
    {
        $defaults = [
            'date' => time(),
            'deliverydate' => time(),
            'owner' => midcom_connection::get_user()
        ];

        $dummy = new org_openpsa_invoices_invoice_dba();
        if ($this->customer) {
            $dummy->customer = $this->customer->id;
            $defaults['customer'] = $this->customer->id;
        } else if ($this->contact_id) {
            $dummy->customerContact = $this->contact_id;
            $defaults['customerContact'] = $this->contact_id;
        }
        $defaults['description'] = $dummy->get_default('remarks');
        $defaults['vat'] = $dummy->get_default('vat');

        // Generate invoice number
        $client_class = midcom_baseclasses_components_configuration::get('org.openpsa.sales', 'config')->get('calculator');
        $calculator = new $client_class;
        $defaults['number'] = $calculator->generate_invoice_number();
        return $defaults;
    }
}
