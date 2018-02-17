<?php
/**
 * @package org.openpsa.expenses
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\helper\autocomplete;

/**
 * @package org.openpsa.expenses
 */
class org_openpsa_expenses_handler_hours_list extends midcom_baseclasses_components_handler
{
    /**
     * @var midcom_core_querybuilder
     */
    private $qb;

    /**
     * @var string
     */
    private $breadcrumb_title;

    public function _on_initialize()
    {
        $this->qb = org_openpsa_expenses_hour_report_dba::new_query_builder();
        $this->qb->add_order('date', 'DESC');

        org_openpsa_widgets_grid::add_head_elements();
        autocomplete::add_head_elements();
        org_openpsa_widgets_contact::add_head_elements();
    }

    /**
     * The handler for the list view
     *
     * @param mixed $handler_id the array key from the request array
     * @param array $args the arguments given to the handler
     * @param array &$data The local request data.
     */
    public function _handler_list($handler_id, array $args, array &$data)
    {
        $data['mode'] = 'full';
        $data['view_title'] = $data['l10n']->get('hour reports');
        $this->breadcrumb_title = $data['view_title'];
        $this->_master->add_list_filter($this->qb, true);
        $this->prepare_request_data();

        return $this->show_list($data);
    }

    /**
     * The handler for the task list view
     *
     * @param mixed $handler_id the array key from the request array
     * @param array $args the arguments given to the handler
     * @param array &$data The local request data.
     */
    public function _handler_task($handler_id, array $args, array &$data)
    {
        $task = new org_openpsa_projects_task_dba($args[0]);
        $this->qb->add_constraint('task', '=', $task->id);

        $data['mode'] = 'task';
        $data['view_title'] = sprintf($data['l10n']->get("list_hours_task %s"), $task->title);
        $this->breadcrumb_title = $task->get_label();

        $this->prepare_request_data('task/', $task->guid . '/');
        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        if ($projects_url = $siteconfig->get_node_full_url('org.openpsa.projects')) {
            $this->_view_toolbar->add_item([
                MIDCOM_TOOLBAR_URL => $projects_url . "task/{$task->guid}/",
                MIDCOM_TOOLBAR_LABEL => sprintf($this->_l10n->get('show task %s'), $task->title),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/jump-to.png',
                MIDCOM_TOOLBAR_ACCESSKEY => 'g',
            ]);
        }

        return $this->show_list($data);
    }

    /**
     * The handler for the invoice list view
     *
     * @param mixed $handler_id the array key from the request array
     * @param array $args the arguments given to the handler
     * @param array &$data The local request data.
     */
    public function _handler_invoice($handler_id, array $args, array &$data)
    {
        $invoice = new org_openpsa_invoices_invoice_dba($args[0]);
        $this->qb->add_constraint('invoice', '=', $invoice->id);

        $data['mode'] = 'invoice';
        $data['view_title'] = sprintf($data['l10n']->get("list_hours_invoice %s"), $invoice->get_label());
        $this->breadcrumb_title = $data['view_title'];

        $this->prepare_request_data('invoice/', $invoice->guid . '/');
        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        if ($invoices_url = $siteconfig->get_node_full_url('org.openpsa.invoices')) {
            $this->_view_toolbar->add_item([
                MIDCOM_TOOLBAR_URL => $invoices_url . "invoice/{$invoice->guid}/",
                MIDCOM_TOOLBAR_LABEL => sprintf($this->_l10n->get('show invoice %s'), $invoice->get_label()),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/jump-to.png',
                MIDCOM_TOOLBAR_ACCESSKEY => 'g',
            ]);
        }
        return $this->show_list($data);
    }

    private function show_list(array &$data)
    {
        $data['grid'] = new org_openpsa_widgets_grid($data['mode'] . '_hours_grid', 'local');
        $data['group_options'] = [
            'category' => $this->_l10n->get('category'),
            'task' => $this->_l10n->get('task'),
            'reporter' => $this->_l10n->get('person')
        ];
        $data['action_options'] = $this->prepare_batch_options();

        return $this->show('hours_grid');
    }

    private function prepare_request_data($prefix = '', $suffix = '')
    {
        $this->_request_data['hours'] = $this->qb->execute();

        midcom::get()->head->set_pagetitle($this->_request_data['view_title']);
        $this->add_breadcrumb('', $this->breadcrumb_title);

        $this->_master->populate_view_toolbar($prefix, $suffix);
    }

    /**
     * Set options array for JS, to show the right choosers
     */
    private function prepare_batch_options()
    {
        $task_conf = autocomplete::get_widget_config('task');
        $invoice_conf = autocomplete::get_widget_config('invoice');

        return [
            'none' => ['label' => midcom::get()->i18n->get_string("choose action", "midgard.admin.user")],
            'invoiceable' => [
                'label' => $this->_l10n->get('mark_invoiceable'),
                'value' => true
            ],
            'uninvoiceable' => [
                'label' => $this->_l10n->get('mark_uninvoiceable'),
                'value' => false
            ],
            'task' => [
                'label' => $this->_l10n->get('change_task'),
                'widget_config' => $task_conf
            ],
            'invoice' => [
                'label' => $this->_l10n->get('change_invoice'),
                'widget_config' => $invoice_conf
            ]
        ];
    }
}
