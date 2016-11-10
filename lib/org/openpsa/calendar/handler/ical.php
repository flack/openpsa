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
        return preg_replace('/\.(i|v)cs$/', '', $str);
    }

    /**
     * If we have person defined populate $this->_request_data['events']
     */
    private function _get_events()
    {
        $this->_request_data['events'] = array();

        $root_event = org_openpsa_calendar_interface::find_root_event();

        $mc = org_openpsa_calendar_event_member_dba::new_collector('uid', $this->_request_data['person']->id);
        $mc->add_constraint('eid.up', '=', $root_event->id);
        // Display events two weeks back
        $mc->add_constraint('eid.start', '>', mktime(0, 0, 0, date('n'), date('j') - 14, date('Y')));

        $members = $mc->get_values('eid');

        if (!empty($members)) {
            $qb = org_openpsa_calendar_event_dba::new_query_builder();
            $qb->add_constraint('id', 'IN', $members);
            $qb->add_order('start', 'ASC');
            $this->_request_data['events'] = $qb->execute();
        }
    }

    /**
     * Set Content-Type headers
     */
    private function _content_type()
    {
        midcom::get()->skip_page_style = true;
        midcom::get()->header('Content-type: text/calendar');
    }

    /**
     * iCal feed of uses events
     *
     * HTTP-Basic authenticated, requires valid user, normal ACL restrictions apply
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_user_events($handler_id, array $args, array &$data)
    {
        midcom::get()->auth->require_valid_user('basic');

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
    public function _show_user_events($handler_id, array &$data)
    {
        $encoder = new org_openpsa_calendar_vcal;
        array_map(array($encoder, 'add_event'), $this->_request_data['events']);
        echo $encoder;
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
        if (empty($username)) {
            throw new midcom_error('Username missing');
        }
        $qb = org_openpsa_contacts_person_dba::new_query_builder();
        midcom_core_account::add_username_constraint($qb, '=', $username);
        midcom::get()->auth->request_sudo($this->_component);
        $persons = $qb->execute();
        midcom::get()->auth->drop_sudo();
        if (empty($persons)) {
            throw new midcom_error_notfound('Could not find person with username ' . $username);
        }
        return $persons[0];
    }

    /**
     * Publicly available iCal feed indicating user when is busy
     *
     * Most values are stripped before display
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
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
        $encoder = new org_openpsa_calendar_vcal;
        foreach ($this->_request_data['events'] as $event) {
            // clear all data not absolutely required for busy listing
            foreach ($event->get_properties() as $fieldname) {
                switch (true) {
                    case ($fieldname == 'metadata'):
                    case ($fieldname == 'guid'):
                    case ($fieldname == 'start'):
                    case ($fieldname == 'end'):
                        break;
                    case ($fieldname == 'title'):
                        $event->title = $this->_l10n->get('busy');
                        break;
                    case is_array($event->$fieldname):
                        $event->$fieldname = array();
                        break;
                    case is_string($event->$fieldname):
                    default:
                        $event->$fieldname = '';
                        break;
                }
            }
            // Only display the requested user as participant
            $event->participants[$data['person']->id] =  true;
            // Always force busy in this view
            $event->busy = true;
            $encoder->add_event($event);
        }
        echo $encoder;
    }
}
