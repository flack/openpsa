<?php
/**
 * @package org.openpsa.reports
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Deliverable reports
 *
 * @package org.openpsa.reports
 */
class org_openpsa_reports_handler_projects_report extends org_openpsa_reports_handler_base
{
    private $_grouping = 'date';
    private $_valid_groupings = [
        'date' => true,
        'person' => true,
    ];

    public function _on_initialize()
    {
        $this->module = 'projects';
    }

    /**
     * Get array of IDs of all tasks in subtree
     */
    private function _expand_task($project_guid)
    {
        $project = org_openpsa_projects_project::get_cached($project_guid);
        $mc = org_openpsa_projects_task_dba::new_collector();
        $mc->add_constraint('project', 'INTREE', $project->id);
        return $mc->get_values('id');
    }

    /**
     * Makes and executes querybuilder for filtering hour_reports
     */
    private function _get_hour_reports()
    {
        $qb_hr = org_openpsa_expenses_hour_report_dba::new_query_builder();
        $qb_hr->add_constraint('date', '<=', (int) $this->_request_data['query_data']['end']);
        $qb_hr->add_constraint('date', '>=', (int) $this->_request_data['query_data']['start']);
        if (   array_key_exists('invoiceable_filter', $this->_request_data['query_data'])
            && $this->_request_data['query_data']['invoiceable_filter'] != -1) {
            $qb_hr->add_constraint('invoiceable', '=', (bool) $this->_request_data['query_data']['invoiceable_filter']);
        }

        $this->_apply_filter($qb_hr, 'invoiced', 'invoice', 0);

        if ($this->_request_data['query_data']['resource'] != 'all') {
            $this->_request_data['query_data']['resource_expanded'] = $this->_expand_resource($this->_request_data['query_data']['resource']);
            $qb_hr->add_constraint('person', 'IN', $this->_request_data['query_data']['resource_expanded']);
        }
        if ($this->_request_data['query_data']['task'] != 'all') {
            $tasks = $this->_expand_task($this->_request_data['query_data']['task']);
            $qb_hr->add_constraint('task', 'IN', $tasks);
        }
        if (   array_key_exists('hour_type_filter', $this->_request_data['query_data'])
            && $this->_request_data['query_data']['hour_type_filter'] != 'builtin:all') {
            $qb_hr->add_constraint('reportType', '=', $this->_request_data['query_data']['hour_type_filter']);
        }
        return $qb_hr->execute();
    }

    private function _apply_filter(midcom_core_query $qb, $name, $field, $value)
    {
        $filter = $name . '_filter';
        if (array_key_exists($filter, $this->_request_data['query_data'])) {
            debug_add($filter . ' detected, raw value: ' . $this->_request_data['query_data'][$filter]);
            if ($this->_request_data['query_data'][$filter] != -1) {
                if ((int) $this->_request_data['query_data'][$filter]) {
                    debug_add($filter . ' parsed as ONLY, adding constraint');
                    $qb->add_constraint($field, '<>', $value);
                } else {
                    debug_add($filter . ' parsed as only NOT, adding constraint');
                    $qb->add_constraint($field, '=', $value);
                }
            } else {
                debug_add($filter . ' parsed as BOTH, do not add any constraints');
            }
        }
    }

    private function _sort_rows()
    {
        usort($this->_request_data['report']['rows'], ['self', '_sort_by_key']);
        foreach ($this->_request_data['report']['rows'] as &$group) {
            if (!empty($group['rows'])) {
                usort($group['rows'], ['self', '_sort_by_key']);
            }
        }
    }

    private static function _sort_by_key($a, $b)
    {
        $ap = $a['sort'];
        $bp = $b['sort'];
        if (is_numeric($ap)) {
            if ($ap == $bp) {
                return 0;
            }
            return ($ap > $bp) ? 1 : -1;
        }
        if (is_string($ap)) {
            return strnatcmp($ap, $bp);
        }
        return 0;
    }

