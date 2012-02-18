<?php
/**
 * @package org.openpsa.reports
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Invoices reporting
 *
 * @package org.openpsa.reports
 */
class org_openpsa_reports_handler_invoices_report extends org_openpsa_reports_handler_base
{
    private $_sales_url;

    public function _on_initialize()
    {
        org_openpsa_widgets_contact::add_head_elements();
        $this->module = 'invoices';
        $this->_initialize_datamanager();
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_generator($handler_id, array $args, array &$data)
    {
        midcom::get('auth')->require_valid_user();

        $this->_generator_load_redirect($args);
        $this->set_active_leaf($this->_topic->id . ':generator_invoices');
        $this->_handler_generator_style();

        $data['invoices'] = Array();

        $data['start'] = $data['query_data']['start'];
        $data['end'] = $data['query_data']['end'];

        if (    !isset($data['query_data']['date_field'])
             || $data['query_data']['date_field'] == '')
        {
            $data['query_data']['date_field'] = $data['query']->get_parameter('midcom.helper.datamanager2', 'date_field');
        }
        $data['date_field'] = $data['query_data']['date_field'];

        $data['invoices'] = array();
        foreach ($data['query_data']['invoice_status'] as $status)
        {
            $data['invoices'][$status] = $this->_load_invoices($status);
        }

        org_openpsa_widgets_grid::add_head_elements();
    }

    private function _get_invoices_for_subscription($deliverable, $at_entry)
    {
        if (   $deliverable->invoiceByActualUnits
            && $at_entry->arguments['cycle'] > 1)
        {
            $invoice_sum = $deliverable->invoiced / ($at_entry->arguments['cycle'] - 1);
            if ($invoice_sum == 0)
            {
                return array();
            }
            $calculation_base = sprintf($this->_l10n->get('average of %s runs'), $at_entry->arguments['cycle'] - 1);
        }
        else
        {
            $invoice_sum = $deliverable->price;
            $calculation_base = $this->_l10n->get('fixed price');
        }

        $salesproject = org_openpsa_sales_salesproject_dba::get_cached($deliverable->salesproject);
        $scheduler = new org_openpsa_invoices_scheduler($deliverable);

        $invoices = array();
        $time = $at_entry->start;

        while (   $time < $this->_request_data['end']
               && (   $time < $deliverable->end
                   || $deliverable->continuous))
        {
            $invoice = new org_openpsa_invoices_invoice_dba();
            $invoice->customer = $salesproject->customer;
            $invoice->customerContact = $salesproject->customerContact;
            $invoice->owner = $salesproject->owner;
            $invoice->sum = $invoice_sum;

            $invoice->sent = $time;
            $invoice->due = ($invoice->get_default_due() * 3600 * 24) + $time;
            $invoice->vat = $invoice->get_default_vat();

            $invoice->description = $deliverable->title . ' (' . $calculation_base . ')';
            if ($this->_sales_url)
            {
                $invoice->description = '<a href="' . $this->_sales_url . 'deliverable/' . $deliverable->guid . '/">' . $invoice->description . '</a>';
            }

            $invoice->paid = $invoice->due;

            $invoices[] = $invoice;

            if (!$time = $scheduler->calculate_cycle_next($time))
            {
                debug_add('Failed to calculate timestamp for next cycle, exiting', MIDCOM_LOG_WARN);
                break;
            }
        }

        return $invoices;
    }

    private function _get_scheduled_invoices()
    {
        $invoices = array();
        $at_qb = midcom_services_at_entry_dba::new_query_builder();
        $at_qb->add_constraint('method', '=', 'new_subscription_cycle');
        $at_qb->add_constraint('component', '=', 'org.openpsa.sales');
        $at_entries = $at_qb->execute();
        foreach ($at_entries as $at_entry)
        {
            try
            {
                $deliverable = org_openpsa_sales_salesproject_deliverable_dba::get_cached($at_entry->arguments['deliverable']);
                if (   $deliverable->continuous
                    || (   $deliverable->start < $this->_request_data['end']
                        && $deliverable->end > $this->_request_data['start']))
                {
                    $invoices = array_merge($invoices, $this->_get_invoices_for_subscription($deliverable, $at_entry));
                }
            }
            catch (midcom_error $e){}
        }

        $invoices = array_filter($invoices, array($this, '_filter_by_date'));

        usort($invoices, array($this, '_sort_by_date'));

        return $invoices;
    }

    private function _sort_by_date($a, $b)
    {
        if ($a->{$this->_request_data['date_field']} == $b->{$this->_request_data['date_field']})
        {
            return 0;
        }
        return ($a->{$this->_request_data['date_field']} < $b->{$this->_request_data['date_field']}) ? -1 : 1;
    }

    private function _filter_by_date($inv)
    {
        if ($inv->{$this->_request_data['date_field']} > $this->_request_data['end'])
        {
            return false;
        }
        return true;
    }

    private function _load_invoices($status)
    {
        if ($status == 'scheduled')
        {
            $siteconfig = org_openpsa_core_siteconfig::get_instance();
            $this->_sales_url = $siteconfig->get_node_full_url('org.openpsa.sales');
            return $this->_get_scheduled_invoices();
        }

        $qb = org_openpsa_invoices_invoice_dba::new_query_builder();

        if ($status != 'unsent')
        {
            $qb->begin_group('AND');
                $qb->add_constraint($this->_request_data['date_field'], '>=', $this->_request_data['start']);
                $qb->add_constraint($this->_request_data['date_field'], '<', $this->_request_data['end']);
            $qb->end_group();
        }
        if ($this->_request_data['query_data']['resource'] != 'all')
        {
            $this->_request_data['query_data']['resource_expanded'] = $this->_expand_resource($this->_request_data['query_data']['resource']);
            $qb->add_constraint('owner', 'IN', $this->_request_data['query_data']['resource_expanded']);
        }

        switch ($status)
        {
            case 'unsent':
                $qb->add_constraint('sent', '=', 0);
                $qb->add_constraint('paid', '=', 0);
                break;
            case 'paid':
                $qb->add_constraint('paid', '>', 0);
                break;
            case 'overdue':
                $qb->add_constraint('sent', '>', 0);
                $qb->add_constraint('due', '<', mktime(0, 0, 0, date('n'), date('j') - 1, date('Y')));
                $qb->add_constraint('paid', '=', 0);
                break;
            case 'open':
                $qb->add_constraint('sent', '>', 0);
                $qb->add_constraint('paid', '=', 0);
                $qb->add_constraint('due', '>', mktime(0, 0, 0, date('n'), date('j') - 1, date('Y')));
                break;
        }

        $qb->add_order($this->_request_data['date_field'], 'DESC');

        $invoices = $qb->execute();

        return $invoices;
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_generator($handler_id, array &$data)
    {
        midcom_show_style('invoices_report-start');

        foreach ($data['invoices'] as $type => $invoices)
        {
            if (   is_array($invoices)
                && !empty($invoices))
            {
                $this->_show_table($type, $invoices, $data);
            }
        }
        midcom_show_style('invoices_report-end');
    }

    private function _show_table($type, &$invoices, &$data)
    {
        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        $data['invoices_url'] = $siteconfig->get_node_full_url('org.openpsa.invoices');
        $data['contacts_url'] = $siteconfig->get_node_full_url('org.openpsa.contacts');

        $data['table_class'] = $type;
        $data['table_title'] = midcom::get('i18n')->get_string($type . ' invoices', 'org.openpsa.invoices');

        $data['invoices'] = $invoices;

        midcom_show_style('invoices_report-grid');
    }
}
?>