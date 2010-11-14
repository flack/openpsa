<?php
/**
 * @package org.openpsa.invoices
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: list.php 26714 2010-10-22 19:25:07Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * invoice list handler
 *
 * @package org.openpsa.invoices
 */
class org_openpsa_invoices_handler_list extends midcom_baseclasses_components_handler
{
    /**
     * The customer we're working with, if any
     *
     * @var org_openpsa_contacts_group_dba
     * @access private
     */
    private $_customer = null;

    function __construct()
    {
        parent::__construct();
    }

    function _on_initialize()
    {
        // Locate Contacts node for linking
        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        $this->_request_data['contacts_url'] = $siteconfig->get_node_full_url('org.openpsa.contacts');
        $this->_request_data['invoices_url'] = $siteconfig->get_node_full_url('org.openpsa.invoices');

        org_openpsa_core_ui::enable_jqgrid();
    }

    private function _process_invoice_list($invoices)
    {
        $this->_request_data['invoices'] = Array();
        $this->_request_data['totals']['totals'] = 0;

        foreach ($invoices as $invoice)
        {
            $this->_request_data['totals']['totals'] += $invoice->sum;
            $this->_request_data['invoices'][] = $invoice;
        }
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_dashboard($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_valid_user();

        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => 'invoice/new/',
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('create invoice'),
                MIDCOM_TOOLBAR_HELPTEXT => null,
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/printer.png',
                MIDCOM_TOOLBAR_ENABLED => $_MIDCOM->auth->can_user_do('midgard:create', null, 'org_openpsa_invoices_invoice_dba'),
            )
        );

        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => 'projects/',
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('project invoicing'),
                MIDCOM_TOOLBAR_HELPTEXT => null,
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/printer.png',
                MIDCOM_TOOLBAR_ENABLED => $_MIDCOM->auth->can_user_do('midgard:create', null, 'org_openpsa_invoices_invoice_dba'),
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

        $_MIDCOM->set_pagetitle($this->_l10n->get('dashboard'));

        return true;
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    function _show_dashboard($handler_id, &$data)
    {
        $this->_request_data['header-size'] = 2;

        $this->_show_unsent();
        $this->_show_overdue();
        $this->_show_open();
        $this->_show_recent();
    }

    /**
     * Helper that loads all unsent invoices
     */
    private function _show_unsent()
    {
        $qb = org_openpsa_invoices_invoice_dba::new_query_builder();
        $qb->add_constraint('sent', '=', 0);
        $this->_add_customer_filter($qb);
        $qb->add_order('number');
        $invoices = $qb->execute();
        $this->_process_invoice_list($invoices);

        $this->_request_data['list_label'] = $this->_l10n->get('unsent invoices');
        $this->_request_data['list_type'] = 'unsent';

        $this->_show_invoice_list();
    }

    /**
     * Helper that loads all overdue invoices
     */
    private function _show_overdue()
    {
        $qb = org_openpsa_invoices_invoice_dba::new_query_builder();
        $qb->add_constraint('sent', '>', 0);
        $qb->add_constraint('paid', '=', 0);
        $qb->add_constraint('due', '<=', mktime(0, 0, 0, date('n'), date('j') - 1, date('Y')));
        $this->_add_customer_filter($qb);
        $qb->add_order('due');
        $qb->add_order('number');
        $invoices = $qb->execute();
        $this->_process_invoice_list($invoices);

        $this->_request_data['list_label'] = $this->_l10n->get('overdue invoices');
        $this->_request_data['list_type'] = 'overdue';

        $this->_show_invoice_list();
    }

    /**
     * Helper that loads all open (sent, unpaid and not overdue) invoices
     */
    private function _show_open()
    {
        $qb = org_openpsa_invoices_invoice_dba::new_query_builder();
        $qb->add_constraint('sent', '>', 0);
        $qb->add_constraint('paid', '=', 0);
        $qb->add_constraint('due', '>', mktime(0, 0, 0, date('n'), date('j') - 1, date('Y')));
        $this->_add_customer_filter($qb);
        $qb->add_order('due');
        $qb->add_order('number');
        $invoices = $qb->execute();
        $this->_process_invoice_list($invoices);

        $this->_request_data['list_label'] = $this->_l10n->get('open invoices');
        $this->_request_data['list_type'] = 'open';

        $this->_show_invoice_list();
    }

