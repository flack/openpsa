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
     * The task cache
     *
     * @var Array
     */
    private $tasks = array();

    /**
     * Prepare a paged query builder for listing hour reports
     */
    function &_prepare_qb()
    {
        $qb = org_openpsa_projects_hour_report_dba::new_query_builder();
        $this->_request_data['qb'] =& $qb;
        return $qb;
    }

    /**
     * The handler for the list view
     *
     * @param mixed $handler_id the array key from the request array
     * @param array $args the arguments given to the handler
     * @param Array &$data The local request data.
     */
    public function _handler_list($handler_id, array $args, array &$data)
    {
        $_MIDCOM->auth->require_valid_user();

        // List hours
        $qb = $this->_prepare_qb();

        $mode = 'full';

        $filter_array = array
        (
            "person"
        );
        //url for batch_handler
        $this->_request_data['action_target_url'] = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX) . "hours/task/batch/";

        switch ($handler_id)
        {
            case 'list_hours_between':
                $person_filter = new org_openpsa_core_filter($filter_array, $qb);
                $data['filter_persons'] = $person_filter->list_filter("person");
                // Fallthrough
            case 'list_hours_between_all':
                $start_time = @strtotime($args[0]);
                $end_time = @strtotime($args[1]);
                if (   $start_time == -1
                    || $end_time == -1)
                {
                    throw new midcom_error('Failed to generate start and end times from ' . $args[0] . ', ' . $args[1]);
                }
                $qb->add_constraint('date', '>=', $start_time);
                $qb->add_constraint('date', '<=', $end_time);

                $data['view_title'] = sprintf($data['l10n']->get('hour reports between %s and %s'), strftime("%x", $start_time), strftime("%x", $end_time));
                $data['breadcrumb_title'] = $data['view_title'];
                break;

            case 'list_hours_task':
                $person_filter = new org_openpsa_core_filter($filter_array, $qb);
                $data['filter_persons'] = $person_filter->list_filter("person");
                // Fallthrough
            case 'list_hours_task_all':
                $task = new org_openpsa_projects_task_dba($args[0]);
                $qb->add_constraint('task', '=', $task->id);

                $mode = 'simple';
                $data['view_title'] = sprintf($data['l10n']->get($handler_id . " %s"), $task->title);
                $data['breadcrumb_title'] = $task->get_label();

                $siteconfig = org_openpsa_core_siteconfig::get_instance();
                $projects_url = $siteconfig->get_node_full_url('org.openpsa.projects');

                if ($projects_url)
                {
                    $this->_view_toolbar->add_item
                    (
                        array
                        (
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

        $data['sorted_reports'] = array
        (
            'invoiceable' => array
            (
                'hours' => 0,
                'reports' => array(),
            ),
            'uninvoiceable' => array
            (
                'hours' => 0,
                'reports' => array(),
            ),
            'invoiced' => array
            (
                'hours' => 0,
                'reports' => array(),
            ),
        );
        $this->load_hour_data($data['hours'], $data['sorted_reports']);

        $data['mode'] =& $mode;
        $data['tasks'] =& $this->tasks;

        org_openpsa_widgets_grid::add_head_elements();
        midcom_helper_datamanager2_widget_autocomplete::add_head_elements();
        org_openpsa_widgets_contact::add_head_elements();
        $this->_add_filter_widget();

        $_MIDCOM->set_pagetitle($data['view_title']);
        $this->add_breadcrumb('', $data['breadcrumb_title']);
    }

    private function _add_filter_widget()
    {
        if (!array_key_exists("filter_persons", $this->_request_data))
        {
            return;
        }

        //css & js needed for widget
        $_MIDCOM->enable_jquery();

        $this->add_stylesheet(MIDCOM_STATIC_URL . "/org.openpsa.expenses/dropdown-check-list.0.9/css/ui.dropdownchecklist.css");
        $_MIDCOM->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.core.min.js');
        $_MIDCOM->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.widget.min.js');
        $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . '/org.openpsa.expenses/dropdown-check-list.0.9/js/ui.dropdownchecklist-min.js');
    }


    /**
     * Helper to load the data linked to the hour reports
     *
     * @param array &$hours the hour reports we're working with
     * @param array &$reports The sorted reports array
     */
    private function load_hour_data(&$hours, &$reports)
    {
        $prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
        foreach ($hours as $report)
        {
            if (!array_key_exists($report->person, $this->reporters))
            {
                try
                {
                    $reporter = new midcom_db_person($report->person);
                    $reporter_card = new org_openpsa_widgets_contact($reporter);
                    $this->reporters[$report->person] = $reporter_card->show_inline();
                }
                catch (midcom_error $e)
                {
                    $e->log();
                    $this->reporters[$report->person] = '';
                }
            }

            if (!array_key_exists($report->task, $this->tasks))
            {
                $task = new org_openpsa_projects_task_dba($report->task);
                $task_html = "<a href=\"{$prefix}hours/task/{$task->guid}/\">" . $task->get_label() . "</a>";
                $this->tasks[$report->task] = $task_html;
            }

            switch (true)
            {
                case ($report->invoice):
                    $reports['invoiced']['reports'][] = $report;
                    $reports['invoiced']['hours'] += $report->hours;
                    break;
                case ($report->invoiceable):
                    $reports['invoiceable']['reports'][] = $report;
                    $reports['invoiceable']['hours'] += $report->hours;
                    break;
                default:
                    $reports['uninvoiceable']['reports'][] = $report;
                    $reports['uninvoiceable']['hours'] += $report->hours;
                    break;
            }
        }
    }

    /**
     * This function does the output.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_list($handler_id, array &$data)
    {
        $data['reporters'] =& $this->reporters;

        midcom_show_style('hours_list_top');

        foreach ($data['sorted_reports'] as $status => $reports)
        {
            if (sizeof($reports['reports']) == 0)
            {
                continue;
            }
            $data['subheading'] = $this->_l10n->get($status . ' reports');
            $data['status'] = $status;
            $data['action_options'] = $this->_prepare_batch_options();

            //add different options for different statuses
            switch ($status)
            {
                case 'invoiceable':
                    $data['action_options']['mark_uninvoiceable'] = array('label' => $this->_l10n->get('mark_uninvoiceable'));
                    break;
                case 'uninvoiceable':
                    $data['action_options']['mark_invoiceable'] =  array('label' => $this->_l10n->get('mark_invoiceable'));;
                    break;
            }
            $data['reports'] = $reports;

            midcom_show_style('hours_grid');
        }
        midcom_show_style('hours_list_bottom');
    }

    /**
     * Set options array for JS, to show the right choosers
     */
    private function _prepare_batch_options()
    {
        // Get url to search handler
        $handler_url = midcom_connection::get_url('self') . 'midcom-exec-midcom.helper.datamanager2/autocomplete_handler.php';

        $widget_config = midcom_baseclasses_components_configuration::get('midcom.helper.datamanager2', 'config')->get('clever_classes');
        $task_conf = $widget_config['task'];
        $task_conf['handler_url'] = $handler_url;

        //Make sure we have the needed constants
        midcom::get('componentloader')->load('org.openpsa.projects');
        $task_conf['constraints'][] = array
        (
            'field' => 'orgOpenpsaObtype',
            'op'    => '=',
            'value' => ORG_OPENPSA_OBTYPE_TASK,
        );

        $invoice_conf = $widget_config['invoice'];
        $invoice_conf['handler_url'] = $handler_url;

        $options = array
        (
            'none' => array('label' => $_MIDCOM->i18n->get_string("choose action", "midcom.admin.user")),
            'change_task' => array
            (
                'label' => $this->_l10n->get('change_task'),
                'widget_config' => $task_conf
            ),
            'change_invoice' => array
            (
                'label' => $this->_l10n->get('change_invoice'),
                'widget_config' => $invoice_conf
            )
        );
        return $options;
    }
}
?>