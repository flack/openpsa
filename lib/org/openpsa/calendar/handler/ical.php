<?php
/**
 * @package org.openpsa.calendar
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Sabre\VObject\Reader;
use Doctrine\ORM\Query\Expr\Join;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Calendar ical handler
 *
 * @package org.openpsa.calendar
 */
class org_openpsa_calendar_handler_ical extends midcom_baseclasses_components_handler
{
    /**
     * @var org_openpsa_contacts_person_dba
     */
    private $person;

    /**
     * @return org_openpsa_calendar_event_dba[]
     */
    private function get_events() : array
    {
        $root_event = org_openpsa_calendar_interface::find_root_event();

        $qb = org_openpsa_calendar_event_dba::new_query_builder();
        $qb->get_doctrine()
            ->leftJoin('org_openpsa_eventmember', 'm', Join::WITH, 'm.eid = c.id')
            ->where('m.uid = :uid')
            ->setParameter('uid', $this->person->id);

        $qb->add_constraint('up', '=', $root_event->id);
        // Display events two weeks back
        $qb->add_constraint('start', '>', strtotime('14 days ago'));
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
            $this->update(file_get_contents('php://input'));
        }

        $encoder = new org_openpsa_calendar_vcal;
        array_map([$encoder, 'add_event'], $this->get_events());

        return new Response($encoder, Response::HTTP_OK, [
            'Content-Type' => 'text/calendar'
        ]);
    }

    private function update(string $input)
    {
        $vcalendar = Reader::read($input);
        if (!$vcalendar->select('VEVENT')) {
            return;
        }
        foreach ($vcalendar->select('VEVENT') as $vevent) {
            $uid = $vevent->UID->getValue();
            if (str_ends_with($uid, '-midgardGuid')) {
                $event = new org_openpsa_calendar_event_dba(substr($uid, 0, -12));
            } else {
                $event = new org_openpsa_calendar_event_dba;
                $event->externalGuid = $uid;
                $root_event = org_openpsa_calendar_interface::find_root_event();
                $root_event->require_do('midgard:create');
                $event->up = $root_event->id;
            }

            $event->title = $vevent->SUMMARY->getValue();
            $event->description = $vevent->DESCRIPTION ? $vevent->DESCRIPTION->getValue() : '';
            $event->location = $vevent->LOCATION ? $vevent->LOCATION->getValue() : '';
            $event->busy = $vevent->TRANSP->getValue() == 'OPAQUE';
            $start = new DateTime($vevent->DTSTART->getValue());
            $event->start = (int) $start->format('U');
            $end = new DateTime($vevent->DTEND->getValue());
            $event->end = (int) $end->format('U');

            if ($event->id) {
                $event->update();
            } else {
                $event->create();
                $member = new org_openpsa_calendar_event_member_dba;
                $member->eid = $event->id;
                $member->uid = $this->person->id;
                $member->create();
            }
        }
    }

    /**
     * Finds a person by username
     */
    private function find_person_by_name(string $username)
    {
        $username = preg_replace('/\.(i|v)cs$/', '', $username);

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
        $this->person = $persons[0];
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
        foreach ($this->get_events() as $event) {
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
