<?php
/**
 * @package org.openpsa.calendar
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Calendar agenda handler
 *
 * @package org.openpsa.calendar
 */
class org_openpsa_calendar_handler_agenda extends midcom_baseclasses_components_handler
{
    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_day($handler_id, array $args, array &$data)
    {
        $date = new DateTime($args[0]);
        $data['calendar_options'] = $this->_master->get_calendar_options();
        $data['calendar_options']['defaultDate'] = $date->format('Y-m-d');
        $data['date'] = $date;
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_day($handler_id, array &$data)
    {
        midcom_show_style('show-agenda');
    }
}
