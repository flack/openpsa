<?php
/**
 * @package org.openpsa.mypage
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\helper\autocomplete;
use Symfony\Component\HttpFoundation\Request;

/**
 * Mypage "now working on"
 *
 * @package org.openpsa.mypage
 */
class org_openpsa_mypage_handler_workingon extends midcom_baseclasses_components_handler
{
    use org_openpsa_mypage_handler;

    /**
     * @param array $data The local request data.
     */
    public function _handler_view(array &$data)
    {
        midcom::get()->auth->require_valid_user();

        // Set the "now working on" status
        $data['workingon'] = new org_openpsa_mypage_workingon();

        $task_conf = autocomplete::get_widget_config('task');
        $task_conf['id_field'] = 'guid';

        $task_conf['constraints'][] = [
            'field' => 'status',
            'op'    => '<',
            'value' => org_openpsa_projects_task_status_dba::COMPLETED,
        ];
        $data['widget_config'] = $task_conf;

        // List work hours this week
        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        $data['expenses_url'] = $siteconfig->get_node_full_url('org.openpsa.expenses');

        $data['requested_time'] = new DateTime;

        $this->prepare_timestamps($data['requested_time']);
        $this->_list_work_hours();

        autocomplete::add_head_elements();
        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . "/org.openpsa.mypage/jquery.epiclock.min.js");
        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . "/org.openpsa.mypage/workingon.js");

        return $this->show('workingon');
    }

    /**
     * Function to list invoiceable and uninvoicable hours
     */
    private function _list_work_hours()
    {
        $this->_request_data['customers'] = [];
        $this->_request_data['hours'] = [
            'invoiceable' => [],
            'uninvoiceable' => [],
            'total_invoiceable' => 0,
            'total_uninvoiceable' => 0,
        ];

        $hours_mc = org_openpsa_expenses_hour_report_dba::new_collector('person', midcom_connection::get_user());
        $hours_mc->add_constraint('date', '>=', $this->_request_data['week_start']);
        $hours_mc->add_constraint('date', '<=', $this->_request_data['week_end']);

        $reports = $hours_mc->get_rows(['task', 'invoiceable', 'hours']);
        foreach ($reports as $report) {
            $this->_add_hour_data($report);
        }
    }

    /**
     * Set request data for hour reports
     *
     * @param array $array The array returned by collector
     */
    private function _add_hour_data(array $array)
    {
        static $customer_cache = [];
        if (!isset($customer_cache[$array['task']])) {
            $customer = 0;
            $customer_label = $this->_l10n->get('no customer');
            if ($array['task'] != 0) {
                $mc = new midgard_collector('org_openpsa_task', 'id', $array['task']);
                $mc->set_key_property('id');
                $mc->add_value_property('customer');
                $mc->execute();
                if ($customer_id = $mc->get_subkey($array['task'], 'customer')) {
                    try {
                        $customer = new org_openpsa_contacts_group_dba($customer_id);
                        $customer_label = $customer->official;
                        $customer = $customer_id;
                    } catch (midcom_error $e) {
                    }
                }
            }
            $customer_cache[$array['task']] = $customer;
            if (!isset($this->_request_data['customers'][$customer])) {
                $this->_request_data['customers'][$customer] = $customer_label;
            }
        }

        $customer = $customer_cache[$array['task']];
        $category = ($array['invoiceable']) ? 'invoiceable' : 'uninvoiceable';

        if (!isset($this->_request_data['hours'][$category][$customer])) {
            $this->_request_data['hours'][$category][$customer] = $array['hours'];
        } else {
            $this->_request_data['hours'][$category][$customer] += $array['hours'];
        }
        $this->_request_data['hours']['total_' . $category] += $array['hours'];
    }

    /**
     * @param Request $request The request object
     * @throws midcom_error
     * @return midcom_response_relocate
     */
    public function _handler_set(Request $request)
    {
        $task = $request->request->get('task');
        if (!$task) {
            throw new midcom_error('No task specified.');
        }

        // Handle "not working on anything"
        if ($request->request->get('action') == 'stop') {
            $task = '';
        }

        // Set the "now working on" status
        $workingon = new org_openpsa_mypage_workingon();
        if (!$workingon->set($task)) {
            midcom::get()->uimessages->add($this->_l10n->get('org.openpsa.mypage'), 'Failed to set "working on" parameter to "' . $task . '", reason ' . midcom_connection::get_error_string(), 'error');
        }

        $relocate = $request->request->get('url', '');
        return new midcom_response_relocate($relocate . "workingon/");
    }
}
