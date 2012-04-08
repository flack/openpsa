<?php
/**
 * @package org.openpsa.invoices
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Invoice list handler
 *
 * @package org.openpsa.invoices
 */
class org_openpsa_invoices_handler_list extends midcom_baseclasses_components_handler
implements org_openpsa_widgets_grid_provider_client
{
    /**
     * The customer we're working with, if any
     *
     * @var org_openpsa_contacts_group_dba
     */
    private $_customer;

    /**
     * The deliverable we're working with, if any
     *
     * @var org_openpsa_sales_salesproject_deliverable_dba
     */
    private $_deliverable;

    /**
     * The current list type
     *
     * @var string
     */
    private $_list_type = 'all';

    public function _on_initialize()
    {
        // Locate Contacts node for linking
        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        $this->_request_data['contacts_url'] = $siteconfig->get_node_full_url('org.openpsa.contacts');
        $this->_request_data['invoices_url'] = $siteconfig->get_node_full_url('org.openpsa.invoices');
        org_openpsa_invoices_viewer::add_head_elements_for_invoice_grid();
    }

    public function get_qb($field = null, $direction = 'ASC')
    {
        $qb = org_openpsa_invoices_invoice_dba::new_collector('metadata.deleted', false);
        if (!is_null($field))
        {
            $qb->add_order($field, $direction);
        }

        $this->_add_filters($qb);

        switch ($this->_list_type)
        {
            case 'paid':
                $qb->add_constraint('paid', '>', 0);
                break;
            case 'unsent':
                $qb->add_constraint('sent', '=', 0);
                break;
            case 'overdue':
                $qb->add_constraint('sent', '>', 0);
                $qb->add_constraint('paid', '=', 0);
                $qb->add_constraint('due', '<=', mktime(0, 0, 0, date('n'), date('j') - 1, date('Y')));
                break;
            case 'open':
                $qb->add_constraint('sent', '>', 0);
                $qb->add_constraint('paid', '=', 0);
                $qb->add_constraint('due', '>', mktime(0, 0, 0, date('n'), date('j') - 1, date('Y')));
                break;
        }

        $qb->add_order('number');
        return $qb;
    }

    public function get_row(midcom_core_dbaobject $invoice)
    {
        $prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
        $entry = array();
        $number = $invoice->get_label();
        $link_html = "<a href='{$prefix}invoice/{$invoice->guid}/'>" . $number . "</a>";

        if ($number == "")
        {
            $number = "n/a";
        }

        $entry['id'] = $invoice->id;
        $entry['index_number'] = $number;
        $entry['number'] = $link_html;

        if (!is_a($this->_customer, 'org_openpsa_contacts_group_dba'))
        {
            try
            {
                $customer = org_openpsa_contacts_group_dba::get_cached($invoice->customer);
                $entry['customer'] = "<a href=\"{$this->_request_data['invoices_url']}list/customer/all/{$customer->guid}/\">" . $customer->get_label() . "</a>";
            }
            catch (midcom_error $e)
            {
                $entry['customer'] = '';
            }
        }

        if (!is_a($this->_customer, 'org_openpsa_contacts_person_dba'))
        {
            try
            {
                $contact = org_openpsa_contacts_person_dba::get_cached($invoice->customerContact);
                $entry['contact'] = "<a href=\"{$this->_request_data['invoices_url']}list/customer/all/{$contact->guid}/\">" . $contact->get_label() . "</a>";
            }
            catch (midcom_error $e)
            {
                $entry['contact'] = '';
            }
        }

        if (!empty($this->_request_data['deliverable']))
        {
            $constraints = array
            (
                'invoice' => $invoice->id,
                'deliverable' => $this->_request_data['deliverable']->id
            );
            $item_sum = org_openpsa_invoices_invoice_item_dba::get_sum($constraints);
            $this->_request_data['totals']['deliverable'] += $item_sum;
            $entry['index_item_sum'] = $item_sum;
            $entry['item_sum'] = '<span title="' . $this->_l10n->get('sum including vat') . ': ' . org_openpsa_helpers::format_number((($item_sum / 100) * $invoice->vat) + $item_sum) . '">' . org_openpsa_helpers::format_number($item_sum) . '</span>';
        }
        $entry['index_sum'] = $invoice->sum;
        $entry['sum'] = '<span title="' . $this->_l10n->get('sum including vat') . ': ' . org_openpsa_helpers::format_number((($invoice->sum / 100) * $invoice->vat) + $invoice->sum) . '">' . org_openpsa_helpers::format_number($invoice->sum) . '</span>';

        $entry['due'] = '';
        if ($invoice->due > 0)
        {
            $entry['due'] = strftime('%Y-%m-%d', $invoice->due);
        }

        $colname = 'action';
        if ($this->_list_type == 'paid')
        {
            $colname = 'paid';
        }
        $entry[$colname] = $this->_master->render_invoice_actions($invoice);

        return $entry;
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_json($handler_id, array $args, array &$data)
    {
        midcom::get('auth')->require_valid_user();
        midcom::get()->skip_page_style = true;
        $this->_list_type = $args[0];
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_json($handler_id, array &$data)
    {
        $data['provider'] = new org_openpsa_widgets_grid_provider($this);
        midcom_show_style('show-grid-json');
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_dashboard($handler_id, array $args, array &$data)
    {
        midcom::get('auth')->require_valid_user();

        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => 'invoice/new/',
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('create invoice'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/printer.png',
                MIDCOM_TOOLBAR_ENABLED => midcom::get('auth')->can_user_do('midgard:create', null, 'org_openpsa_invoices_invoice_dba'),
            )
        );

        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => 'projects/',
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('project invoicing'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/printer.png',
                MIDCOM_TOOLBAR_ENABLED => midcom::get('auth')->can_user_do('midgard:create', null, 'org_openpsa_invoices_invoice_dba'),
            )
        );

        if (   $this->_topic->can_do('midgard:update')
            && $this->_topic->can_do('midcom:component_config'))
        {
            $this->_node_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => 'config/',
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('component configuration'),
                    MIDCOM_TOOLBAR_HELPTEXT => $this->_l10n_midcom->get('component configuration helptext'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_folder-properties.png',
                )
            );
        }
        $this->_request_data['customer'] = $this->_customer;

        midcom::get('head')->set_pagetitle($this->_l10n->get('dashboard'));
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_dashboard($handler_id, array &$data)
    {
        $this->_show_invoice_list('unsent');
        $this->_show_invoice_list('overdue');
        $this->_show_invoice_list('open');
        $this->_show_recent();
    }

    /**
     * Helper that shows the six most recently paid invoices
     */
    private function _show_recent()
    {
        $this->_request_data['list_type'] = 'paid';
        $this->_list_type = 'recent';
        $provider = new org_openpsa_widgets_grid_provider($this);
        $provider->add_order('paid', 'DESC');

        if ($provider->count_rows() > 0)
        {
            $this->_request_data['grid'] = $provider->get_grid('paid_invoices_grid');
            $this->_request_data['list_label'] = $this->_l10n->get('recently paid invoices');

            midcom_show_style('show-grid-ajax');
        }
    }

    /**
     * Helper that adds a customer/deliverable constraints to list QBs
     *
     * @param midcom_core_querybuilder &$qb th QB we're working with
     */
    private function _add_filters(&$qb)
    {
        if ($this->_customer)
        {
            if (is_a($this->_customer, 'org_openpsa_contacts_group_dba'))
            {
                $qb->add_constraint('customer', '=', $this->_customer->id);
            }
            else
            {
                $qb->add_constraint('customerContact', '=', $this->_customer->id);
            }
        }
        if ($this->_deliverable)
        {
            $mc = org_openpsa_invoices_invoice_item_dba::new_collector('deliverable', $this->_deliverable->id);
            $invoice_ids = $mc->get_values('invoice');

            if (!empty($invoice_ids))
            {
                $qb->add_constraint('id', 'IN', $invoice_ids);
            }
            else
            {
                $qb->add_constraint('id', '=', 0);
            }
        }
    }

    private function _show_invoice_list($type = 'all')
    {
        $this->_list_type = $type;

        $provider = new org_openpsa_widgets_grid_provider($this, 'local');
        if ($provider->count_rows() == 0)
        {
            return;
        }
        switch ($this->_list_type)
        {
            case 'paid':
                $provider->add_order('paid', 'DESC');
                break;
            case 'unsent':
                $provider->add_order('index_number');
                break;
            case 'overdue':
            case 'open':
                $provider->add_order('due');
                break;
        }
        $grid_id = $type . '_invoices_grid';

        if ($this->_deliverable)
        {
            $grid_id = 'd_' . $this->_deliverable->id . $grid_id;
            $this->_request_data['totals']['deliverable'] = 0;
        }

        $this->_request_data['grid'] = $provider->get_grid($grid_id);
        $this->_request_data['list_type'] = $this->_list_type;

        $label = ($type == 'all') ? 'invoices' : $type . ' invoices';
        $this->_request_data['list_label'] = $this->_l10n->get($label);

        midcom_show_style('show-grid');
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_customer($handler_id, array $args, array &$data)
    {
        midcom::get('auth')->require_valid_user();

        try
        {
            $this->_customer = new org_openpsa_contacts_group_dba($args[0]);
        }
        catch (midcom_error $e)
        {
            $this->_customer = new org_openpsa_contacts_person_dba($args[0]);
        }
        $data['customer'] = $this->_customer;

        if (midcom::get('auth')->can_user_do('midgard:create', null, 'org_openpsa_invoices_invoice_dba'))
        {
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "invoice/new/{$this->_request_data['customer']->guid}/",
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('create invoice'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/printer.png',
                )
            );

            $billing_data_url = "create/" . $this->_customer->guid ."/";
            $qb_billing_data = org_openpsa_invoices_billing_data_dba::new_query_builder();
            $qb_billing_data->add_constraint('linkGuid', '=', $this->_customer->guid);
            $billing_data = $qb_billing_data->execute();
            if (count($billing_data) > 0)
            {
                $billing_data_url = $billing_data[0]->guid . "/";
            }

            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "billingdata/" . $billing_data_url,
                    MIDCOM_TOOLBAR_LABEL => midcom::get('i18n')->get_string('edit billingdata', 'org.openpsa.contacts'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/edit.png',
                    MIDCOM_TOOLBAR_ENABLED => $this->_customer->can_do('midgard:update'),
                )
            );
        }

        if ($this->_request_data['contacts_url'])
        {
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => $this->_request_data['contacts_url'] . (is_a($this->_customer, 'org_openpsa_contacts_group_dba') ? 'group' : 'person') . "/{$this->_request_data['customer']->guid}/",
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('go to customer'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/jump-to.png',
                )
            );
        }

        $title = sprintf($this->_l10n->get('all invoices for customer %s'), $this->_request_data['customer']->get_label());

        midcom::get('head')->set_pagetitle($title);

        $this->add_breadcrumb("", $title);
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_customer($handler_id, array &$data)
    {
        $this->_show_invoice_list('unsent');
        $this->_show_invoice_list('overdue');
        $this->_show_invoice_list('open');
        $this->_show_invoice_list('paid');
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_deliverable($handler_id, array $args, array &$data)
    {
        midcom::get('auth')->require_valid_user();

        if (count($args) != 1)
        {
            throw new midcom_error('Incomplete request data');
        }

        // We're displaying invoices of a specific deliverable
        $this->_deliverable = new org_openpsa_sales_salesproject_deliverable_dba($args[0]);
        $data['deliverable'] = $this->_deliverable;

        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        $sales_url = $siteconfig->get_node_full_url('org.openpsa.sales');

        if ($sales_url)
        {
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => $sales_url . "deliverable/{$data['deliverable']->guid}/",
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('go to deliverable'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/jump-to.png',
                )
            );
        }

        $title = sprintf($this->_l10n->get('all invoices for deliverable %s'), $data['deliverable']->title);
        midcom::get('head')->set_pagetitle($title);
        $this->add_breadcrumb("", $title);
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_deliverable($handler_id, array &$data)
    {
        $this->_show_invoice_list();
    }
}
?>