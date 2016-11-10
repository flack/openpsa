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
    private $_valid_groupings = array
    (
        'date' => true,
        'person' => true,
    );

    public function _on_initialize()
    {
        $this->module = 'projects';
        $this->_initialize_datamanager();
    }

    /**
     * Get array of IDs of all tasks in subtree
     */
    private function _expand_task($project_guid)
    {
        $project = org_openpsa_projects_project::get_cached($project_guid);
        $mc = org_openpsa_projects_task_dba::new_collector('metadata.deleted', false);
        $mc->add_constraint('project', 'INTREE', $project->id);
        return $mc->get_values('id');
    }

    /**
     * Makes and executes querybuilder for filtering hour_reports
     */
    private function _get_hour_reports()
    {
        //Create queries to get data

        $qb_hr = org_openpsa_projects_hour_report_dba::new_query_builder();
        $qb_hr->add_constraint('date', '<=', (int) $this->_request_data['query_data']['end']);
        $qb_hr->add_constraint('date', '>=', (int) $this->_request_data['query_data']['start']);
        if (   array_key_exists('invoiceable_filter', $this->_request_data['query_data'])
            && $this->_request_data['query_data']['invoiceable_filter'] != -1)
        {
            $qb_hr->add_constraint('invoiceable', '=', (bool) $this->_request_data['query_data']['invoiceable_filter']);
        }

        $this->_apply_filter($qb_hr, 'approved', 'metadata.isapproved', false);
        $this->_apply_filter($qb_hr, 'invoiced', 'invoice', 0);

        if ($this->_request_data['query_data']['resource'] != 'all')
        {
            $this->_request_data['query_data']['resource_expanded'] = $this->_expand_resource($this->_request_data['query_data']['resource']);
            $qb_hr->add_constraint('person', 'IN', $this->_request_data['query_data']['resource_expanded']);
        }
        if ($this->_request_data['query_data']['task'] != 'all')
        {
            $tasks = $this->_expand_task($this->_request_data['query_data']['task']);
            $qb_hr->add_constraint('task', 'IN', $tasks);
        }
        if (   array_key_exists('hour_type_filter', $this->_request_data['query_data'])
            && $this->_request_data['query_data']['hour_type_filter'] != 'builtin:all')
        {
            $qb_hr->add_constraint('reportType', '=', $this->_request_data['query_data']['hour_type_filter']);
        }
        return $qb_hr->execute();
    }

    private function _apply_filter(midcom_core_query $qb, $name, $field, $value)
    {
        $filter = $name . '_filter';
        if (array_key_exists($filter, $this->_request_data['query_data']))
        {
            debug_add($filter . ' detected, raw value: ' . $this->_request_data['query_data'][$filter]);
            if ($this->_request_data['query_data'][$filter] != -1)
            {
                if ((int) $this->_request_data['query_data'][$filter])
                {
                    debug_add($filter . ' parsed as ONLY, adding constraint');
                    $qb->add_constraint($field, '<>', $value);
                }
                else
                {
                    debug_add($filter . ' parsed as only NOT, adding constraint');
                    $qb->add_constraint($field, '=', $value);
                }
            }
            else
            {
                debug_add($filter . ' parsed as BOTH, do not add any constraints');
            }
        }
    }

    private function _sort_rows_recursive(array &$data)
    {
        usort($data, array('self', '_sort_by_key'));
        foreach ($data as $row)
        {
            if (!empty($row['is_group']))
            {
                // Is group, recurse
                $this->_sort_rows_recursive($row['rows']);
            }
        }
    }

    private static function _sort_by_key($a, $b)
    {
        $ap = (array_key_exists('sort', $a)) ? $a['sort'] : false;
        $bp = (array_key_exists('sort', $b)) ? $b['sort'] : false;
        if (is_numeric($ap))
        {
            if ($ap == $bp)
            {
                return 0;
            }
            return ($ap > $bp) ? 1 : -1;
        }
        if (is_string($ap))
        {
            return strnatcmp($ap, $bp);
        }
        return 0;
    }

    private function _analyze_raw_hours()
    {
        if (empty($this->_request_data['raw_results']['hr']))
        {
            debug_add('Hour reports array not found', MIDCOM_LOG_WARN);
            return;
        }
        $formatter = $this->_l10n->get_formatter();
        foreach ($this->_request_data['raw_results']['hr'] as $hour)
        {
            $row = array();
            $row['is_group'] = false;
            $row['hour'] = $hour;
            $row['task'] = org_openpsa_projects_task_dba::get_cached($hour->task);
            try
            {
                $row['person'] = org_openpsa_contacts_person_dba::get_cached($hour->person);
            }
            catch (midcom_error $e)
            {
                $e->log();
                continue;
            }

            // Default (should work for almost every grouping) is to sort rows by the hour report date
            $row['sort'] = $row['hour']->date;
            //Determine our group
            if ($this->_grouping == 'date')
            {
                $matching = 'date:' . date('Ymd', $row['hour']->date);
                $sort = date('Ymd', $row['hour']->date);
                $title = $formatter->date($row['hour']->date);
                $this->add_to_group($row, $matching, $sort, $title);
            }
            elseif ($this->_grouping == 'person')
            {
                $matching = 'person:' . $row['person']->guid;
                $sort = $row['person']->rname;
                $title = $row['person']->rname;
                $this->add_to_group($row, $matching, $sort, $title);
            }
            else
            {
                continue;
            }

            //Place data to report
            $this->_request_data['report']['total_hours'] += $hour->hours;
        }
    }

    private function add_to_group($new_row, $matching, $sort, $title, array &$rows = null)
    {
        $recursed = true;
        if ($rows === null)
        {
            $rows =& $this->_request_data['report']['rows'];
            $recursed = false;
        }
        foreach ($rows as &$row)
        {
            if (empty($row['is_group']))
            {
                continue;
            }
            if ($row['matching'] === $matching)
            {
                $row['rows'][] = $new_row;
                $row['total_hours'] += $new_row['hour']->hours;
                return true;
            }
            if (   array_key_exists('rows', $row)
                && $this->add_to_group($new_row, $matching, $sort, $title, $row['rows']))
            {
                return true;
            }
        }
        //Could not find group, but since we're inside recursion loop we won't create it yet
        if ($recursed)
        {
            return false;
        }
        $rows[] = array
        (
            'is_group' => true,
            'matching' => $matching,
            'sort' => $sort,
            'title' => $title,
            'rows' => array($new_row),
            'total_hours' => $new_row['hour']->hours
        );
        return true;
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_generator($handler_id, array &$data)
    {
        //Mangling if report wants to do it (done here to have style context, otherwise MidCOM will not like us.
        midcom_show_style('projects_report-basic-mangle-query');
        //Handle grouping
        if (!empty($this->_request_data['query_data']['grouping']))
        {
            if (array_key_exists($this->_request_data['query_data']['grouping'], $this->_valid_groupings))
            {
                debug_add('Setting grouping to: ' . $this->_request_data['query_data']['grouping']);
                $this->_grouping = $this->_request_data['query_data']['grouping'];
            }
            else
            {
                debug_add(sprintf("\"%s\" is not a valid grouping, keeping default", $this->_request_data['query_data']['grouping']), MIDCOM_LOG_WARN);
            }
        }

        // Put grouping to request data
        $this->_request_data['grouping'] = $this->_grouping;

        //Get our results
        $results_hr = $this->_get_hour_reports();

        //For debugging and sensible passing of data
        $this->_request_data['raw_results'] = array('hr' => $results_hr);
        //TODO: Mileages, expenses

        $this->_request_data['report'] = array('rows' => array(), 'total_hours' => 0);

        $this->_analyze_raw_hours();

        $this->_sort_rows_recursive($this->_request_data['report']['rows']);

        //TODO: add other report types when supported
        if (empty($this->_request_data['raw_results']['hr']))
        {
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

    public function _show_generator_group(array $data, $level = 0)
    {
        foreach ($data as $row)
        {
            $row['level'] = $level;
            $this->_request_data['current_row'] = $row;
            if (   array_key_exists('is_group', $row)
                && $row['is_group'] == true)
            {
                $this->_request_data['current_group'] = $row;
                //Indented to make style flow clearer
                midcom_show_style('projects_report-basic-group-start');
                    midcom_show_style('projects_report-basic-group-header');
                        $this->_show_generator_group($row['rows'], $level + 1);
                    midcom_show_style('projects_report-basic-group-totals');
                    midcom_show_style('projects_report-basic-group-footer');
                midcom_show_style('projects_report-basic-group-end');
            }
            else
            {
                midcom_show_style('projects_report-basic-item');
            }
        }
    }
}
