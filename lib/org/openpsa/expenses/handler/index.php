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
class org_openpsa_expenses_handler_index  extends midcom_baseclasses_components_handler
{
    /**
     * The handler for the index view.
     *
     * @param mixed $handler_id the array key from the request array
     * @param array $args the arguments given to the handler
     * @param Array &$data The local request data.
     */
    public function _handler_index ($handler_id, array $args, array &$data)
    {
        $_MIDCOM->auth->require_valid_user();

        if (isset($args[0]))
        {
            $data['requested_time'] = $args[0];
        }
        else
        {
            $data['requested_time'] = date('Y-m-d');
        }

        $date = new DateTime($data['requested_time']);
        $offset = $date->format('N') - 1;

        $date->modify('-' . $offset . ' days');
        $data['week_start'] = (int) $date->format('U');

        $date->modify('+7 days');
        $data['week_end'] = (int) $date->format('U');

        $date->modify('+1 day');
        $next_week = $date->format('Y-m-d');

        $date->modify('-14 days');
        $previous_week = $date->format('Y-m-d');

        $hours_mc = org_openpsa_projects_hour_report_dba::new_collector('metadata.deleted', false);
        $hours_mc->add_value_property('task');
        $hours_mc->add_value_property('hours');
        $hours_mc->add_value_property('date');
        $hours_mc->add_value_property('person');

        //array with filter options
        $filters = array
        (
            "person"
        );
        $person_filter = new org_openpsa_core_filter($filters, $hours_mc);
        $this->_request_data["filter_persons"] = $person_filter->list_filter("person");

        $hours_mc->add_constraint('date', '>=', $data['week_start']);
        $hours_mc->add_constraint('date', '<=', $data['week_end']);
        $hours_mc->add_order('task');
        $hours_mc->add_order('date');
        $hours_mc->execute();

        $data['tasks'] = $this->_get_sorted_reports($hours_mc);

        $this->_populate_toolbar($previous_week, $next_week);

        $this->add_stylesheet(MIDCOM_STATIC_URL . "/org.openpsa.expenses/expenses.css");
        $this->add_stylesheet(MIDCOM_STATIC_URL . "/org.openpsa.expenses/dropdown-check-list.0.9/css/ui.dropdownchecklist.css");

        $_MIDCOM->enable_jquery();
        $_MIDCOM->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.core.min.js');
        $_MIDCOM->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.widget.min.js');
        $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . '/org.openpsa.expenses/dropdown-check-list.0.9/js/ui.dropdownchecklist-min.js');

        $this->add_breadcrumb('', sprintf($this->_l10n->get("expenses in week %s"), strftime("%V %Y", $this->_request_data['week_start'])));

        $_MIDCOM->set_pagetitle(sprintf($this->_l10n->get("expenses in week %s"), strftime("%V %Y", $this->_request_data['week_start'])));
    }

    private function _populate_toolbar($previous_week, $next_week)
    {
        $prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);

        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "{$prefix}" . $previous_week . "/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('previous'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/up.png',
            )
        );
        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "{$prefix}" . $next_week . "/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('next'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/down.png',
            )
        );
    }

    /**
     * Sort the reports by task and day
     */
    private function _get_sorted_reports($hours_mc)
    {
        $tasks = array();
        $hours = $hours_mc->list_keys();

        foreach ($hours as $guid => $empty)
        {
            $task_id = $hours_mc->get_subkey($guid, 'task');
            try
            {
                $task = org_openpsa_projects_task_dba::get_cached($task_id);
            }
            catch (midcom_error $e)
            {
                // Task couldn't be loaded, probably because of ACL
                continue;
            }
            $date = $hours_mc->get_subkey($guid, 'date');
            $report_hours = $hours_mc->get_subkey($guid, 'hours');
            $person = $hours_mc->get_subkey($guid, 'person');
            if (!isset($tasks[$task_id]))
            {
                $tasks[$task_id] = array
                (
                    'persons' => array(),
                    'task_object' => $task,
                );
            }
            if (!isset($tasks[$task_id]['persons'][$person]))
            {
                $tasks[$task_id]['persons'][$person] = array();
            }

            $date_identifier = date('Y-m-d', $date);
            if (!isset($tasks[$task_id][$date_identifier]))
            {
                $tasks[$task_id][$date_identifier] = 0;
            }

            if (!isset($tasks[$task_id]['persons'][$person][$date_identifier]))
            {
                $tasks[$task_id]['persons'][$person][$date_identifier] = 0;
            }

            $tasks[$task_id][$date_identifier] += $report_hours;
            $tasks[$task_id]['persons'][$person][$date_identifier] += $report_hours;
        }
        return $tasks;
    }

    /**
     * This function does the output.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_index($handler_id, array &$data)
    {
        midcom_show_style('expenses_index_header');
        midcom_show_style('hours_week');
        midcom_show_style('expenses_index_footer');
    }
}
?>