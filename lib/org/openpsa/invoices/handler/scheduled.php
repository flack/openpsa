<?php
/**
 * @package org.openpsa.invoices
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use midcom\grid\provider\client;
use midcom\grid\provider;

/**
 * @package org.openpsa.invoices
 */
class org_openpsa_invoices_handler_scheduled extends midcom_baseclasses_components_handler
implements client
{
    use org_openpsa_invoices_handler;

    private $_sales_url;

    /**
     * @param array &$data The local request data.
     */
    public function _handler_list(array &$data)
    {
        midcom::get()->auth->require_valid_user();
        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        $this->_sales_url = $siteconfig->get_node_full_url('org.openpsa.sales');

        $provider = new provider($this, 'local');
        $provider->add_order('start');

        $data['grid'] = $provider->get_grid('scheduled_invoices');
        midcom::get()->head->set_pagetitle($this->_l10n->get('scheduled invoices'));
        $this->prepare_toolbar();
        $this->set_active_leaf($this->_topic->id . ':scheduled');

        return $this->show('show-scheduled');
    }

    public function get_qb($field = null, $direction = 'ASC', array $search = [])
    {
        $qb = midcom_services_at_entry_dba::new_query_builder();
        $qb->add_constraint('method', '=', 'new_subscription_cycle');
        $qb->add_constraint('component', '=', 'org.openpsa.sales');
        $qb->add_constraint('status', '=', midcom_services_at_entry_dba::SCHEDULED);
        if (!is_null($field)) {
            $qb->add_order($field, $direction);
        }

        return $qb;
    }

    public function get_row(midcom_core_dbaobject $at_entry)
    {
        $invoice = [
            'time' => strftime('%Y-%m-%d %H:%M:%S', $at_entry->start),
            'month' => $this->_l10n->get_formatter()->customdate($at_entry->start, 'MMMM y'),
            'index_month' => strftime('%Y-%m', $at_entry->start),
        ];
        try {
            $deliverable = org_openpsa_sales_salesproject_deliverable_dba::get_cached($at_entry->arguments['deliverable']);
            $salesproject = org_openpsa_sales_salesproject_dba::get_cached($deliverable->salesproject);
        } catch (midcom_error $e) {
            $e->log();
            return $invoice;
        }

        if ($deliverable->invoiceByActualUnits) {
            $type = $this->_l10n->get('invoiceable reports');
            $invoice_sum = $deliverable->units * $deliverable->pricePerUnit;
        } else {
            $invoice_sum = $deliverable->price;
            $type = $this->_i18n->get_l10n('org.openpsa.reports')->get('fixed price');
        }

        $invoice['sum'] = $invoice_sum;
        $invoice['deliverable'] = $deliverable->title;
        $invoice['index_deliverable'] = $deliverable->title;
        $invoice['type'] = $type;

        $this->_render_contact_field($salesproject->customer, 'customer', $invoice, org_openpsa_contacts_group_dba::class);
        $this->_render_contact_field($salesproject->customerContact, 'customerContact', $invoice);

        if (!empty($this->_sales_url)) {
            $invoice['deliverable'] = '<a href="' . $this->_sales_url . 'deliverable/' . $deliverable->guid . '/">' . $invoice['deliverable'] . '</a>';
        }

        return $invoice;
    }

    private function _render_contact_field($id, $fieldname, array &$invoice, $classname = org_openpsa_contacts_person_dba::class)
    {
        $sales_url = org_openpsa_core_siteconfig::get_instance()->get_node_full_url('org.openpsa.sales');
        $invoice[$fieldname] = '';
        $invoice['index_' . $fieldname] = '';
        if ($id > 0) {
            try {
                $object = $classname::get_cached($id);
                $invoice['index_' . $fieldname] = $object->get_label();
                $invoice[$fieldname] = $invoice['index_' . $fieldname];
                if ($sales_url) {
                    $invoice[$fieldname] = '<a href="' . $sales_url . 'list/customer/' . $object->guid . '/">' . $invoice[$fieldname] . '</a>';
                }
                $invoice['index_' . $fieldname] = $object->get_label();
            } catch (midcom_error $e) {
                $e->log();
            }
        }
    }
}
