<?php
/**
 * @package org.openpsa.calendar
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use Doctrine\ORM\Query\Expr\Join;
use Symfony\Component\HttpFoundation\Request;

/**
 * @package org.openpsa.calendar
 */
class org_openpsa_calendar_handler_json extends midcom_baseclasses_components_handler
{
    /**
     * @var org_openpsa_calendar_event_dba
     */
    private $root_event;

    private $filters;

    private $events = [];

    /**
     * JSON view
     */
    public function _handler_json(Request $request)
    {
        midcom::get()->auth->require_valid_user();
        $this->root_event = org_openpsa_calendar_interface::find_root_event();
        $this->load_events($request->query->getInt('start'), $request->query->getInt('end'));
        $this->add_holidays($request->query->getInt('start'), $request->query->getInt('end'));
        return new midcom_response_json(array_values($this->events));
    }

    private function add_holidays(int $from, int $to)
    {
        $from = new DateTime(strftime('%Y-%m-%d', $from));
        $to = new DateTime(strftime('%Y-%m-%d', $to));
        $country = $this->_config->get('holidays_country');
        if (class_exists('\\Checkdomain\\Holiday\\Provider\\' . strtoupper($country))) {
            $util = new \Checkdomain\Holiday\Util;
            $region = $this->_config->get('holidays_region');

            do {
                if ($holiday = $util->getHoliday($country, $from, $region)) {
                    $this->events[] = [
                        'title' => $holiday->getName(),
                        'start' => $from->format('Y-m-d'),
                        'className' => [],
                        'rendering' => 'background'
                    ];
                }
                $from->modify('+1 day');
            } while ($from < $to);
        }
    }

    private function get_filters(string $type) : array
    {
        if (!$this->filters) {
            $this->filters = ['people' => [], 'groups' => [], 'resources' => []];
            foreach (midcom::get()->auth->user->get_storage()->list_parameters('org.openpsa.calendar.filters') as $key => $value) {
                $selected = @unserialize($value);

                if (!empty($selected)) {
                    $this->filters[$key] = array_unique(array_merge($selected, $this->filters[$key]));
                }
            }
        }

        return $this->filters[$type];
    }

    private function load_memberships(int $from, int $to) : array
    {
        $user = midcom::get()->auth->user->get_storage();
        $mc = org_openpsa_calendar_event_member_dba::new_collector('eid.up', $this->root_event->id);
        // Find all events that occur during [$from, $to]
        $mc->add_constraint('eid.start', '<=', $to);
        $mc->add_constraint('eid.end', '>=', $from);

        $mc->begin_group('OR');
        $mc->add_constraint('uid', '=', $user->id);

        if ($selected = $this->get_filters('people')) {
            $mc->add_constraint('uid.guid', 'IN', $selected);
        }
        if ($selected = $this->get_filters('groups')) {
            $mc->get_doctrine()->leftJoin('midgard_member', 'm', Join::WITH, 'm.uid = c.uid');
            $mc->get_doctrine()->leftJoin('midgard_group', 'g', Join::WITH, 'g.id = m.gid');
            $mc->get_current_group()->add('g.guid IN(:selected)');
            $mc->get_doctrine()->setParameter('selected', $selected);
        }
        $mc->end_group();

        return $mc->get_rows(['uid', 'eid']);
    }

    private function load_resources(int $from, int $to) : array
    {
        $selected = $this->get_filters('resources');
        if (empty($selected)) {
            return [];
        }
        $mc = org_openpsa_calendar_event_resource_dba::new_collector();
        // Find all events that occur during [$from, $to]
        $mc->add_constraint('event.start', '<=', $to);
        $mc->add_constraint('event.end', '>=', $from);
        $mc->add_constraint('resource', 'IN', $selected);
        return $mc->get_rows(['event', 'resource']);
    }

    /**
     * Loads calendar events
     */
    private function load_events(int $from, int $to)
    {
        foreach ($this->load_memberships($from, $to) as $membership) {
            $event = org_openpsa_calendar_event_dba::get_cached($membership['eid']);
            $this->add_event($event);
            if ($membership['uid'] == midcom_connection::get_user()) {
                $this->events[$event->guid]['participants'][] = $this->_l10n->get('me');
                $this->events[$event->guid]['className'][] = 'paticipant_me';
            } else {
                $person = org_openpsa_contacts_person_dba::get_cached($membership['uid']);
                $this->events[$event->guid]['participants'][] = $person->get_label();
            }
            $this->events[$event->guid]['className'][] = 'paticipant_' . $membership['uid'];
        }

        foreach ($this->load_resources($from, $to) as $event_resource) {
            $event = org_openpsa_calendar_event_dba::get_cached($event_resource['event']);
            $this->add_event($event);
            $this->events[$event->guid]['className'][] = 'resource_' . $event_resource['resource'];
        }
    }

    private function add_event(org_openpsa_calendar_event_dba $event)
    {
        if (!isset($this->events[$event->guid])) {
            // Customize label
            $label_field = $this->_config->get('event_label') ?: 'title';
            $label = $event->$label_field;
            if ($label_field == 'creator') {
                $user = midcom::get()->auth->get_user($event->metadata->creator);
                $label = $user->name;
            }

            $this->events[$event->guid] = [
                'id' => $event->guid,
                'title' => $label,
                'location' => $event->location,
                'start' => strftime('%Y-%m-%dT%T', $event->start),
                'end' => strftime('%Y-%m-%dT%T', $event->end),
                'className' => [],
                'participants' => [],
                'allDay' => (($event->end - $event->start) > 8 * 60 * 60)
            ];
            if ($event->orgOpenpsaAccesstype == org_openpsa_core_acl::ACCESS_PRIVATE) {
                $this->events[$event->guid]['className'][] = 'private';
            }
        }
    }
}
