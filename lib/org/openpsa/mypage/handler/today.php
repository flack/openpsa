<?php
/**
 * @package org.openpsa.mypage
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * My page today handler
 *
 * @package org.openpsa.mypage
 */
class org_openpsa_mypage_handler_today extends midcom_baseclasses_components_handler
{
    var $user = null;

    public function _on_initialize()
    {
        $_MIDCOM->auth->require_valid_user();
    }

    /**
     * Get start and end times
     */
    private function _calculate_day($time)
    {
        $date = new DateTime($time);

        $this->_request_data['this_day'] = $date->format('Y-m-d');
        $this->_request_data['day_start'] = (int) $date->format('U');
        $date->setTime(23, 59, 59);
        $this->_request_data['day_end'] = (int) $date->format('U');
        $date->modify('-1 day');
        $this->_request_data['prev_day'] = $date->format('Y-m-d');
        $date->modify('+2 days');
        $this->_request_data['next_day'] = $date->format('Y-m-d');
        $date->modify('-1 days');

        $offset = $date->format('N') - 1;
        $date->modify('-' . $offset . ' days');
        $this->_request_data['week_start'] = (int) $date->format('U');
        $date->modify('+6 days');
        $this->_request_data['week_end'] = (int) $date->format('U');
    }

    private function _populate_toolbar()
    {
        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => 'day/' . $this->_request_data['prev_day'] . '/',
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('previous'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/up.png',
            )
        );
        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => 'day/' . $this->_request_data['next_day'] . '/',
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('next'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/down.png',
            )
        );

        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => 'weekreview/' . $this->_request_data['this_day'] . '/',
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('week review'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/properties.png',
            )
        );
    }

    /**
     * Helper function that sets the request data for hour reports
     *
     * @param &$array The array returned by collector
     */
    private function _add_hour_data(&$array)
    {
        static $customer_cache = array();
        if (!isset($customer_cache[$array['task']]))
        {
            $customer = 0;
            $customer_label = $this->_l10n->get('no customer');
            if ($array['task'] != 0)
            {
                $mc = new midgard_collector('org_openpsa_task', 'id', $array['task']);
                $mc->set_key_property('id');
                $mc->add_value_property('customer');
                $mc->execute();
                $customer_id = $mc->get_subkey($array['task'], 'customer');
                if ($customer_id)
                {
                    try
                    {
                        $customer = new org_openpsa_contacts_group_dba($customer_id);
                        $customer_label = $customer->official;
                        $customer = $customer_id;
                    }
                    catch (midcom_error $e){}
               }
            }
            $customer_cache[$array['task']] = $customer;
            if (!isset($this->_request_data['customers'][$customer]))
            {
                $this->_request_data['customers'][$customer] = $customer_label;
            }
        }

        $customer = $customer_cache[$array['task']];

        $category = 'uninvoiceable';
        if ($array['invoiceable'])
        {
            $category = 'invoiceable';
        }

        if (!isset($this->_request_data['hours'][$category][$customer]))
        {
            $this->_request_data['hours'][$category][$customer] = $array['hours'];
        }
        else
        {
            $this->_request_data['hours'][$category][$customer] += $array['hours'];
        }
        $this->_request_data['hours']['total_' . $category] += $array['hours'];
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_today($handler_id, $args, &$data)
    {
        $this->user = $_MIDCOM->auth->user->get_storage();

        if ($handler_id == 'today')
        {
            $data['requested_time'] = date('Y-m-d');
        }
        else
        {
            // TODO: Check format as YYYY-MM-DD via regexp
            $data['requested_time'] = $args[0];
        }

        $this->_calculate_day($data['requested_time']);

        // List work hours this week
        $this->_list_work_hours();

        $this->_populate_toolbar();

        $data['title'] = strftime($data['requested_time']);
        $_MIDCOM->set_pagetitle($data['title']);

        // Add the JS file for "now working on" calculator
        $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . "/jQuery/jquery.epiclock.min.js");

        // Add the JS file for dynamic switching tasks without reloading the whole window
        $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . "/org.openpsa.mypage/mypage.js");

        $this->add_stylesheet(MIDCOM_STATIC_URL . "/org.openpsa.mypage/mypage.css");
        $this->add_stylesheet(MIDCOM_STATIC_URL . "/org.openpsa.core/list.css");
        $this->add_stylesheet(MIDCOM_STATIC_URL . "/org.openpsa.core/ui-elements.css");

        //needed js/css-files for jqgrid
        org_openpsa_core_ui::enable_jqgrid();

        //set the start-constraints for journal-entries
        $time_span = 7 * 24 * 60 *60 ; //7 days

        $this->_request_data['journal_constraints'] = array();
        //just show entries of current_user
        $this->_request_data['journal_constraints'][] = array(
                        'property' => 'metadata.creator',
                        'operator' => '=',
                        'value' => $_MIDCOM->auth->user->guid,
                        );
        $this->_request_data['journal_constraints'][] = array(
                        'property' => 'followUp',
                        'operator' => '<',
                        'value' => $this->_request_data['day_start'] + $time_span,
                        );
        $this->_request_data['journal_constraints'][] = array(
                        'property' => 'closed',
                        'operator' => '=',
                        'value' => false,
                        );
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    public function _show_today($handler_id, &$data)
    {
        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        $data['calendar_url'] = $siteconfig->get_node_relative_url('org.openpsa.calendar');
        $data['projects_url'] = $siteconfig->get_node_full_url('org.openpsa.projects');
        $data['projects_relative_url'] = $siteconfig->get_node_relative_url('org.openpsa.projects');
        $data['expenses_url'] = $siteconfig->get_node_full_url('org.openpsa.expenses');
        $data['wiki_url'] = $siteconfig->get_node_relative_url('net.nemein.wiki');

        $data_url = $_MIDCOM->get_host_name() . $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
        $data['journal_url'] = $data_url . '/__mfa/org.openpsa.relatedto/journalentry/list/xml/';

        midcom_show_style('show-today');
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_expenses($handler_id, $args, &$data)
    {
        $data['requested_time'] = date('Y-m-d');

        $this->_calculate_day($data['requested_time']);

        // List work hours this week
        $this->_list_work_hours();

        $_MIDCOM->skip_page_style = true;
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    public function _show_expenses($handler_id, &$data)
    {
        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        $data['expenses_url'] = $siteconfig->get_node_full_url('org.openpsa.expenses');
        midcom_show_style('workingon_expenses');
    }

    /**
     * Function to list invoiceable and uninvoicable hours
     */
    private function _list_work_hours()
    {
        $hours_mc = org_openpsa_projects_hour_report_dba::new_collector('person', midcom_connection::get_user());
        $hours_mc->add_value_property('task');
        $hours_mc->add_value_property('invoiceable');
        $hours_mc->add_value_property('hours');
        $hours_mc->add_constraint('date', '>=', $this->_request_data['week_start']);
        $hours_mc->add_constraint('date', '<=', $this->_request_data['week_end']);
        $hours_mc->execute();

        $hours = $hours_mc->list_keys();

        $this->_request_data['customers'] = array();
        $this->_request_data['hours'] = array
        (
            'invoiceable' => array(),
            'uninvoiceable' => array(),
            'total_invoiceable' => 0,
            'total_uninvoiceable' => 0,
        );

        foreach ($hours as $guid => $values)
        {
            $this->_add_hour_data($hours_mc->get($guid));
        }

        return true;
    }
}
?>