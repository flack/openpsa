<?php
/**
 * @package org.openpsa.calendar
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use Doctrine\ORM\Query\Expr\Join;
use midcom\datamanager\schemadb;
use midcom\datamanager\datamanager;

/**
 * org.openpsa.calendar site interface class.
 *
 * @package org.openpsa.calendar
 */
class org_openpsa_calendar_handler_view extends midcom_baseclasses_components_handler
{
    /**
     * Datamanager2 instance
     *
     * @var datamanager
     */
    private $datamanager;

    /**
     * The calendar root event
     *
     * @var org_openpsa_calendar_event_dba
     */
    private $_root_event = null;

    private $filters;

    private $events = [];

    /**
     * Initialization of the handler class
     */
    public function _on_initialize()
    {
        $this->_root_event = org_openpsa_calendar_interface::find_root_event();
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
        midcom::get()->auth->require_valid_user();
        $buttons = [];
        if ($this->_root_event->can_do('midgard:create')) {
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
    }

    /**
     * Show the calendar view
     *
     * @param String $handler_id    Name of the request handler
     * @param array &$data          Public request data, passed by reference
     */
    public function _show_calendar($handler_id, array &$data)
    {
        midcom_show_style('show-calendar');
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
        midcom::get()->auth->require_valid_user();
        $this->_load_events($_GET['start'], $_GET['end']);
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
        $mc = org_openpsa_calendar_event_member_dba::new_collector('eid.up', $this->_root_event->id);
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
    private function _load_events($from, $to)
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

    /**
     * Handle the single event view
     *
     * @param String $handler_id    Name of the request handler
     * @param array $args           Variable arguments
     * @param array &$data          Public request data, passed by reference
     */
    public function _handler_event($handler_id, array $args, array &$data)
    {
        // Get the requested event object
        $data['event'] = new org_openpsa_calendar_event_dba($args[0]);

        midcom::get()->skip_page_style = ($handler_id == 'event_view_raw');

        $this->load_datamanager();

        // Add toolbar items
        $buttons = [
            [
                MIDCOM_TOOLBAR_URL => 'event/edit/' . $this->_request_data['event']->guid . '/',
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('edit'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/edit.png',
                MIDCOM_TOOLBAR_ENABLED => $data['event']->can_do('midgard:update'),
                MIDCOM_TOOLBAR_ACCESSKEY => 'e',
            ]
        ];
        if ($data['event']->can_do('midgard:delete')) {
            $workflow = $this->get_workflow('delete', ['object' => $data['event']]);
            $buttons[] = $workflow->get_button("event/delete/{$data['event']->guid}/");
        }
        $buttons[] = [
            MIDCOM_TOOLBAR_URL => 'javascript:window.print()',
            MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('print'),
            MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/printer.png',
            MIDCOM_TOOLBAR_OPTIONS  => ['rel' => 'directlink']
        ];

        $relatedto_button_settings = null;

        if (midcom::get()->auth->user) {
            $user = midcom::get()->auth->user->get_storage();
            $date = $this->_l10n->get_formatter()->date();
            $relatedto_button_settings = [
                'wikinote'      => [
                    'component' => 'net.nemein.wiki',
                    'node'  => false,
                    'wikiword'  => str_replace('/', '-', sprintf($this->_l10n->get($this->_config->get('wiki_title_skeleton')), $data['event']->title, $date, $user->name)),
                ],
            ];
        }
        $this->_view_toolbar->add_items($buttons);
        org_openpsa_relatedto_plugin::common_node_toolbar_buttons($this->_view_toolbar, $data['event'], $this->_component, $relatedto_button_settings);

        midcom::get()->head->set_pagetitle(sprintf($this->_l10n->get('event %s'), $this->_request_data['event']->title));
        return $this->get_workflow('viewer')->run();
    }

    private function load_datamanager()
    {
        // Load schema database
        $schemadb = schemadb::from_path($this->_config->get('schemadb'));
        $schema = null;
        if (!$this->_request_data['event']->can_do('org.openpsa.calendar:read')) {
            $schema = 'private';
        }
        $this->datamanager = new datamanager($schemadb);
        $this->datamanager->set_storage($this->_request_data['event'], $schema);
    }

    /**
     * Show a single event
     *
     * @param String $handler_id    Name of the request handler
     * @param array &$data          Public request data, passed by reference
     */
    public function _show_event($handler_id, array &$data)
    {
        if ($handler_id == 'event_view') {
            // Set title to popup
            $data['title'] = sprintf($this->_l10n->get('event %s'), $data['event']->title);

            // Show popup
            $data['event_dm'] = $this->datamanager;
            midcom_show_style('show-event');
        } else {
            midcom_show_style('show-event-raw');
        }
    }
}
