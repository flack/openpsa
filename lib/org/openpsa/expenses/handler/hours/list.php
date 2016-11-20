<?php
/**
 * @package org.openpsa.expenses
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * This is a URL handler class for org.openpsa.expenses
 *
 * The midcom_baseclasses_components_handler class defines a bunch of helper vars
 *
 * @see midcom_baseclasses_components_handler
 * @package org.openpsa.expenses
 */
class org_openpsa_expenses_handler_hours_list extends midcom_baseclasses_components_handler
{
    /**
     * The reporter cache
     *
     * @var Array
     */
    private $reporters = array();

    /**
     * The handler for the list view
     *
     * @param mixed $handler_id the array key from the request array
     * @param array $args the arguments given to the handler
     * @param array &$data The local request data.
     */
    public function _handler_list($handler_id, array $args, array &$data)
    {
        midcom::get()->auth->require_valid_user();

        // List hours
        $qb = org_openpsa_projects_hour_report_dba::new_query_builder();

        $mode = 'full';

        //url for batch_handler
        $this->_request_data['action_target_url'] = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX) . "hours/task/batch/";

        switch ($handler_id) {
            case 'list_hours':
                $this->_master->add_list_filter($qb);

                $data['view_title'] = $data['l10n']->get('hour reports');
                $data['breadcrumb_title'] = $data['view_title'];
                break;

            case 'list_hours_task':
                $this->_master->add_list_filter($qb);
                // Fallthrough
            case 'list_hours_task_all':
                $task = new org_openpsa_projects_task_dba($args[0]);
                $qb->add_constraint('task', '=', $task->id);

                $mode = 'simple';
                $data['view_title'] = sprintf($data['l10n']->get($handler_id . " %s"), $task->title);
                $data['breadcrumb_title'] = $task->get_label();

                $siteconfig = org_openpsa_core_siteconfig::get_instance();
                if ($projects_url = $siteconfig->get_node_full_url('org.openpsa.projects')) {
                    $this->_view_toolbar->add_item(
                        array(
                            MIDCOM_TOOLBAR_URL => $projects_url . "task/{$task->guid}/",
                            MIDCOM_TOOLBAR_LABEL => sprintf($this->_l10n->get('show task %s'), $task->title),
                            MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/jump-to.png',
                            MIDCOM_TOOLBAR_ACCESSKEY => 'g',
                        )
                    );
                }

                break;
        }

        $qb->add_order('date', 'DESC');
        $data['hours'] = $qb->execute();

        $this->_load_hour_data($data['hours']);

        $data['mode'] = $mode;
        $data['qb'] = $qb;

        org_openpsa_widgets_grid::add_head_elements();
        midcom_helper_datamanager2_widget_autocomplete::add_head_elements();
        org_openpsa_widgets_contact::add_head_elements();

        midcom::get()->head->set_pagetitle($data['view_title']);
        $this->add_breadcrumb('', $data['breadcrumb_title']);
    }

    /**
     * Load the data linked to the hour reports
     *
     * @param array $hours the hour reports we're working with
     */
    private function _load_hour_data(array $hours)
    {
        $reports = array(
            'invoiceable' => array(
                'hours' => 0,
                'reports' => array(),
            ),
            'uninvoiceable' => array(
                'hours' => 0,
                'reports' => array(),
            ),
            'invoiced' => array(
                'hours' => 0,
                'reports' => array(),
            ),
        );

        foreach ($hours as $report) {
            if (!array_key_exists($report->person, $this->reporters)) {
                try {
                    $reporter = new midcom_db_person($report->person);
                    $reporter_card = new org_openpsa_widgets_contact($reporter);
                    $this->reporters[$report->person] = array(
                        'card' => $reporter_card->show_inline(),
                        'rname' => $reporter->rname
                    );
                } catch (midcom_error $e) {
                    $e->log();
                    $this->reporters[$report->person] = array('card' => '', 'rname' => '');
                }
            }

            if ($report->invoice) {
                $category = 'invoiced';
            } elseif ($report->invoiceable) {
                $category = 'invoiceable';
            } else {
                $category = 'uninvoiceable';
            }
            $reports[$category]['reports'][] = $report;
            $reports[$category]['hours'] += $report->hours;
        }
        $this->_request_data['sorted_reports'] = $reports;
    }

    /**
     * This function does the output.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_list($handler_id, array &$data)
    {
        $data['reporters'] = $this->reporters;

        midcom_show_style('hours_list_top');

        foreach ($data['sorted_reports'] as $status => $reports) {
            if (sizeof($reports['reports']) == 0) {
                continue;
            }
            $data['subheading'] = $this->_l10n->get($status . ' reports');
            $data['status'] = $status;
            $data['action_options'] = $this->_prepare_batch_options($status === 'uninvoiceable');
            $data['reports'] = $reports;

            midcom_show_style('hours_grid');
        }
        midcom_show_style('hours_list_bottom');
    }

    /**
     * Set options array for JS, to show the right choosers
     */
    private function _prepare_batch_options($status)
    {
        $task_conf = midcom_helper_datamanager2_widget_autocomplete::get_widget_config('task');
        $invoice_conf = midcom_helper_datamanager2_widget_autocomplete::get_widget_config('invoice');

        return array(
            'none' => array('label' => midcom::get()->i18n->get_string("choose action", "midgard.admin.user")),
            'invoiceable' => array(
                'label' => $this->_l10n->get('mark_' . ($status ? 'invoiceable' : 'uninvoiceable')),
                'value' => $status
            ),
            'task' => array(
                'label' => $this->_l10n->get('change_task'),
                'widget_config' => $task_conf
            ),
            'invoice' => array(
                'label' => $this->_l10n->get('change_invoice'),
                'widget_config' => $invoice_conf
            )
        );
    }
}
