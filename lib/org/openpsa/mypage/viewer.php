<?php
/**
 * @package org.openpsa.mypage
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * org.openpsa.mypage site interface class.
 *
 * Personal summary page into OpenPSA
 *
 * @package org.openpsa.mypage
 */
class org_openpsa_mypage_viewer extends midcom_baseclasses_components_request
{
    public function _on_handle($handler_id, array $args)
    {
        // Always run in uncached mode
        midcom::get()->cache->content->no_cache();
        if ($handler_id == 'workingon_set') {
            midcom::get()->auth->require_valid_user('basic');
        } else {
            midcom::get()->auth->require_valid_user();
            org_openpsa_widgets_contact::add_head_elements();
        }
    }

    /**
     * Get start and end times
     */
    public function calculate_day(DateTime $time)
    {
        $date = clone $time;

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
        $date->setTime(0, 0, 0);
        $this->_request_data['week_start'] = (int) $date->format('U');
        $date->setTime(23, 59, 59);
        $date->modify('+6 days');
        $this->_request_data['week_end'] = (int) $date->format('U');
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_updates($handler_id, array $args, array &$data)
    {
        $indexer = midcom::get()->indexer;

        $start = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        $query = '__TOPIC_URL:"' . midcom::get()->get_host_name() . '*"';
        $filter = new midcom_services_indexer_filter_date('__EDITED', $start, 0);
        $this->_request_data['today'] = $indexer->query($query, $filter);
        $start = mktime(0, 0, 0, date('m'), date('d') - 1, date('Y'));
        $end = mktime(23, 59, 59, date('m'), date('d') - 1, date('Y'));
        $query = '__TOPIC_URL:"' . midcom::get()->get_host_name() . '*"';
        $filter = new midcom_services_indexer_filter_date('__EDITED', $start, $end);
        $this->_request_data['yesterday'] = $indexer->query($query, $filter);
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_updates($handler_id, array &$data)
    {
        midcom_show_style('show-updates');
    }
}
