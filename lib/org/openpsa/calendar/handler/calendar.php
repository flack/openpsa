<?php
/**
 * @package org.openpsa.calendar
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use Doctrine\ORM\Query\Expr\Join;

/**
 * @package org.openpsa.calendar
 */
class org_openpsa_calendar_handler_calendar extends midcom_baseclasses_components_handler
{
    /**
     * The calendar root event
     *
     * @var org_openpsa_calendar_event_dba
     */
    private $root_event;

    private $filters;

    private $events = [];

    /**
     * Initialization of the handler class
     */
    public function _on_initialize()
    {
        midcom::get()->auth->require_valid_user();
        $this->root_event = org_openpsa_calendar_interface::find_root_event();
    }

    /**
     * @param String $handler_id    Name of the request handler
     * @param array $args           Variable arguments
     * @param array &$data          Public request data, passed by reference
     */
    public function _handler_frontpage($handler_id, array $args, array &$data)
    {
        $selected_time = time();
        $view = $this->_config->get('start_view');
        if ($view == 'day') {
            $view = 'agendaDay';
        } elseif ($view != 'month') {
            $view = 'agendaWeek';
        }
        return new midcom_response_relocate($view . '/' . date('Y-m-d', $selected_time) . '/');
    }

    /**
     * Calendar view
     *
     * @param String $handler_id    Name of the request handler
     * @param array $args           Variable arguments
     * @param array &$data          Public request data, passed by reference
     */
    public function _handler_calendar($handler_id, array $args, array &$data)
    {
        $workflow = $this->get_workflow('datamanager');
        $buttons = [];
        if ($this->root_event->can_do('midgard:create')) {
            $buttons[] = $workflow->get_button('#', [
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('create event'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_new-event.png',
                MIDCOM_TOOLBAR_OPTIONS  => [
                    'id' => 'openpsa_calendar_add_event',
                ]
            ]);
            if (midcom::get()->auth->can_user_do('midgard:create', null, org_openpsa_calendar_resource_dba::class)) {
                $buttons[] = $workflow->get_button('resource/new/', [
                    MIDCOM_TOOLBAR_LABEL => sprintf($this->_l10n_midcom->get('create %s'), $this->_l10n->get('resource')),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/printer.png',
                ]);
            }
        }
        $buttons[] = $workflow->get_button('filters/', [
            MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('choose calendars'),
            MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/preferences-desktop.png',
        ]);

        $buttons[] = [
            MIDCOM_TOOLBAR_URL => '#',
            MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('go to'),
            MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_jump-to.png',
            MIDCOM_TOOLBAR_OPTIONS  => [
                'rel' => 'directlink',
                'id' => 'date-navigation',
            ]
        ];
        $this->_view_toolbar->add_items($buttons);

        $data['calendar_options'] = $this->_master->get_calendar_options();
        org_openpsa_widgets_calendar::add_head_elements();
        midcom::get()->head->enable_jquery_ui(['datepicker']);
        return $this->show('show-calendar');
    }

    /**
     * JSON view
     *
     * @param String $handler_id    Name of the request handler
     * @param array $args           Variable arguments
     * @param array &$data          Public request data, passed by reference
     */
    public function _handler_json($handler_id, array $args, array &$data)
    {
        $this->load_events($_GET['start'], $_GET['end']);
        $this->add_holidays($_GET['start'], $_GET['end']);
        return new midcom_response_json(array_values($this->events));
    }

    private function add_holidays($from, $to)
    {
        $from = new DateTime(strftime('%Y-%m-%d', $from));
        $to = new DateTime(strftime('%Y-%m-%d', $to));
        $country = $this->_config->get('holidays_country');
        if (class_exists('\\Checkdomain\\Holiday\\Provider\\' . $country)) {
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

    private function get_filters($type)
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

    private function load_memberships($from, $to)
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

    private function load_resources($from, $to)
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
     *
     * @param int $from Start time
     * @param int $to End time
     */
    private function load_events($from, $to)
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
        // Customize label
        $label_field = $this->_config->get('event_label');
        if (!$label_field) {
            $label_field = 'title';
        }
        if (!isset($this->events[$event->guid])) {
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
