<?php
/**
 * @package org.openpsa.calendar
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Doctrine\ORM\Query\Expr\Join;
use Sabre\DAV\Exception as DAVException;
use Sabre\VObject\Component\VCalendar;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Calendar ical handler
 *
 * @package org.openpsa.calendar
 */
class org_openpsa_calendar_handler_ical extends midcom_baseclasses_components_handler
{
    private midcom_db_person $person;

    /**
     * Events from two weeks back
     *
     * @param bool $occurrences Whether to list the occurrences of recurring events as separate events
     *                          (otherwise, a series is one event with its rule)
     * @return org_openpsa_calendar_event_dba[]
     */
    private function get_events(bool $occurrences = false) : array
    {
        $since = strtotime('14 days ago');
        $qb = org_openpsa_calendar_event_dba::new_query_builder();
        $qb->get_doctrine()
            ->leftJoin('org_openpsa_eventmember', 'm', Join::WITH, 'm.eid = c.id')
            ->where('m.uid = :uid')
            ->setParameter('uid', $this->person->id);

        if ($occurrences) {
            org_openpsa_calendar_event_dba::add_calendar_constraint($qb);
            $qb->add_constraint('start', '>', $since);
        } else {
            $qb->add_constraint('up', '=', org_openpsa_calendar_interface::find_root_event()->id);
            $qb->begin_group('OR');
                $qb->add_constraint('start', '>', $since);
                // Series that started earlier, but continue
                $qb->begin_group('AND');
                    $qb->add_constraint('recurrence', '<>', '');
                    $qb->begin_group('OR');
                        $qb->add_constraint('recurrenceUntil', '=', 0);
                        $qb->add_constraint('recurrenceUntil', '>', $since);
                    $qb->end_group();
                $qb->end_group();
            $qb->end_group();
        }
        $qb->add_order('start', 'ASC');
        return $qb->execute();
    }

    /**
     * iCal feed of uses events
     *
     * HTTP-Basic authenticated, requires valid user, normal ACL restrictions apply
     */
    public function _handler_user_events(Request $request, string $username)
    {
        midcom::get()->auth->require_valid_user('basic');

        $this->find_person_by_name($username);
        if ($request->getMethod() === 'PUT') {
            $this->update($request->getContent());
        }

        $encoder = new org_openpsa_calendar_vcal;
        array_map($encoder->add_event(...), $this->get_events());

        return new Response($encoder, Response::HTTP_OK, [
            'Content-Type' => 'text/calendar'
        ]);
    }

    /**
     * Saves the VEVENTs, grouped by UID (a recurring event's overrides belong to its master)
     */
    private function update(string $input)
    {
        $vcalendar = org_openpsa_calendar_vcal::read($input) ?? throw new midcom_error('Could not parse calendar data', Response::HTTP_BAD_REQUEST);
        $by_uid = [];
        foreach ($vcalendar->select('VEVENT') as $vevent) {
            $by_uid[(string) $vevent->UID][] = $vevent;
        }
        $import = new org_openpsa_calendar_import;
        foreach ($by_uid as $uid => $vevents) {
            $vcal = new VCalendar;
            $vcal->VERSION = '2.0';
            foreach ($vcalendar->select('VTIMEZONE') as $timezone) {
                $vcal->add(clone $timezone);
            }
            foreach ($vevents as $vevent) {
                $vcal->add(clone $vevent);
            }
            try {
                $import->save($this->find_event((string) $uid), $this->person->id, $vcal);
            } catch (DAVException $e) {
                throw new midcom_error($e->getMessage(), $e->getHTTPCode());
            }
        }
    }

    private function find_event(string $uid) : org_openpsa_calendar_event_dba
    {
        if (str_ends_with($uid, '-midgardGuid')) {
            return new org_openpsa_calendar_event_dba(substr($uid, 0, -12));
        }
        $qb = org_openpsa_calendar_event_dba::new_query_builder();
        $qb->add_constraint('externalGuid', '=', $uid);
        if ($result = $qb->get_result(0)) {
            return $result;
        }

        $event = new org_openpsa_calendar_event_dba;
        $event->externalGuid = $uid;
        $root_event = org_openpsa_calendar_interface::find_root_event();
        $root_event->require_do('midgard:create');
        $event->up = $root_event->id;
        return $event;
    }

    /**
     * Finds a person by username
     */
    private function find_person_by_name(string $username)
    {
        $username = preg_replace('/\.[iv]cs$/', '', $username);

        if (empty($username)) {
            throw new midcom_error('Username missing');
        }
        $qb = midcom_db_person::new_query_builder();
        midcom_core_account::add_username_constraint($qb, '=', $username);
        midcom::get()->auth->request_sudo($this->_component);
        $person = $qb->get_result(0);
        midcom::get()->auth->drop_sudo();
        if (empty($person)) {
            throw new midcom_error_notfound('Could not find person with username ' . $username);
        }
        $this->person = $person;
    }

    /**
     * Publicly available iCal feed indicating user when is busy
     *
     * Most values are stripped before display
     */
    public function _handler_user_busy(string $username)
    {
        $this->find_person_by_name($username);

        $encoder = new org_openpsa_calendar_vcal;
        foreach ($this->get_events(true) as $event) {
            // clear all data not absolutely required for busy listing
            foreach ($event->get_properties() as $fieldname) {
                if ($fieldname == 'title') {
                    $event->title = $this->_l10n->get('busy');
                } elseif (is_array($event->$fieldname)) {
                    $event->$fieldname = [];
                } elseif (!in_array($fieldname, ['metadata', 'guid', 'start', 'end'])) {
                    $event->$fieldname = '';
                }
            }
            // Only display the requested user as participant
            $event->participants[$this->person->id] = true;
            // Always force busy in this view
            $event->busy = true;
            $encoder->add_event($event);
        }
        return new Response($encoder, Response::HTTP_OK, [
            'Content-Type' => 'text/calendar'
        ]);
    }
}
