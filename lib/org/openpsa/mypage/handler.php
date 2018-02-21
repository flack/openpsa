<?php
/**
 * @package org.openpsa.mypage
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Handler addons
 *
 * @package org.openpsa.mypage
 */
trait org_openpsa_mypage_handler
{
    /**
     * Get start and end times
     */
    public function prepare_timestamps(DateTime $time)
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
