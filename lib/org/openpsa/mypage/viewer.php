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
}