    /**
     * Helper that shows the six most recently paid invoices
     */
    private function _show_recent()
    {
        $qb = org_openpsa_invoices_invoice_dba::new_query_builder();
        $qb->add_constraint('paid', '>', 0);
        $qb->add_order('paid', 'DESC');
        $qb->set_limit(6);
        $invoices = $qb->execute();
        $this->_process_invoice_list($invoices);

        $this->_request_data['list_label'] = $this->_l10n->get('recently paid invoices');
        $this->_request_data['list_type'] = 'paid';

        $this->_show_invoice_list();
    }

    /**
     * Helper that shows all paid invoices
     */
    private function _show_paid()
    {
        $qb = org_openpsa_invoices_invoice_dba::new_query_builder();
        $qb->add_constraint('paid', '>', 0);
        $this->_add_customer_filter($qb);
        $qb->add_order('paid', 'DESC');

        $invoices = $qb->execute_unchecked();
        $this->_process_invoice_list($invoices);

        $this->_request_data['list_label'] = $this->_l10n->get('paid invoices');
        $this->_request_data['list_type'] = 'paid';

        $this->_show_invoice_list();
    }

    /**
     * Helper that adds a customer constraint to list QBs
     *
     * @param midcom_core_querybuilder &$qb th QB we're working with
     */
    private function _add_customer_filter(&$qb)
    {
        if ($this->_customer)
        {
            $qb->add_constraint('customer', '=', $this->_customer->id);
        }
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_customer($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_valid_user();

        if (count($args) != 1)
        {
            return false;
        }

        // We're creating invoice for chosen company
        $this->_customer = org_openpsa_contacts_group_dba::get_cached($args[0]);
        if (!$this->_customer)
        {
            return false;
        }

        $data['customer'] =& $this->_customer;

        if ($_MIDCOM->auth->can_user_do('midgard:create', null, 'org_openpsa_invoices_invoice_dba'))
        {
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "invoice/new/{$this->_request_data['customer']->guid}/",
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('create invoice'),
                    MIDCOM_TOOLBAR_HELPTEXT => null,
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/printer.png',
                )
            );

            $billing_data_url = "create/" . $this->_customer->guid ."/";
            $qb_billing_data = org_openpsa_invoices_billing_data_dba::new_query_builder();
            $qb_billing_data->add_constraint('linkGuid' , '=' , $this->_customer->guid);
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
                    MIDCOM_TOOLBAR_LABEL => $_MIDCOM->i18n->get_string('edit billingdata', 'org.openpsa.contacts'),
                    MIDCOM_TOOLBAR_HELPTEXT => null,
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
                    MIDCOM_TOOLBAR_URL => $this->_request_data['contacts_url'] . "group/{$this->_request_data['customer']->guid}/",
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('go to customer'),
                    MIDCOM_TOOLBAR_HELPTEXT => null,
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/jump-to.png',
                )
            );
        }

        $title = sprintf($this->_l10n->get('all invoices for customer %s'), $this->_request_data['customer']->official);

        $_MIDCOM->set_pagetitle($title);

        $tmp = Array();

        $tmp[] = array
        (
            MIDCOM_NAV_URL => "",
            MIDCOM_NAV_NAME => $title,
        );

        $_MIDCOM->set_custom_context_data('midcom.helper.nav.breadcrumb', $tmp);

        return true;
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    function _show_customer($handler_id, &$data)
    {
        $this->_request_data['header-size'] = 2;

        $this->_show_unsent();
        $this->_show_overdue();
        $this->_show_open();
        $this->_show_paid();
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_deliverable($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_valid_user();

        if (count($args) != 1)
        {
            return false;
        }

        // We're displaying invoices of a specific deliverable
        $data['deliverable'] = new org_openpsa_sales_salesproject_deliverable_dba($args[0]);
        if (!$data['deliverable'])
        {
            return false;
        }

        $data['list_label'] = $this->_l10n->get('invoices');

        $data['invoices'] = Array();
        $data['totals'] = Array();
        $data['totals']['totals'] = 0;
        $data['list_type'] = 'all';

        $mc = new org_openpsa_relatedto_collector($data['deliverable']->guid, 'org_openpsa_invoices_invoice_dba');
        $invoices = $mc->get_related_objects();

        $this->_process_invoice_list($invoices);

        $_MIDCOM->set_pagetitle($data['list_label']);

        return true;
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    function _show_deliverable($handler_id, &$data)
    {
        $this->_request_data['header-size'] = 4;
        $this->_show_invoice_list();
    }

    private function _show_invoice_list()
    {
        if (count($this->_request_data['invoices']) > 0)
        {
            $show_customer = true;
            if ($this->_customer)
            {
                $show_customer = false;
            }

            $this->_request_data['show_customer'] = $show_customer;
            midcom_show_style('show-grid');
        }
    }


}

?>