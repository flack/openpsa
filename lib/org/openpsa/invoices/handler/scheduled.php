<?php
/**
 * @package org.openpsa.core
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Helper class that serves as a cache for OpenPSA site information
 *
 * It locates topics for specific components used in OpenPSA and automatically
 * generates a cached version of the site structure in the config snippet
 *
 * @package org.openpsa.core
 */
class org_openpsa_invoices_handler_scheduled extends midcom_baseclasses_components_handler
implements org_openpsa_widgets_grid_provider_client
{
    private $_reports_url;

    private $_sales_url;

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_list($handler_id, array $args, array &$data)
    {
        midcom::get('auth')->require_valid_user();
        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        $this->_sales_url = $siteconfig->get_node_full_url('org.openpsa.sales');

        $provider = new org_openpsa_widgets_grid_provider($this, 'local');
        $provider->add_order('start');

        $data['grid'] = $provider->get_grid('scheduled');
        midcom::get('head')->set_pagetitle($this->_l10n->get('scheduled invoices'));
        $this->add_breadcrumb('', $this->_l10n->get('scheduled invoices'));
    }

    public function get_qb($field = null, $direction = 'ASC')
    {
        $qb = midcom_services_at_entry_dba::new_query_builder();
        $qb->add_constraint('method', '=', 'new_subscription_cycle');
        $qb->add_constraint('component', '=', 'org.openpsa.sales');
        $qb->add_constraint('status', '=', midcom_services_at_entry_dba::SCHEDULED);
        if (!is_null($field))
        {
            $qb->add_order($field, $direction);
        }

        return $qb;
    }

    public function get_row(midcom_core_dbaobject $at_entry)
    {
        $invoice = array
        (
            'time' => strftime('%Y-%m-%d %H:%M:%S', $at_entry->start),
        );
        try
        {
            $deliverable = org_openpsa_sales_salesproject_deliverable_dba::get_cached($at_entry->arguments['deliverable']);
            $salesproject = org_openpsa_sales_salesproject_dba::get_cached($deliverable->salesproject);
        }
        catch (midcom_error $e)
        {
            $e->log();
            return $invoice;
        }

        if ($deliverable->invoiceByActualUnits)
        {
            $type = midcom::get('i18n')->get_l10n('org.openpsa.expenses')->get('invoiceable reports');
            $invoice_sum = $deliverable->units * $deliverable->pricePerUnit;
        }
        else
        {
            $invoice_sum = $deliverable->price;
            $type = midcom::get('i18n')->get_l10n('org.openpsa.reports')->get('fixed price');
        }

        $invoice['sum'] = $invoice_sum;
        $invoice['description'] = $deliverable->title;
        $invoice['type'] = $type;

        $invoice = $this->_render_contact_field($salesproject->customer, 'customer', $invoice, 'org_openpsa_contacts_group_dba');
        $invoice = $this->_render_contact_field($salesproject->customerContact, 'customerContact', $invoice);
        $invoice = $this->_render_contact_field($salesproject->owner, 'owner', $invoice);


        if (!empty($this->_sales_url))
        {
            $invoice['description'] = '<a href="' . $this->_sales_url . 'deliverable/' . $deliverable->guid . '/">' . $invoice['description'] . '</a>';
        }

        return $invoice;
    }

    private function _render_contact_field($id, $fieldname, array $invoice, $classname = 'org_openpsa_contacts_person_dba')
    {
        try
        {
            $object = $classname::get_cached($id);
            $invoice[$fieldname] = $object->render_link();
            $invoice['index_' . $fieldname] = $object->get_label();
        }
        catch (midcom_error $e)
        {
            $e->log();
            $invoice[$fieldname] = '';
            $invoice['index_' . $fieldname] = '';
        }
        return $invoice;
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_list($handler_id, array &$data)
    {
        midcom_show_style('show-scheduled');
    }
}
?>