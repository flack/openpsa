<?php
/**
 * @package org.openpsa.invoices
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Doctrine\ORM\Query\Expr\Join;
use midcom\grid\provider\client;
use midcom\grid\provider;

/**
 * Invoice list handler
 *
 * @package org.openpsa.invoices
 */
class org_openpsa_invoices_handler_list extends midcom_baseclasses_components_handler
implements client
{
    use org_openpsa_invoices_handler;

    /**
     * The customer we're working with, if any
     *
     * @var midcom_core_dbaobject
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

    /**
     * @var midcom_services_i18n_formatter
     */
    private $formatter;

    public function _on_initialize()
    {
        midcom::get()->auth->require_valid_user();
        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/org.openpsa.invoices/invoices.js');
        $this->formatter = $this->_l10n->get_formatter();
        midcom::get()->uimessages->add_head_elements();
    }

    public function get_qb($field = null, $direction = 'ASC', array $search = [])
    {
        $qb = org_openpsa_invoices_invoice_dba::new_collector();
        if ($field !== null) {
            $qb->add_order($field, $direction);
        }

        $this->_add_filters($qb);

        switch ($this->_list_type) {
            case 'paid':
                $qb->add_constraint('paid', '>', 0);
                break;
            case 'unsent':
                $qb->add_constraint('sent', '=', 0);
                break;
            case 'overdue':
                $qb->add_constraint('sent', '>', 0);
                $qb->add_constraint('paid', '=', 0);
                $qb->add_constraint('due', '<=', strtotime('yesterday'));
                break;
            case 'open':
                $qb->add_constraint('sent', '>', 0);
                $qb->add_constraint('paid', '=', 0);
                $qb->add_constraint('due', '>', strtotime('yesterday'));
                break;
        }

        $qb->add_order('number');
        return $qb;
    }

    public function get_row(midcom_core_dbaobject $invoice)
    {
        $link = $this->router->generate('invoice', ['guid' => $invoice->guid]);
        $entry = [];
        $number = $invoice->get_label();
        $link_html = "<a href='{$link}'>" . $number . "</a>";

        $entry['id'] = $invoice->id;
        $entry['index_number'] = $number;
        $entry['number'] = $link_html;

        if (!is_a($this->_customer, org_openpsa_contacts_group_dba::class)) {
            try {
                $customer = org_openpsa_contacts_group_dba::get_cached($invoice->customer);
                $entry['customer'] = "<a href=\"{$this->router->generate('list_customer_all', ['guid' => $customer->guid])}\">" . $customer->get_label() . "</a>";
                $entry['index_customer'] = $customer->get_label();
            } catch (midcom_error $e) {
                $entry['customer'] = '';
                $entry['index_customer'] = '';
            }
        }

        if (!is_a($this->_customer, org_openpsa_contacts_person_dba::class)) {
            try {
                $contact = org_openpsa_contacts_person_dba::get_cached($invoice->customerContact);
                $entry['contact'] = "<a href=\"{$this->router->generate('list_customer_all', ['guid' => $contact->guid])}\">" . $contact->get_label() . "</a>";
                $entry['index_contact'] = $contact->get_label();
            } catch (midcom_error $e) {
                $entry['contact'] = '';
                $entry['index_contact'] = '';
            }
        }

        if (!empty($this->_request_data['deliverable'])) {
            $constraints = [
                'invoice' => $invoice->id,
                'deliverable' => $this->_request_data['deliverable']->id
            ];
            $item_sum = org_openpsa_invoices_invoice_item_dba::get_sum($constraints);
            $this->_request_data['totals']['deliverable'] += $item_sum;
            $entry['index_item_sum'] = $item_sum;
            $entry['item_sum'] = '<span title="' . $this->_l10n->get('sum including vat') . ': ' . $this->formatter->number((($item_sum / 100) * $invoice->vat) + $item_sum) . '">' . $this->formatter->number($item_sum) . '</span>';
        }
        $entry['index_sum'] = $invoice->sum;
        $entry['sum'] = '<span title="' . $this->_l10n->get('sum including vat') . ': ' . $this->formatter->number((($invoice->sum / 100) * $invoice->vat) + $invoice->sum) . '">' . $this->formatter->number($invoice->sum) . '</span>';

        $entry['due'] = '';
        if ($invoice->due > 0) {
            $entry['due'] = strftime('%Y-%m-%d', $invoice->due);
        }

        $colname = 'action';
        if ($this->_list_type == 'paid') {
            $colname = 'paid';
        }
        $entry[$colname] = $this->render_invoice_actions($invoice);

        return $entry;
    }

    public function _handler_json(string $type, array &$data)
    {
        midcom::get()->skip_page_style = true;
        $this->_list_type = $type;
        $data['provider'] = new provider($this);

        return $this->show('show-grid-json');
    }

    public function _handler_dashboard(array &$data)
    {
        $this->prepare_toolbar(false);

        if (   $this->_topic->can_do('midgard:update')
            && $this->_topic->can_do('midcom:component_config')) {
            $workflow = $this->get_workflow('datamanager');
            $this->_node_toolbar->add_item($workflow->get_button($this->router->generate('config'), [
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('component configuration'),
                MIDCOM_TOOLBAR_HELPTEXT => $this->_l10n_midcom->get('component configuration helptext'),
                MIDCOM_TOOLBAR_GLYPHICON => 'wrench',
            ]));
        }

        $data['customer'] = $this->_customer;

        midcom::get()->head->set_pagetitle($this->_l10n->get('dashboard'));
    }

    public function _show_dashboard(string $handler_id, array &$data)
    {
        $this->_show_invoice_list('unsent');
        $this->_show_invoice_list('overdue');
        $this->_show_invoice_list('open');
        $this->_show_recent();
    }

    /**
     * Show the six most recently paid invoices
     */
    private function _show_recent()
    {
        $this->_request_data['list_type'] = 'paid';
        $this->_list_type = 'recent';
        $provider = new provider($this);
        $provider->add_order('paid', 'DESC');

        if ($provider->count_rows() > 0) {
            $this->_request_data['grid'] = $provider->get_grid('paid_invoices_grid');
            $this->_request_data['list_label'] = $this->_l10n->get('recently paid invoices');

            midcom_show_style('show-grid-ajax');
        }
    }

    /**
     * Add a customer/deliverable constraint to list QBs
     */
    private function _add_filters(midcom_core_query $qb)
    {
        if ($this->_deliverable) {
            $qb->get_doctrine()
                ->leftJoin('org_openpsa_invoice_item', 'i', Join::WITH, 'i.invoice = c.id')
                ->setParameter('deliverable', $this->_deliverable->id);
            $qb->get_current_group()->add('i.deliverable = :deliverable');
        } elseif ($this->_customer) {
            if (is_a($this->_customer, org_openpsa_contacts_group_dba::class)) {
                $qb->add_constraint('customer', '=', $this->_customer->id);
            } else {
                $qb->add_constraint('customerContact', '=', $this->_customer->id);
            }
        }
    }

    private function _show_invoice_list(string $type = 'all')
    {
        $this->_list_type = $type;

        $provider = new provider($this, 'local');
        if ($provider->count_rows() == 0) {
            return;
        }

        switch ($this->_list_type) {
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

        if ($this->_deliverable) {
            $grid_id = 'd_' . $this->_deliverable->id . $grid_id;
            $this->_request_data['totals']['deliverable'] = 0;
        }

        $this->_request_data['grid'] = $provider->get_grid($grid_id);
        $this->_request_data['list_type'] = $this->_list_type;

        $label = ($type == 'all') ? 'invoices' : $type . ' invoices';
        $this->_request_data['list_label'] = $this->_l10n->get($label);

        midcom_show_style('show-grid');
    }

    public function _handler_customer(string $guid, array &$data)
    {
        try {
            $this->_customer = new org_openpsa_contacts_group_dba($guid);
        } catch (midcom_error $e) {
            $this->_customer = new org_openpsa_contacts_person_dba($guid);
        }
        $data['customer'] = $this->_customer;
        $buttons = [];
        if (midcom::get()->auth->can_user_do('midgard:create', null, org_openpsa_invoices_invoice_dba::class)) {
            $workflow = $this->get_workflow('datamanager');
            $buttons[] = $workflow->get_button($this->router->generate('invoice_new', ['company' => $guid]), [
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('create invoice'),
                MIDCOM_TOOLBAR_GLYPHICON => 'plus',
            ]);

            if ($this->_customer->can_do('midgard:create')) {
                $buttons[] = $workflow->get_button($this->router->generate('billing_data', ['guid' => $guid]), [
                    MIDCOM_TOOLBAR_LABEL => $this->_i18n->get_string('edit billingdata', 'org.openpsa.contacts'),
                    MIDCOM_TOOLBAR_OPTIONS => ['data-refresh-opener' => 'false'],
                    MIDCOM_TOOLBAR_GLYPHICON => 'address-card'
                ]);
            }
        }

        // Locate Contacts node for linking
        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        if ($contacts_url = $siteconfig->get_node_full_url('org.openpsa.contacts')) {
            $buttons[] = [
                MIDCOM_TOOLBAR_URL => $contacts_url . (is_a($this->_customer, org_openpsa_contacts_group_dba::class) ? 'group' : 'person') . "/{$guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('go to customer'),
                MIDCOM_TOOLBAR_GLYPHICON => 'user',
            ];
        }
        $this->_view_toolbar->add_items($buttons);

        $title = sprintf($this->_l10n->get('all invoices for customer %s'), $this->_customer->get_label());

        midcom::get()->head->set_pagetitle($title);

        $this->add_breadcrumb("", $title);
    }

    public function _show_customer(string $handler_id, array &$data)
    {
        $this->_show_invoice_list('unsent');
        $this->_show_invoice_list('overdue');
        $this->_show_invoice_list('open');
        $this->_show_invoice_list('paid');
    }

    public function _handler_deliverable(string $guid, array &$data)
    {
        // We're displaying invoices of a specific deliverable
        $this->_deliverable = new org_openpsa_sales_salesproject_deliverable_dba($guid);
        $data['deliverable'] = $this->_deliverable;
        $salesproject = new org_openpsa_sales_salesproject_dba($this->_deliverable->salesproject);
        $this->_customer = $salesproject->get_customer();
        $data['customer'] = $this->_customer;

        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        if ($sales_url = $siteconfig->get_node_full_url('org.openpsa.sales')) {
            $this->_view_toolbar->add_item([
                MIDCOM_TOOLBAR_URL => $sales_url . "deliverable/{$guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('go to deliverable'),
                MIDCOM_TOOLBAR_GLYPHICON => 'money',
            ]);
        }

        $title = sprintf($this->_l10n->get('all invoices for deliverable %s'), $this->_deliverable->title);
        midcom::get()->head->set_pagetitle($title);
        $this->add_breadcrumb("", $title);
    }

    public function _show_deliverable(string $handler_id, array &$data)
    {
        $this->_show_invoice_list();
    }
}
