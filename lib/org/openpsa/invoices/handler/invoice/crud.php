<?php
/**
 * @package org.openpsa.invoices
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Invoice create/read/update/delete handler
 *
 * @package org.openpsa.invoices
 */
class org_openpsa_invoices_handler_invoice_crud extends midcom_baseclasses_components_handler_crud
{
    protected $_dba_class = 'org_openpsa_invoices_invoice_dba';

    /**
     * @var org_openpsa_contacts_group_dba
     */
    private $customer;

    /**
     * @var org_openpsa_contacts_person_dba
     */
    private $contact;

    public function _load_schemadb()
    {
        $this->_schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb'));
        if (   $this->_mode == 'create'
            && count($this->_master->_handler['args']) == 1) {
            // We're creating invoice for chosen customer
            try {
                $this->customer = new org_openpsa_contacts_group_dba($this->_master->_handler['args'][0]);
            } catch (midcom_error $e) {
                $this->contact = new org_openpsa_contacts_person_dba($this->_master->_handler['args'][0]);
            }
        }
        $this->_modify_schema();
    }

    /**
     * Alter the schema based on the current operation
     */
    private function _modify_schema()
    {
        $fields =& $this->_schemadb['default']->fields;
        // Fill VAT select
        $vat_array = explode(',', $this->_config->get('vat_percentages'));
        if (!empty($vat_array)) {
            $vat_values = array();
            foreach ($vat_array as $vat) {
                $vat_values[$vat] = "{$vat}%";
            }
            $fields['vat']['type_config']['options'] = $vat_values;
        }

        if ($this->_config->get('invoice_pdfbuilder_class')) {
            $fields['pdf_file']['hidden'] = false;
        }
        $fields['due']['hidden'] = empty($this->_object->sent);
        $fields['sent']['hidden'] = empty($this->_object->sent);
        $fields['paid']['hidden'] = empty($this->_object->paid);

        if (!empty($this->_object->customerContact)) {
            $this->_populate_schema_customers_for_contact($this->_object->customerContact);
        } elseif ($this->customer) {
            $this->_populate_schema_contacts_for_customer($this->customer);
        } elseif ($this->contact) {
                $this->_populate_schema_customers_for_contact($this->contact->id);
        } elseif (!empty($this->_object->customer)) {
            try {
                $this->customer = org_openpsa_contacts_group_dba::get_cached($this->_object->customer);
                $this->_populate_schema_contacts_for_customer($this->customer);
            } catch (midcom_error $e) {
                $fields['customer']['hidden'] = true;
                $e->log();
            }
        } else {
            // We don't know company, present customer contact as chooser and hide customer field
            $fields['customer']['hidden'] = true;
        }
    }

    /**
     * List customer contact's groups
     */
    private function _populate_schema_customers_for_contact($contact_id)
    {
        $fields =& $this->_schemadb['default']->fields;
        $organizations = array(0 => '');
        $member_mc = org_openpsa_contacts_member_dba::new_collector('uid', $contact_id);
        $member_mc->add_constraint('gid.orgOpenpsaObtype', '>', org_openpsa_contacts_group_dba::MYCONTACTS);
        $groups = $member_mc->get_values('gid');
        if (!empty($groups)) {
            $qb = org_openpsa_contacts_group_dba::new_query_builder();
            $qb->add_constraint('id', 'IN', $groups);
            $qb->add_order('official');
            $qb->add_order('name');
            foreach ($qb->execute() as $group) {
                $organizations[$group->id] = $group->official;
            }
        }

        //Fill the customer field to DM
        $fields['customer']['type_config']['options'] = $organizations;
    }

    private function _populate_schema_contacts_for_customer($customer)
    {
        $fields =& $this->_schemadb['default']->fields;
        // We know the customer company, present contact as a select widget
        $persons_array = array();
        $member_mc = midcom_db_member::new_collector('gid', $customer->id);
        $members = $member_mc->get_values('uid');
        foreach ($members as $member) {
            try {
                $person = org_openpsa_contacts_person_dba::get_cached($member);
                $persons_array[$person->id] = $person->rname;
            } catch (midcom_error $e) {
            }
        }
        asort($persons_array);
        $fields['customerContact']['widget'] = 'select';
        $fields['customerContact']['type_config']['options'] = $persons_array;

        // And display the organization too
        $organization_array = array();
        $organization_array[$customer->id] = $customer->official;

        $fields['customer']['widget'] = 'select';
        $fields['customer']['type_config']['options'] = $organization_array;
    }

    /**
     * This is what Datamanager calls to actually create an invoice
     */
    public function & dm2_create_callback(&$datamanager)
    {
        $this->_object = new org_openpsa_invoices_invoice_dba();

        if (!$this->_object->create()) {
            debug_print_r('We operated on this object:', $this->_object);
            throw new midcom_error("Failed to create a new invoice. Error: " . midcom_connection::get_error_string());
        }

        return $this->_object;
    }

