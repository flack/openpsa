<?php
/**
 * @package org.openpsa.expenses
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: index.php 26520 2010-07-07 13:49:07Z gudd $
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
     * Simple default constructor.
     */
    function __construct()
    {
        parent::__construct();
    }

    /**
     * The handler for the index view.
     *
     * @param mixed $handler_id the array key from the request array
     * @param array $args the arguments given to the handler
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_index ($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_valid_user();

        if (isset($args[0]))
        {
            $data['requested_time'] = $args[0];
        }

        org_openpsa_helpers::calculate_week($data);

        $hours_mc = org_openpsa_projects_hour_report_dba::new_collector('sitegroup', $_MIDGARD['sitegroup']);
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

        $hours = $hours_mc->list_keys();

        // Sort the reports by task and day
        $tasks = array();
        foreach ($hours as $guid => $empty)
        {
            $task_id = $hours_mc->get_subkey($guid, 'task');
            $task = org_openpsa_projects_task_dba::get_cached($task_id);
            if (!$task->guid)
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

        $data['tasks'] =& $tasks;

        $prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
        $previous_week = $data['requested_time'] - 3600 * 24 * 7;
        $next_week = $data['requested_time'] + 3600 * 24 * 7;

        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "{$prefix}" . $previous_week . "/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('previous'),
                MIDCOM_TOOLBAR_HELPTEXT => null,
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/up.png',
                MIDCOM_TOOLBAR_ENABLED => true,
            )
        );
        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "{$prefix}" . $next_week . "/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('next'),
                MIDCOM_TOOLBAR_HELPTEXT => null,
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/down.png',
                MIDCOM_TOOLBAR_ENABLED => true,
            )
        );

        $_MIDCOM->add_link_head
        (
            array
            (
                'rel' => 'stylesheet',
                'type' => 'text/css',
                'href' => MIDCOM_STATIC_URL . "/org.openpsa.expenses/expenses.css",
            )
        );

        $_MIDCOM->add_link_head
        (
            array
            (
                'rel' => 'stylesheet',
                'type' => 'text/css',
                'href' => MIDCOM_STATIC_URL . "/org.openpsa.expenses/dropdown-check-list.0.9/css/ui.dropdownchecklist.css",
            )
        );

        $ui_version = $GLOBALS['midcom_config']['jquery_ui_version'];
        $min_version = '1.7.2';

        $_MIDCOM->enable_jquery();

        //check if right ui-version for dropdownchecklist is available
        if(version_compare($min_version , $ui_version , "<="))
        {
            $_MIDCOM->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.core.min.js');
            $_MIDCOM->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.widget.min.js');
            $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . '/org.openpsa.expenses/dropdown-check-list.0.9/js/ui.dropdownchecklist-min.js');
        }

        $this->_update_breadcrumb_line();

        $_MIDCOM->set_pagetitle(sprintf($this->_l10n->get("expenses in week %s"), strftime("%V %Y", $this->_request_data['requested_time'])));

        return true;
    }

    /**
     * Helper, updates the context so that we get a complete breadcrumb line towards the current
     * location.
     */
    private function _update_breadcrumb_line()
    {
        $tmp = array();

        $tmp[] = array
        (
            MIDCOM_NAV_URL => "",
            MIDCOM_NAV_NAME => sprintf($this->_l10n->get("expenses in week %s"), strftime("%V %Y", $this->_request_data['requested_time'])),
        );

        $_MIDCOM->set_custom_context_data('midcom.helper.nav.breadcrumb', $tmp);
    }

    /**
     * This function does the output.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    function _show_index($handler_id, &$data)
    {
        midcom_show_style('person_filter');
        midcom_show_style('expenses_index_header');
        midcom_show_style('hours_week');
        midcom_show_style('expenses_index_footer');
    }
}
?>