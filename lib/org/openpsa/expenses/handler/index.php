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
        midcom::get('auth')->require_valid_user();

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

        $date->modify('+7 days -1 second');
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

        $this->_master->add_list_filter($hours_mc);
        $hours_mc->add_constraint('date', '>=', $data['week_start']);
        $hours_mc->add_constraint('date', '<=', $data['week_end']);
        $hours_mc->add_order('task');
        $hours_mc->add_order('date');
        $hours_mc->execute();

        $data['rows'] = $this->_get_sorted_reports($hours_mc);

        $this->_populate_toolbar($previous_week, $next_week);

        org_openpsa_widgets_grid::add_head_elements();
        $this->add_stylesheet(MIDCOM_STATIC_URL . "/org.openpsa.expenses/expenses.css");

        $this->add_breadcrumb('', sprintf($this->_l10n->get("expenses in week %s"), strftime("%V %G", $this->_request_data['week_start'])));

        midcom::get('head')->set_pagetitle(sprintf($this->_l10n->get("expenses in week %s"), strftime("%V %G", $this->_request_data['week_start'])));
    }

    private function _populate_toolbar($previous_week, $next_week)
    {
        $week_start = strftime('%Y-%m-%d', $this->_request_data['week_start']);
        $week_end = strftime('%Y-%m-%d', $this->_request_data['week_end']);
        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => 'hours/?date[from]=' . $week_start . '&amp;date[to]=' . $week_end,
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('list view'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/view.png',
            )
        );

        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => $previous_week . "/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('previous'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/back.png',
            )
        );
        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => $next_week . "/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('next'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/next.png',
            )
        );
    }

    /**
     * Sort the reports by task and day
     */
    private function _get_sorted_reports($hours_mc)
    {
        $reports = array();
        $hours = $hours_mc->list_keys();
        $prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);

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
            $person = $hours_mc->get_subkey($guid, 'person');
            $date = $hours_mc->get_subkey($guid, 'date');

            $date_identifier = date('Y-m-d', $date);
            $row_identifier = $task->id . '-' .  $person;

            if (!isset($reports[$row_identifier]))
            {
                $reports[$row_identifier] = array
                (
                    $date_identifier => 0,
                    'task' => "<a href=\"{$prefix}hours/task/{$task->guid}/\">" . $task->get_label() . "</a>",
                    'task_index' => $task->get_label()
                );

                try
                {
                    $person = org_openpsa_contacts_person_dba::get_cached($person);
                    $reports[$row_identifier]['person'] = $person->name;
                }
                catch (midcom_error $e)
                {
                    $reports[$row_identifier]['person'] = $this->_l10n->get('no person');
                }
            }

            if (!isset($reports[$row_identifier][$date_identifier]))
            {
                $reports[$row_identifier][$date_identifier] = 0;
            }

            $reports[$row_identifier][$date_identifier] += $hours_mc->get_subkey($guid, 'hours');
        }

        return array_values($reports);
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
    }
}
?>