<?php
/**
 * @package org.openpsa.mypage
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\helper\autocomplete;

/**
 * Mypage "now working on"
 *
 * @package org.openpsa.mypage
 */
class org_openpsa_mypage_handler_workingon extends midcom_baseclasses_components_handler
{
    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_view($handler_id, array $args, array &$data)
    {
        midcom::get()->auth->require_valid_user();

        // Set the "now working on" status
        $data['workingon'] = new org_openpsa_mypage_workingon();

        midcom::get()->skip_page_style = true;

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

        $this->_master->calculate_day($data['requested_time']);

        $this->_list_work_hours();
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

        $hours_mc = org_openpsa_projects_hour_report_dba::new_collector('person', midcom_connection::get_user());
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
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_view($handler_id, array &$data)
    {
        midcom_show_style('workingon');
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_set($handler_id, array $args, array &$data)
    {
        if (!array_key_exists('task', $_POST)) {
            throw new midcom_error('No task specified.');
        }

        $relocate = '';
        if (array_key_exists('url', $_POST)) {
            $relocate = $_POST['url'];
        }

        // Handle "not working on anything"
        if ($_POST['action'] == 'stop') {
            $_POST['task'] = '';
        }

        // Set the "now working on" status
        $workingon = new org_openpsa_mypage_workingon();
        if (!$workingon->set($_POST['task'])) {
            midcom::get()->uimessages->add($this->_l10n->get('org.openpsa.mypage'),  'Failed to set "working on" parameter to "' . $_POST['task'] . '", reason ' . midcom_connection::get_error_string(), 'error');
        }

        return new midcom_response_relocate($relocate . "workingon/");
    }
}
