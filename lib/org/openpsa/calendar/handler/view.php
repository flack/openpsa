<?php
/**
 * @package org.openpsa.calendar
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

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
     * @var midcom_helper_datamanager2_datamanager
     */
    private $_datamanager;

    /**
     * The calendar root event
     *
     * @var midcom_db_event
     */
    private $_root_event = null;

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
        $workflow = $this->get_workflow('datamanager2');
        midcom::get()->auth->require_valid_user();
        $buttons = array();
        if ($this->_root_event->can_do('midgard:create')) {
            $buttons[] = $workflow->get_button('#', array
            (
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('create event'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_new-event.png',
                MIDCOM_TOOLBAR_OPTIONS  => array
                (
                    'id' => 'openpsa_calendar_add_event',
                )
            ));
        }
        $buttons[] = array
        (
            MIDCOM_TOOLBAR_URL => "filters/?org_openpsa_calendar_returnurl=" . midcom_connection::get_url('uri'),
            MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('choose calendars'),
            MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/preferences-desktop.png',
        );

        $buttons[] = array
        (
            MIDCOM_TOOLBAR_URL => '#',
            MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('go to'),
            MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_jump-to.png',
            MIDCOM_TOOLBAR_OPTIONS  => array
            (
                'rel' => 'directlink',
                'id' => 'date-navigation',
            )
        );
        $this->_view_toolbar->add_items($buttons);

        $data['calendar_options'] = $this->_master->get_calendar_options();
        org_openpsa_widgets_calendar::add_head_elements();
        midcom_helper_datamanager2_widget_jsdate::add_head_elements();
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
        $uids = $this->_load_uids(midcom::get()->auth->user->get_storage());
        $events = $this->_load_events($uids, $_GET['start'], $_GET['end']);
        $this->add_holidays($events, $_GET['start'], $_GET['end']);
        return new midcom_response_json($events);
    }

    private function _load_uids($user)
    {
        $uids = array($user->guid => $user->id);
        // New UI for showing resources
        foreach ($user->list_parameters('org.openpsa.calendar.filters') as $type => $value) {
            $selected = @unserialize($value);

            // Skip empty
            if (empty($selected)) {
                continue;
            }

            // Include each type
            switch ($type) {
                case 'people':
                    $mc = midcom_db_person::new_collector('metadata.deleted', false);
                    $mc->add_constraint('guid', 'IN', $selected);
                    $uids = array_merge($uids, $mc->get_values('id'));
                    break;

                case 'groups':
                    $mc = midcom_db_member::new_collector('metadata.deleted', false);
                    $mc->add_constraint('gid.uid', 'IN', $selected);
                    $uids = array_merge($uids, $mc->get_values('uid'));
                    break;
            }
        }
        return $uids;
    }

    private function add_holidays(array &$events, $from, $to)
    {
        $from = new DateTime(strftime('%Y-%m-%d', $from));
        $to = new DateTime(strftime('%Y-%m-%d', $to));
        $country = $this->_config->get('holidays_country');
        if (class_exists('\\Checkdomain\\Holiday\\Provider\\' . $country)) {
            $util = new \Checkdomain\Holiday\Util;
            $region = $this->_config->get('holidays_region');

            do {
                if ($holiday = $util->getHoliday($country, $from, $region)) {
                    $events[] = array
                    (
                        'title' => $holiday->getName(),
                        'start' => $from->format('Y-m-d'),
                        'className' => array(),
                        'rendering' => 'background'
                    );
                }
                $from->modify('+1 day');
            } while ($from < $to);
        }
    }

    /**
     * Loads calendar events
     *
     * @param array $uids
     * @param int $from Start time
     * @param int $to End time
     */
    private function _load_events(array $uids, $from, $to)
    {
        $events = array();

        $mc = org_openpsa_calendar_event_member_dba::new_collector('eid.up', $this->_root_event->id);

        // Find all events that occur during [$from, $to]
        $mc->add_constraint('eid.start', '<=', $to);
        $mc->add_constraint('eid.end', '>=', $from);

        $mc->add_constraint('uid', 'IN', $uids);

        $memberships = $mc->get_rows(array('uid', 'eid'));

        if ($memberships) {
            // Customize label
            $label_field = $this->_config->get('event_label');
            if (!$label_field) {
                $label_field = 'title';
            }
            foreach ($memberships as $membership) {
                $event = org_openpsa_calendar_event_dba::get_cached($membership['eid']);

                if (!isset($events[$event->guid])) {
                    $label = $event->$label_field;
                    if ($label_field == 'creator') {
                        $user = midcom::get()->auth->get_user($event->metadata->creator);
                        $label = $user->name;
                    }

                    $events[$event->guid] = array
                    (
                        'id' => $event->guid,
                        'title' => $label,
                        'location' => $event->location,
                        'start' => strftime('%Y-%m-%dT%T', $event->start),
                        'end' => strftime('%Y-%m-%dT%T', $event->end),
                        'className' => array(),
                        'participants' => array(),
                        'allDay' => (($event->end - $event->start) > 8 * 60 * 60)
                    );
                    if ($event->orgOpenpsaAccesstype == org_openpsa_core_acl::ACCESS_PRIVATE) {
                        $events[$event->guid]['className'][] = 'private';
                    }
                }
                if ($membership['uid'] == midcom_connection::get_user()) {
                    $events[$event->guid]['participants'][] = $this->_l10n->get('me');
                    $events[$event->guid]['className'][] = 'paticipant_me';
                } else {
                    $person = org_openpsa_contacts_person_dba::get_cached($membership['uid']);
                    $events[$event->guid]['participants'][] = $person->get_label();
                }
                $events[$event->guid]['className'][] = 'paticipant_' . $membership['uid'];
            }
        }

        return array_values($events);
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

        $this->_load_datamanager();

        // Add toolbar items
        $buttons = array
        (
            array
            (
                MIDCOM_TOOLBAR_URL => 'event/edit/' . $this->_request_data['event']->guid . '/',
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('edit'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/edit.png',
                MIDCOM_TOOLBAR_ENABLED => $data['event']->can_do('midgard:update'),
                MIDCOM_TOOLBAR_ACCESSKEY => 'e',
            )
        );
        if ($data['event']->can_do('midgard:delete')) {
            $workflow = $this->get_workflow('delete', array('object' => $data['event']));
            $buttons[] = $workflow->get_button("event/delete/{$data['event']->guid}/");
        }
        $buttons[] = array
        (
            MIDCOM_TOOLBAR_URL => 'javascript:window.print()',
            MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('print'),
            MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/printer.png',
            MIDCOM_TOOLBAR_OPTIONS  => array('rel' => 'directlink')
        );

        $relatedto_button_settings = null;

        if (midcom::get()->auth->user) {
            $user = midcom::get()->auth->user->get_storage();
            $date = $this->_l10n->get_formatter()->date();
            $relatedto_button_settings = array
            (
                'wikinote'      => array
                (
                    'component' => 'net.nemein.wiki',
                    'node'  => false,
                    'wikiword'  => str_replace('/', '-', sprintf($this->_l10n->get($this->_config->get('wiki_title_skeleton')), $data['event']->title, $date, $user->name)),
                ),
            );
        }
        $this->_view_toolbar->add_items($buttons);
        org_openpsa_relatedto_plugin::common_node_toolbar_buttons($this->_view_toolbar, $data['event'], $this->_component, $relatedto_button_settings);

        midcom::get()->head->set_pagetitle(sprintf($this->_l10n->get('event %s'), $this->_request_data['event']->title));
        return $this->get_workflow('viewer')->run();
    }

    private function _load_datamanager()
    {
        // Load schema database
        $schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb'));
        $this->_datamanager = new midcom_helper_datamanager2_datamanager($schemadb);

        if (!$this->_request_data['event']->can_do('org.openpsa.calendar:read')) {
            $stat =    $this->_datamanager->set_schema('private')
                    && $this->_datamanager->set_storage($this->_request_data['event']);
        } else {
            $stat = $this->_datamanager->autoset_storage($this->_request_data['event']);
        }
        if (!$stat) {
            throw new midcom_error('Failed to load the event in datamanager');
        }
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
            $this->_request_data['title'] = sprintf($this->_l10n->get('event %s'), $this->_request_data['event']->title);

            // Show popup
            $this->_request_data['event_dm'] = $this->_datamanager;
            midcom_show_style('show-event');
        } else {
            midcom_show_style('show-event-raw');
        }
    }
}
