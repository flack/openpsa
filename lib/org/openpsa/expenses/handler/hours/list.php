<?php
/**
 * @package org.openpsa.expenses
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\helper\autocomplete;
use midcom\grid\grid;
use midcom\datamanager\schemadb;
use Symfony\Component\HttpFoundation\Response;

/**
 * @package org.openpsa.expenses
 */
class org_openpsa_expenses_handler_hours_list extends midcom_baseclasses_components_handler
{
    use org_openpsa_expenses_handler;

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

        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/midcom.grid/FileSaver.js');
        autocomplete::add_head_elements();
        midcom::get()->uimessages->add_head_elements();
    }

    /**
     * The handler for the list view
     */
    public function _handler_list(array &$data)
    {
        $data['mode'] = 'full';
        $data['view_title'] = $data['l10n']->get('hour reports');
        $this->breadcrumb_title = $data['view_title'];
        $this->add_list_filter($this->qb, true);
        $this->prepare_request_data();

        return $this->show_list($data);
    }

    /**
     * The handler for the task list view
     */
    public function _handler_project(string $guid, array &$data)
    {
        $project = new org_openpsa_projects_project($guid);
        $this->qb->add_constraint('task.project', '=', $project->id);

        $data['mode'] = 'project';
        $data['view_title'] = sprintf($data['l10n']->get("list_hours_project %s"), $project->title);

        $this->breadcrumb_title = $project->title;
        $this->add_list_filter($this->qb, true);
        $this->prepare_request_data();

        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        if ($projects_url = $siteconfig->get_node_full_url('org.openpsa.projects')) {
            $this->_view_toolbar->add_item([
                MIDCOM_TOOLBAR_URL => $projects_url . "project/{$guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('show project'),
                MIDCOM_TOOLBAR_GLYPHICON => 'tasks',
                MIDCOM_TOOLBAR_ACCESSKEY => 'p',
            ]);
        }

        return $this->show_list($data);
    }

    /**
     * The handler for the task list view
     */
    public function _handler_task(string $guid, array &$data)
    {
        $task = new org_openpsa_projects_task_dba($guid);
        $project = new org_openpsa_projects_project($task->project);
        $this->qb->add_constraint('task', '=', $task->id);

        $data['mode'] = 'task';
        $data['view_title'] = sprintf($data['l10n']->get("list_hours_task %s"), $task->title);

        $this->add_breadcrumb($this->router->generate('list_hours_project', ['guid' => $project->guid]), $project->title);
        $this->breadcrumb_title = $task->title;

        $this->prepare_request_data('task/', $guid . '/');
        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        if ($projects_url = $siteconfig->get_node_full_url('org.openpsa.projects')) {
            $this->_view_toolbar->add_item([
                MIDCOM_TOOLBAR_URL => $projects_url . "task/{$guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('show task'),
                MIDCOM_TOOLBAR_GLYPHICON => 'calendar-check-o',
                MIDCOM_TOOLBAR_ACCESSKEY => 'g',
            ]);
        }

        return $this->show_list($data);
    }

    /**
     * The handler for the invoice list view
     */
    public function _handler_invoice(string $guid, array &$data)
    {
        $invoice = new org_openpsa_invoices_invoice_dba($guid);
        $this->qb->add_constraint('invoice', '=', $invoice->id);

        $data['mode'] = 'invoice';
        $data['view_title'] = sprintf($data['l10n']->get("list_hours_invoice %s"), $invoice->get_label());
        $this->breadcrumb_title = $data['view_title'];

        $this->prepare_request_data('invoice/', $guid . '/');
        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        if ($invoices_url = $siteconfig->get_node_full_url('org.openpsa.invoices')) {
            $this->_view_toolbar->add_item([
                MIDCOM_TOOLBAR_URL => $invoices_url . "invoice/{$guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('show invoice'),
                MIDCOM_TOOLBAR_GLYPHICON => 'file-text-o',
                MIDCOM_TOOLBAR_ACCESSKEY => 'g',
            ]);
        }
        return $this->show_list($data);
    }

    private function show_list(array &$data) : Response
    {
        $data['grid'] = new grid($data['mode'] . '_hours_grid', 'local');
        $data['group_options'] = [
            'category' => $this->_l10n->get('category'),
            'task' => $this->_l10n->get('task'),
            'reporter' => $this->_l10n->get('person')
        ];
        $data['action_options'] = $this->prepare_batch_options();

        return $this->show('hours_grid');
    }

    private function prepare_request_data(string $prefix = '', string $suffix = '')
    {
        $this->_request_data['hours'] = $this->qb->execute();

        midcom::get()->head->set_pagetitle($this->_request_data['view_title']);
        $this->add_breadcrumb('', $this->breadcrumb_title);

        $schemadb = schemadb::from_path($this->_config->get('schemadb_hours'));
        $workflow = $this->get_workflow('datamanager');
        foreach ($schemadb->all() as $name => $schema) {
            $create_url = "hours/create/{$prefix}{$name}/{$suffix}";
            $this->_view_toolbar->add_item($workflow->get_button($create_url, [
                MIDCOM_TOOLBAR_LABEL => sprintf($this->_l10n_midcom->get('create %s'), $this->_l10n->get($schema->get('description'))),
                MIDCOM_TOOLBAR_GLYPHICON => 'plus',
            ]));
        }
    }

    /**
     * Set options array for JS, to show the right choosers
     */
    private function prepare_batch_options() : array
    {
        $task_conf = autocomplete::get_widget_config('task');
        $invoice_conf = autocomplete::get_widget_config('invoice');

        return [
            'none' => ['label' => $this->_i18n->get_string("choose action", "midgard.admin.user")],
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