    public function _load_defaults()
    {
        $this->_defaults['date'] = time();
        $this->_defaults['deliverydate'] = time();
        $this->_defaults['owner'] = midcom_connection::get_user();

        $dummy = new org_openpsa_invoices_invoice_dba();
        if ($this->customer) {
            $dummy->customer = $this->customer->id;
            $this->_defaults['customer'] = $this->customer->id;
        } else if ($this->contact) {
            $dummy->customerContact = $this->contact->id;
            $this->_defaults['customerContact'] = $this->contact->id;
        }
        $this->_defaults['description'] = $dummy->get_default('remarks');
        $this->_defaults['vat'] = $dummy->get_default('vat');

        // Generate invoice number
        $client_class = midcom_baseclasses_components_configuration::get('org.openpsa.sales', 'config')->get('calculator');
        $calculator = new $client_class;
        $this->_defaults['number'] = $calculator->generate_invoice_number();
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_callback($handler_id, array $args, array &$data)
    {
        if ($this->_mode == 'read') {
            $qb = org_openpsa_projects_hour_report_dba::new_query_builder();
            $qb->add_constraint('invoice', '=', $this->_object->id);
            $this->_request_data['reports'] = $qb->execute();

            org_openpsa_widgets_grid::add_head_elements();

            $this->add_stylesheet(MIDCOM_STATIC_URL . "/org.openpsa.core/list.css");
            midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . "/" . $this->_component . "/invoices.js");
        }
    }

    function _populate_toolbar($handler_id)
    {
        if ($this->_mode == 'read') {
            $this->_populate_read_toolbar($handler_id);
        }
    }

    private function _populate_read_toolbar($handler_id)
    {
        $buttons = array();
        if ($this->_object->can_do('midgard:update')) {
            $workflow = $this->get_workflow('datamanager2');
            $buttons[] = $workflow->get_button("invoice/edit/{$this->_object->guid}/", array(
                MIDCOM_TOOLBAR_ACCESSKEY => 'e',
            ));
        }

        if ($this->_object->can_do('midgard:delete')) {
            $workflow = $this->get_workflow('delete', array('object' => $this->_object));
            $buttons[] = $workflow->get_button("invoice/delete/{$this->_object->guid}/");
        }

        $buttons[] = array(
            MIDCOM_TOOLBAR_URL => "invoice/items/{$this->_object->guid}/",
            MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('edit invoice items'),
            MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/edit.png',
            MIDCOM_TOOLBAR_ENABLED => $this->_object->can_do('midgard:update'),
        );

        if (!$this->_object->sent) {
            $buttons[] = $this->build_button('mark_sent', 'stock-icons/16x16/stock_mail-reply.png');
        } elseif (!$this->_object->paid) {
            $buttons[] = $this->build_button('mark_paid', 'stock-icons/16x16/ok.png');
        }

        if ($this->_config->get('invoice_pdfbuilder_class')) {
            $button = $this->build_button('create_pdf', 'stock-icons/16x16/printer.png');
            $pdf_helper = new org_openpsa_invoices_invoice_pdf($this->_object);
            $button[MIDCOM_TOOLBAR_OPTIONS] = $pdf_helper->get_button_options();
            $buttons[] = $button;

            // sending per email enabled in billing data?
            $billing_data = $this->_object->get_billing_data();
            if (intval($billing_data->sendingoption) == 2) {
                $buttons[] = $this->build_button('send_by_mail', 'stock-icons/16x16/stock_mail-reply.png');
            }
        }

        if ($this->_object->is_cancelable()) {
            $buttons[] = $this->build_button('create_cancelation', 'stock-icons/16x16/cancel.png');
        }
        $this->_view_toolbar->add_items($buttons);
        org_openpsa_relatedto_plugin::add_button($this->_view_toolbar, $this->_object->guid);

        $this->_master->add_next_previous($this->_object, $this->_view_toolbar, 'invoice/');
    }

    private function build_button($action, $icon)
    {
        return array(
            MIDCOM_TOOLBAR_URL => 'invoice/action/' . $action . '/',
            MIDCOM_TOOLBAR_LABEL => $this->_l10n->get($action),
            MIDCOM_TOOLBAR_ICON => $icon,
            MIDCOM_TOOLBAR_POST => true,
            MIDCOM_TOOLBAR_POST_HIDDENARGS => array(
                'id' => $this->_object->id,
                'relocate' => true
            ),
            MIDCOM_TOOLBAR_ENABLED => $this->_object->can_do('midgard:update'),
        );
    }

    /**
     * Update the context so that we get a complete breadcrumb line towards the current location.
     *
     * @param string $handler_id The current handler
     */
    function _update_breadcrumb($handler_id)
    {
        if ($customer = $this->_object->get_customer()) {
            $this->add_breadcrumb("list/customer/all/{$customer->guid}/", $customer->get_label());
        }

        $this->add_breadcrumb("invoice/" . $this->_object->guid . "/", $this->_l10n->get('invoice') . ' ' . $this->_object->get_label());
    }

    /**
     * Update title for current object and handler
     *
     * @param mixed $handler_id The ID of the handler.
     */
    public function _update_title($handler_id)
    {
        switch ($this->_mode) {
            case 'create':
                $view_title = $this->_l10n->get('create invoice');
                break;
            case 'read':
                $view_title = $this->_l10n->get('invoice') . ' ' . $this->_object->get_label();
                break;
            case 'update':
                $view_title = sprintf($this->_l10n_midcom->get('edit %s'), $this->_l10n->get('invoice'));
                break;
        }

        midcom::get()->head->set_pagetitle($view_title);
    }

    function _prepare_request_data()
    {
        $this->_request_data['object'] = $this->_object;
        $this->_request_data['datamanager'] = $this->_datamanager;
        $this->_request_data['controller'] = $this->_controller;
        $this->_request_data['invoice_items'] = $this->_object->get_invoice_items();
    }

    /**
     * Add or update the invoice to the MidCOM indexer service.
     *
     * @param &$dm Datamanager2 instance containing the object
     */
    public function _index_object(&$dm)
    {
        $indexer = new org_openpsa_invoices_midcom_indexer($this->_topic);
        return $indexer->index($dm);
    }
}
