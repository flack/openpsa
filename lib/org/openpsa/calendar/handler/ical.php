<?php
/**
 * @package org.openpsa.calendar
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: ical.php 23401 2009-09-16 15:14:50Z bergie $
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
    function __construct()
    {
        parent::__construct();
    }

    /**
     * Strips last "file extension" from given string
     */
    function _strip_extension($str)
    {
        return preg_replace('/\.(.*?)$/', '', $str);
    }

    /**
     * If we have person defined populate $this->_request_data['events']
     */
    function _get_events()
    {
        $this->_request_data['events'] = array();
        if (!is_object($this->request_data['person']))
        {
            return;
        }
        $root_event = org_openpsa_calendar_interface::find_root_event();
        
        $qb = org_openpsa_calendar_event_member_dba::new_query_builder();
        $qb->add_constraint('eid.up', '=', $root_event->id);
        // Display events two weeks back
        $qb->add_constraint('eid.start', '>', mktime(0, 0, 0, date('n'), date('j')-14, date('Y')));
        $qb->add_constraint('uid', '=', $this->request_data['person']->id);
        $qb->add_order('eid.start', 'ASC');
        $members = $qb->execute();
        if (is_array($members))
        {
            foreach($members as $member)
            {
                $this->_request_data['events'][] = new org_openpsa_calendar_event_dba($member->eid);
            }
        }
    }

    /**
     * Set Content-Type headers
     */
    function _content_type()
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
     * @return boolean Indicating success.
     */
    function _handler_user_events($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_valid_user('basic');
        debug_push_class(__CLASS__, __FUNCTION__);

        $username = $this->_strip_extension($args[0]);
        $this->request_data['person'] = $this->_find_person_by_name($username);
        if (!is_object($this->request_data['person']))
        {
            debug_pop();
            return false;
        }

        $this->_get_events();

        $this->_content_type();

        debug_pop();
        return true;
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    function _show_user_events($handler_id, &$data)
    {
        debug_push_class(__CLASS__, __FUNCTION__);

        $event = new org_openpsa_calendar_event_dba();
        echo $event->vcal_headers();
        foreach ($this->_request_data['events'] as $event)
        {
            echo $event->vcal_export();
        }
        echo $event->vcal_footers();

        debug_pop();
    }

    /**
     * Finds a person by username
     *
     * Returns full object or false in case of failure.
     *
     * @param string username
     * @return object person
     */
    function _find_person_by_name($username)
    {
        if (empty($username))
        {
            return false;
        }
        $_MIDCOM->auth->request_sudo();
        $qb = org_openpsa_contacts_person_dba::new_query_builder();
        $qb->add_constraint('username', '=', $username);
        $persons = $qb->execute();
        $_MIDCOM->auth->drop_sudo();
        if (   !is_array($persons)
            || count($persons) == 0)
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
     * @return boolean Indicating success.
     */
    function _handler_user_busy($handler_id, $args, &$data)
    {
        debug_push_class(__CLASS__, __FUNCTION__);
        $username = $this->_strip_extension($args[0]);
        $this->request_data['person'] = $this->_find_person_by_name($username);

        $this->_get_events();

        $this->_content_type();
        debug_pop();
        return true;
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    function _show_user_busy($handler_id, &$data)
    {
        debug_push_class(__CLASS__, __FUNCTION__);

        $event = new org_openpsa_calendar_event_dba();
        echo $event->vcal_headers();
        foreach ($this->_request_data['events'] as $event)
        {
            // clear all data not absolutely required for busy listing
            foreach($event->__object as $k => $v)
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
            $event->participants[$this->request_data['person']->id] =  true;
            // Always force busy in this view
            $event->busy = true;
            echo $event->vcal_export();
        }
        echo $event->vcal_footers();

        debug_pop();
    }

}
?>