    private function _analyze_raw_hours()
    {
        if (empty($this->_request_data['raw_results']['hr'])) {
            debug_add('Hour reports array not found', MIDCOM_LOG_WARN);
            return;
        }
        $formatter = $this->_l10n->get_formatter();
        foreach ($this->_request_data['raw_results']['hr'] as $hour) {
            $row = [];
            try {
                $row['person'] = org_openpsa_contacts_person_dba::get_cached($hour->person);
            } catch (midcom_error $e) {
                $e->log();
                continue;
            }
            $row['hour'] = $hour;
            $row['task'] = org_openpsa_projects_task_dba::get_cached($hour->task);

            // Default (should work for almost every grouping) is to sort rows by the hour report date
            $row['sort'] = $row['hour']->date;
            //Determine our group
            if ($this->_grouping == 'date') {
                $matching = 'date:' . date('Ymd', $row['hour']->date);
                $sort = date('Ymd', $row['hour']->date);
                $title = $formatter->date($row['hour']->date);
            } else {
                $matching = 'person:' . $row['person']->guid;
                $sort = $row['person']->rname;
                $title = $row['person']->rname;
            }
            $this->add_to_group($row, $matching, $sort, $title);

            //Place data to report
            $this->_request_data['report']['total_hours'] += $hour->hours;
        }
    }

    private function add_to_group($new_row, $matching, $sort, $title)
    {
        $rows =& $this->_request_data['report']['rows'];
        if (array_key_exists($matching, $rows)) {
            $rows[$matching]['rows'][] = $new_row;
            $rows[$matching]['total_hours'] += $new_row['hour']->hours;
        } else {
            $rows[$matching] = [
                'sort' => $sort,
                'title' => $title,
                'rows' => [$new_row],
                'total_hours' => $new_row['hour']->hours
            ];
        }
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $data The local request data.
     */
    public function _show_generator($handler_id, array &$data)
    {
        //Mangling if report wants to do it (done here to have style context, otherwise MidCOM will not like us.
        midcom_show_style('projects_report-basic-mangle-query');
        //Handle grouping
        if (!empty($this->_request_data['query_data']['grouping'])) {
            if (array_key_exists($this->_request_data['query_data']['grouping'], $this->_valid_groupings)) {
                debug_add('Setting grouping to: ' . $this->_request_data['query_data']['grouping']);
                $this->_grouping = $this->_request_data['query_data']['grouping'];
            } else {
                debug_add(sprintf("\"%s\" is not a valid grouping, keeping default", $this->_request_data['query_data']['grouping']), MIDCOM_LOG_WARN);
            }
        }

        // Put grouping to request data
        $this->_request_data['grouping'] = $this->_grouping;

        //Get our results
        $results_hr = $this->_get_hour_reports();

        //For debugging and sensible passing of data
        $this->_request_data['raw_results'] = ['hr' => $results_hr];
        //TODO: Mileages, expenses

        $this->_request_data['report'] = ['rows' => [], 'total_hours' => 0];

        $this->_analyze_raw_hours();
        $this->_sort_rows();

        //TODO: add other report types when supported
        if (empty($this->_request_data['raw_results']['hr'])) {
            midcom_show_style('projects_report-basic-noresults');
            return;
        }

        //Start actual display

        //Indented to make style flow clearer
        midcom_show_style('projects_report-basic-start');
        midcom_show_style('projects_report-basic-header');
        $this->_show_generator_group($this->_request_data['report']['rows']);
        midcom_show_style('projects_report-basic-totals');
        midcom_show_style('projects_report-basic-footer');
        midcom_show_style('projects_report-basic-end');
    }

    public function _show_generator_group(array $data)
    {
        foreach ($data as $group) {
            $this->_request_data['current_group'] = $group;
            //Indented to make style flow clearer
            midcom_show_style('projects_report-basic-group-header');
            foreach ($group['rows'] as $row) {
                $this->_request_data['current_row'] = $row;
                midcom_show_style('projects_report-basic-item');
            }
            midcom_show_style('projects_report-basic-group-totals');
            midcom_show_style('projects_report-basic-group-footer');
        }
    }
}
