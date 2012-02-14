<?php
/**
 * @package org.openpsa.calendar
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Calendar ical handler
 *
 * @package org.openpsa.calendar
 */
class org_openpsa_calendar_handler_ical extends midcom_baseclasses_components_handler
{
    /**
     * Strips last "file extension" from given string
     */
    private function _strip_extension($str)
    {
        return preg_replace('/\.(.*?)$/', '', $str);
    }

    /**
     * If we have person defined populate $this->_request_data['events']
     */
    private function _get_events()
    {
        $this->_request_data['events'] = array();
        if (!is_object($this->_request_data['person']))
        {
            return;
        }
        $root_event = org_openpsa_calendar_interface::find_root_event();

        $qb = org_openpsa_calendar_event_member_dba::new_query_builder();
        $qb->add_constraint('eid.up', '=', $root_event->id);
        // Display events two weeks back
        $qb->add_constraint('eid.start', '>', mktime(0, 0, 0, date('n'), date('j')-14, date('Y')));
        $qb->add_constraint('uid', '=', $this->_request_data['person']->id);
        $qb->add_order('eid.start', 'ASC');
        $members = $qb->execute();
        if (is_array($members))
        {
            foreach ($members as $member)
            {
                $this->_request_data['events'][] = new org_openpsa_calendar_event_dba($member->eid);
            }
        }
    }

    /**
     * Set Content-Type headers
     */
    private function _content_type()
    {
        $_MIDCOM->skip_page_style = true;
        $_MIDCOM->cache->content->content_type('text/calendar');
        // Debugging
        //$_MIDCOM->cache->content->content_type('text/plain');
    }

    /**
     * iCal feed of uses events
     *
     * HTTP-Basic authenticated, requires valid user, normal ACL restrictions apply
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_user_events($handler_id, array $args, array &$data)
    {
        midcom::get('auth')->require_valid_user('basic');

        $username = $this->_strip_extension($args[0]);
        $data['person'] = $this->_find_person_by_name($username);
        if (!is_object($data['person']))
        {
            throw new midcom_error_notfound('Could not find person with username ' . $username);
        }

        $this->_get_events();

        $this->_content_type();
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_user_events($handler_id, array &$data)
    {
        $encoder = new org_openpsa_calendar_vcal();
        echo $encoder->get_headers();
        foreach ($this->_request_data['events'] as $event)
        {
            echo $encoder->export_event($event);
        }
        echo $event->get_footers();
    }

    /**
     * Finds a person by username
     *
     * Returns full object or false in case of failure.
     *
     * @param string username
     * @return object person
     */
    private function _find_person_by_name($username)
    {
        if (empty($username))
        {
            return false;
        }
        midcom::get('auth')->request_sudo();
        $qb = org_openpsa_contacts_person_dba::new_query_builder();
        $qb->add_constraint('username', '=', $username);
        $persons = $qb->execute();
        midcom::get('auth')->drop_sudo();
        if (empty($persons))
        {
            // Error getting user object
            return false;
        }
        return $persons[0];
    }

    /**
     * Publicly available iCal feed indicating user when is busy
     *
     * Most values are stripped before display
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_user_busy($handler_id, array $args, array &$data)
    {
        $username = $this->_strip_extension($args[0]);
        $data['person'] = $this->_find_person_by_name($username);

        $this->_get_events();

        $this->_content_type();
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_user_busy($handler_id, array &$data)
    {
        $encoder = new org_openpsa_calendar_vcal();
        echo $encoder->get_headers();
        foreach ($this->_request_data['events'] as $event)
        {
            // clear all data not absolutely required for busy listing
            foreach ($event->__object as $k => $v)
            {
                switch(true)
                {
                    case ($k == 'metadata'):
                        break;
                    case ($k == 'title'):
                        $event->title = 'busy';
                        break;
                    case ($k == 'guid'):
                    case ($k == 'start'):
                    case ($k == 'end'):
                        $event->$k = $v;
                        break;
                    case is_array($v):
                        $event->$k = array();
                        break;
                    case is_string($v):
                    default:
                        $event->$k = '';
                        break;
                }
            }
            // Only display the requested user as participant
            $event->participants[$data['person']->id] =  true;
            // Always force busy in this view
            $event->busy = true;
            echo $encoder->export_event($event);
        }
        echo $encoder->vcal_footers();
    }
}
?>