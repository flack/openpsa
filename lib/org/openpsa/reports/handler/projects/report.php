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
    private function _expand_task($task, $ret = array())
    {
        //When recursing we get object, otherwise GUID
        if (!is_object($task))
        {
            try
            {
                $task = org_openpsa_projects_task_dba::get_cached($task);
            }
            catch (midcom_error $e)
            {
                //Something went seriously wrong, abort as cleanly as possible
                debug_add('Could not get task object, aborting', MIDCOM_LOG_ERROR);
                return $ret;
            }
        }

        //Add current ID
        debug_add(sprintf('Adding task % (id: %s)', $task->title, $task->id));
        $ret[] = $task->id;

        //Get list of children and recurse
        //We pop already here due to recursion
        debug_add('Checking for children & recursing them');
        $qb = org_openpsa_projects_task_dba::new_query_builder();
        $qb->add_constraint('up', '=', $task->id);
        $results = $qb->execute();
        foreach ($results as $child_task)
        {
            $ret = $this->_expand_task($child_task, $ret);
        }
        return $ret;
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
        debug_add('checking for ' . $filter);
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
        $ap = false;
        $bp = false;
        if (array_key_exists('sort', $a))
        {
            $ap = $a['sort'];
        }
        if (array_key_exists('sort', $b))
        {
            $bp = $b['sort'];
        }
        switch (true)
        {
            default:
            case is_numeric($ap):
                if ($ap > $bp)
                {
                    return 1;
                }
                if ($ap < $bp)
                {
                    return -1;
                }
                return 0;

            case is_string($ap):
                return strnatcmp($ap, $bp);
        }
        return 0;
    }

    private function _analyze_raw_hours()
    {
        if (   empty($this->_request_data['raw_results']['hr'])
            || !is_array($this->_request_data['raw_results']['hr']))
        {
            debug_add('Hour reports array not found', MIDCOM_LOG_WARN);
            return false;
        }

        foreach ($this->_request_data['raw_results']['hr'] as $hour)
        {
            debug_add('processing hour id: ' . $hour->id);

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
            debug_add("grouping is {$this->_grouping}");
            switch ($this->_grouping)
            {
                case 'date':
                    $group =& $this->_get_report_group('date:' . date('Ymd', $row['hour']->date), date('Ymd', $row['hour']->date), strftime('%x', $row['hour']->date), $this->_request_data['report']['rows']);
                break;
                case 'person':
                    $group =& $this->_get_report_group('person:' . $row['person']->guid, $row['person']->rname, $row['person']->rname, $this->_request_data['report']['rows']);
                break;
            }

            if ($group)
            {
                //Place data to group
                $group['rows'][] = $row;
                $group['total_hours'] += $hour->hours;

                //Place data to report
                $this->_request_data['report']['total_hours'] += $hour->hours;
            }
        }

        return true;
    }

    function &_get_report_group($matching, $sort, $title, array &$rows, $recursed = 0)
    {
        foreach ($rows as $k => $row)
        {
            if (   !is_array($row)
                || empty($row['is_group']))
            {
                continue;
            }
            if ($row['matching'] === $matching)
            {
                debug_add(sprintf('found match in key "%s", returning it', $k));
                return $row;
            }

            if (    array_key_exists('rows', $row)
                &&  is_array($row['rows']))
            {
                debug_add(sprintf('found subgroup in key "%s", recursing it', $k));
                $got =& $this->_get_report_group($matching, $sort, $title, $row, ++$recursed);
                if ($got !== false)
                {
                    debug_add('Got result from recurser, returning it');
                    return $got;
                }
            }
        }
        //Could not find group, but since we're inside recursion loop we won't create it yet
        if ($recursed !== 0)
        {
            debug_add('No match and we\'re in recursive mode, returning false');
            $x = false;
            return $x;
        }
        debug_add('No match found, creating new group and returning it');
        //Othewise create a new group to the report
        $group = array();
        $group['is_group'] = true;
        $group['matching'] = $matching;
        $group['sort'] = $sort;
        $group['title'] = $title;
        $group['rows'] = array();
        $group['total_hours'] = 0;
        $next_key = count($rows);
        $rows[$next_key] = $group;
        return $rows[$next_key];
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_generator($handler_id, array $args, array &$data)
    {
        midcom::get()->auth->require_valid_user();

        $this->_generator_load_redirect($args);
        $this->set_active_leaf($this->_topic->id . ':generator_projects');
        $this->_handler_generator_style();
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_generator($handler_id, array &$data)
    {
        // Builtin style prefix
        if (preg_match('/^builtin:(.+)/', $this->_request_data['query_data']['style'], $matches))
        {
            $bpr = '-' . $matches[1];
            debug_add('Recognized builtin report, style prefix: ' . $bpr);
        }
        else
        {
            debug_add("'{$this->_request_data['query_data']['style']}' not recognized as builtin style");
            $bpr = '';
        }

        //Mangling if report wants to do it (done here to have style context, otherwise MidCOM will not like us.
        debug_print_r("query data before mangle:", $this->_request_data['query_data']);
        debug_add("calling midcom_show_style('report{$bpr}-mangle-query') to mangle the query data as necessary");
        midcom_show_style("projects_report{$bpr}-mangle-query");
        debug_print_r("query data after mangle:", $this->_request_data['query_data']);
        //Handle grouping
        debug_add('checking grouping');
        if (!empty($this->_request_data['query_data']['grouping']))
        {
            debug_add("checking validity of grouping value '{$this->_request_data['query_data']['grouping']}'");
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
        $this->_request_data['raw_results'] = array();
        $this->_request_data['raw_results']['hr'] = $results_hr;
        //TODO: Mileages, expenses

        $this->_request_data['report'] = array();
        $this->_request_data['report']['rows'] = array();
        $this->_request_data['report']['total_hours'] = 0;

        $this->_analyze_raw_hours();

        $this->_sort_rows_recursive($this->_request_data['report']['rows']);

        //TODO: add other report types when supported
        if (   !is_array($this->_request_data['raw_results']['hr'])
            || count($this->_request_data['raw_results']['hr']) == 0)
        {
            midcom_show_style("projects_report{$bpr}-noresults");
            return;
        }

        //Start actual display

        //Indented to make style flow clearer
        midcom_show_style("projects_report{$bpr}-start");
            midcom_show_style("projects_report{$bpr}-header");
                $this->_show_generator_group($this->_request_data['report']['rows'], $bpr);
            midcom_show_style("projects_report{$bpr}-totals");
            midcom_show_style("projects_report{$bpr}-footer");
        midcom_show_style("projects_report{$bpr}-end");
    }

    public function _show_generator_group(array $data, $bpr, $level = 0)
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
                midcom_show_style("projects_report{$bpr}-group-start");
                    midcom_show_style("projects_report{$bpr}-group-header");
                        $this->_show_generator_group($row['rows'], $bpr, $level + 1);
                    midcom_show_style("projects_report{$bpr}-group-totals");
                    midcom_show_style("projects_report{$bpr}-group-footer");
                midcom_show_style("projects_report{$bpr}-group-end");
            }
            else
            {
                midcom_show_style("projects_report{$bpr}-item");
            }
        }
    }
}
?>