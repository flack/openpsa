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
     * The calendar widget we're working on
     *
     * @var org_openpsa_widgets_calendar
     */
    private $_calendar = null;

    /**
     * The calendar root event
     *
     * @var midcom_db_event
     */
    private $_root_event = null;

    /**
     * The time to show
     *
     * @var int
     */
    private $_selected_time = null;

    private $_shown_persons = array();

    /**
     * Initialization of the handler class
     */
    public function _on_initialize()
    {
        org_openpsa_widgets_calendar::add_head_elements();

        // Load schema database
        $schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb'));
        $this->_datamanager = new midcom_helper_datamanager2_datamanager($schemadb);

        $this->_root_event = org_openpsa_calendar_interface::find_root_event();
    }

    /**
     * Populate the toolbar
     *
     * @param string $path    Path to calendar
     */
    private function _populate_toolbar($path = null)
    {
        // 'New event' should always be in toolbar
        $nap = new midcom_helper_nav();
        $this_node = $nap->get_node($nap->get_current_node());
        if ($this->_root_event->can_do('midgard:create'))
        {
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => '#',
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('create event'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_new-event.png',
                    MIDCOM_TOOLBAR_OPTIONS  => array
                    (
                        'rel' => 'directlink',
                        'onclick' => org_openpsa_calendar_interface::calendar_newevent_js($this_node),
                    ),
                )
            );
        }

        if (method_exists($this->_calendar, 'get_' . $path . '_start'))
        {
            $previous = date('Y-m-d', call_user_func(array($this->_calendar, 'get_' . $path . '_start')) - 100);
            $next = date('Y-m-d', call_user_func(array($this->_calendar, 'get_' . $path . '_end')) + 100);
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => $path . '/' . $previous . '/',
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('previous'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/up.png',
                )
            );
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => $path . '/' . $next . '/',
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('next'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/down.png',
                )
            );
        }

        if (   $this->_topic->can_do('midgard:update')
            && $this->_topic->can_do('midcom:component_config'))
        {
            $this->_node_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => 'config/',
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('component configuration'),
                    MIDCOM_TOOLBAR_HELPTEXT => $this->_l10n_midcom->get('component configuration helptext'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_folder-properties.png',
                )
            );
        }

        midcom_helper_datamanager2_widget_jsdate::add_head_elements();
        midcom::get('head')->add_jsfile(MIDCOM_STATIC_URL . "/org.openpsa.calendar/navigation.js");

        $prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
        $default_date = date('Y-m-d', $this->_selected_time);

        midcom::get('head')->add_jscript('
var org_openpsa_calendar_default_date = "' . $default_date . '",
org_openpsa_calendar_prefix = "' . $prefix . $path . '";
        ');

        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => '#',
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('go to'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_jump-to.png',
                MIDCOM_TOOLBAR_OPTIONS  => array
                (
                    'rel' => 'directlink',
                    'id' => 'date-navigation',
                ),
            )
        );
        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "{$path}/" . $this->_get_datestring(time()) . '/',
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('today'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/web-calendar.png',
            )
        );
        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "filters/?org_openpsa_calendar_returnurl=" . midcom_connection::get_url('uri'),
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('choose calendars'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/preferences-desktop.png',
            )
        );
    }

    /**
     * Helper that formats a timestamp into a string
     *
     * @param int $from The timestamp, if any
     * @return string The formatted time
     */
    private function _get_datestring($from = false)
    {
        if (!$from)
        {
            $from = $this->_selected_time;
        }
        $datestring = date('Y-m-d', $from);
        return $datestring;
    }

    /**
     * Populate the calendar with resources
     *
     * @param midcom_db_person $resource
     * @param int $from Start time
     * @param int $to End time
     */
    private function _populate_calendar_resource($resource, $from, $to)
    {
        $resource_array = array
        (
            'name' => $resource->firstname . ' ' . $resource->lastname,
            'reservations' => array()
        );
        if ($resource->id == midcom_connection::get_user())
        {
            $resource_array['name'] = $this->_l10n->get('me');
            $resource_array['css_class'] = 'blue';
        }

        $qb = org_openpsa_calendar_event_member_dba::new_query_builder();

        // Find all events that occur during [$from, $end]
        $qb->begin_group('OR');
            // The event begins during [$from, $to]
            $qb->begin_group('AND');
                $qb->add_constraint('eid.start', '>=', $from);
                $qb->add_constraint('eid.start', '<=', $to);
            $qb->end_group();
            // The event begins before and ends after [$from, $to]
            $qb->begin_group('AND');
                $qb->add_constraint('eid.start', '<=', $from);
                $qb->add_constraint('eid.end', '>=', $to);
            $qb->end_group();
            // The event ends during [$from, $to]
            $qb->begin_group('AND');
                $qb->add_constraint('eid.end', '>=', $from);
                $qb->add_constraint('eid.end', '<=', $to);
            $qb->end_group();
        $qb->end_group();

        $qb->add_constraint('eid.up', '=', $this->_root_event->id);
        $qb->add_constraint('uid', '=', (int) $resource->id);

        $memberships = $qb->execute();

        if ($memberships)
        {
            foreach ($memberships as $membership)
            {
                $event = new org_openpsa_calendar_event_dba($membership->eid);

                // Customize label
                $label_field = $this->_config->get('event_label');
                if (!$label_field)
                {
                    $label_field = 'title';
                }
                $label = $event->$label_field;
                if ($label_field == 'creator')
                {
                    $user = midcom::get('auth')->get_user($event->metadata->creator);
                    $label = $user->name;
                }

                $resource_array['reservations'][$event->guid] = array
                (
                    'name' => $label,
                    'location' => $event->location,
                    'start' => $event->start,
                    'end' => $event->end,
                    'private' => false,
                );

                if ($event->orgOpenpsaAccesstype == org_openpsa_core_acl::ACCESS_PRIVATE)
                {
                    $resource_array['reservations'][$event->id]['css_class'] = ' private';
                    $resource_array['reservations'][$event->id]['private'] = true;
                }
            }
        }

        return $resource_array;
    }

    /**
     * Populate the calendar with selected contacts
     *
     * @param int $from    Start time
     * @param int $to      End time
     */
    private function _populate_calendar_contacts($from, $to)
    {
        $user = midcom::get('auth')->user->get_storage();

        if ($this->_config->get('always_show_self'))
        {
            // Populate the user himself first, but only if they can create events
            $this->_calendar->_resources[$user->guid] = $this->_populate_calendar_resource($user, $from, $to);
            $this->_shown_persons[$user->id] = true;
        }

        // Backwards compatibility
        if ($this->_config->get('always_show_group'))
        {
            // Add this group to display as well
            $additional_group = & midcom::get('auth')->get_group($this->_config->get('always_show_group'));
            if ($additional_group)
            {
                $members = $additional_group->list_members();
                foreach ($members as $person)
                {
                    if (array_key_exists($person->id, $this->_shown_persons))
                    {
                        continue;
                    }
                    $person_object = $person->get_storage();
                    $this->_calendar->_resources[$person_object->guid] = $this->_populate_calendar_resource($person_object, $from, $to);
                    $this->_shown_persons[$person->id] = true;
                }
            }
        }

        $this->_populate_calendar_from_filter($user, $from, $to);
    }

    private function _populate_calendar_from_filter($user, $from, $to)
    {
        // New UI for showing resources
        foreach ($user->list_parameters('org.openpsa.calendar.filters') as $type => $value)
        {
            $selected = @unserialize($value);

            // Skip empty
            if (empty($selected))
            {
                continue;
            }

            $persons = array();
            // Include each type
            switch ($type)
            {
                case 'people':
                    $qb = midcom_db_person::new_query_builder();
                    $qb->add_constraint('guid', 'IN', $selected);
                    $qb->add_constraint('id', 'NOT IN', array_keys($this->_shown_persons));
                    $persons = $qb->execute();
                    break;

                case 'groups':
                    $mc = midcom_db_group::new_collector('metadata.deleted', false);
                    $mc->add_constraint('guid', 'IN', $selected);
                    $gids = $mc->get_values('id');
                    if (!empty($gids))
                    {
                        $mc = midcom_db_member::new_collector('metadata.deleted', false);
                        $mc->add_constraint('uid', 'NOT IN', array_keys($this->_shown_persons));
                        $mc->add_constraint('gid', 'IN', $gids);
                        $mc->add_order('metadata.score', 'DESC');
                        $user_ids = $mc->get_values('uid');

                        if (!empty($user_ids))
                        {
                            $qb = midcom_db_person::new_query_builder();
                            $qb->add_constraint('id', 'IN', $user_ids);
                            $persons = $qb->execute();
                        }
                    }
                    break;
            }
            if (!empty($persons))
            {
                foreach ($persons as $person)
                {
                    $this->_calendar->_resources[$person->guid] = $this->_populate_calendar_resource($person, $from, $to);
                    $this->_shown_persons[$person->id] = true;
                }
            }
        }
    }

    private function _generate_date($args)
    {
        if (count($args) == 1)
        {
            // Go to the chosen week instead of current one
            // TODO: Check format as YYYY-MM-DD via regexp
            $requested_time = @strtotime($args[0]);
            if ($requested_time)
            {
                $this->_selected_time = $requested_time;
            }
            else
            {
                throw new midcom_error("Couldn't generate a date");
            }
        }
        else
        {
            $this->_selected_time = time();
        }
    }

    /**
     * Month view
     *
     * @param String $handler_id    Name of the request handler
     * @param array $args           Variable arguments
     * @param array &$data          Public request data, passed by reference
     */
    public function _handler_month($handler_id, array $args, array &$data)
    {
        midcom::get('auth')->require_valid_user();
        $this->_generate_date($args);

        // Instantiate calendar widget
        $this->_calendar = new org_openpsa_widgets_calendar(date('Y', $this->_selected_time), date('m', $this->_selected_time), date('d', $this->_selected_time));
        $this->_calendar->type = org_openpsa_widgets_calendar::MONTH;
        $this->_calendar->cell_height = 100;
        $this->_calendar->column_width = 60;

        $this->_populate_toolbar('month');
        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => 'week/' . $this->_get_datestring() . '/',
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('week view'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/properties.png',
            )
        );
        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => 'day/' . $this->_get_datestring() . '/',
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('day view'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/properties.png',
            )
        );

        // Clicking a free slot should bring up 'new event' dialogue
        $nap = new midcom_helper_nav();
        $this_node = $nap->get_node($nap->get_current_node());

        $this->_calendar->reservation_div_options = array
        (
            'onclick' => org_openpsa_calendar_interface::calendar_editevent_js('__GUID__', $this_node),
        );
        if ($this->_root_event->can_do('midgard:create'))
        {
            $this->_calendar->free_div_options = array
            (
                'onclick' => org_openpsa_calendar_interface::calendar_newevent_js($this_node, '__START__', '__RESOURCE__'),
            );
        }

        // Populate contacts
        $this->_populate_calendar_contacts($this->_calendar->get_month_start(), $this->_calendar->get_month_end());

        $this->_request_data['calendar'] =& $this->_calendar;

        // Set the breadcrumb
        $this->add_breadcrumb
        (
            'month/' . date('Y-m-01', $this->_calendar->get_week_start()) . '/',
            strftime('%B %Y', $this->_selected_time)
        );

        midcom::get('head')->set_pagetitle(strftime("%B %Y", $this->_selected_time));
    }

    /**
     * Show the month view
     *
     * @param String $handler_id    Name of the request handler
     * @param array &$data          Public request data, passed by reference
     */
    public function _show_month($handler_id, array &$data)
    {
        $this->_request_data['selected_time'] = $this->_selected_time;
        midcom_show_style('show-month');
    }

    /**
     * Week view
     *
     * @param String $handler_id    Name of the request handler
     * @param array $args           Variable arguments
     * @param array &$data          Public request data, passed by reference
     */
    public function _handler_week($handler_id, array $args, array &$data)
    {
        midcom::get('auth')->require_valid_user();
        $this->_generate_date($args);

        // Instantiate calendar widget
        $this->_calendar = new org_openpsa_widgets_calendar(date('Y', $this->_selected_time), date('m', $this->_selected_time), date('d', $this->_selected_time));

        // Slots are 2 hours long
        $this->_calendar->calendar_slot_length = $this->_config->get('week_slot_length') * 60;
        $this->_calendar->start_hour = $this->_config->get('day_start_time');
        $this->_calendar->end_hour = $this->_config->get('day_end_time');

        $this->_populate_toolbar('week');
        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => 'month/' . $this->_get_datestring() . '/',
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('month view'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/properties.png',
            )
        );
        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => 'day/' . $this->_get_datestring() . '/',
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('day view'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/properties.png',
            )
        );

        // Clicking a free slot should bring up 'new event' dialogue
        $nap = new midcom_helper_nav();
        $this_node = $nap->get_node($nap->get_current_node());

        $this->_calendar->reservation_div_options = array
        (
            'onclick' => org_openpsa_calendar_interface::calendar_editevent_js('__GUID__', $this_node),
        );
        if ($this->_root_event->can_do('midgard:create'))
        {
            $this->_calendar->free_div_options = array
            (
                'onclick' => org_openpsa_calendar_interface::calendar_newevent_js($this_node, '__START__', '__RESOURCE__'),
            );
        }

        $week_start = $this->_calendar->get_week_start();
        $week_end = $this->_calendar->get_week_end();

        // Populate contacts
        $this->_populate_calendar_contacts($week_start, $week_end);

        $this->_request_data['calendar'] =& $this->_calendar;

        // Set the breadcrumb
        $this->add_breadcrumb('month/' . date('Y-m-01', $week_start) . '/', strftime('%B %Y', $week_start));
        $this->add_breadcrumb
        (
            'week/' . date('Y-m-d', $week_start) . '/',
            sprintf($this->_l10n->get("week #%s %s"), strftime("%W", $week_start), strftime("%Y", $week_start))
        );

        midcom::get('head')->set_pagetitle(sprintf($this->_l10n->get("week #%s %s"), strftime("%W", $this->_selected_time), strftime("%Y", $this->_selected_time)));
    }

    /**
     * Show the week view
     *
     * @param string $handler_id    Name of the request handler
     * @param array &$data          Public request data, passed by reference
     */
    public function _show_week($handler_id, array &$data)
    {
        $this->_request_data['selected_time'] = $this->_selected_time;
        midcom_show_style('show-week');
    }

    /**
     * Day view
     *
     * @param String $handler_id    Name of the request handler
     * @param array $args           Variable arguments
     * @param array &$data          Public request data, passed by reference
     */
    public function _handler_day($handler_id, array $args, array &$data)
    {
        midcom::get('auth')->require_valid_user();
        $this->_generate_date($args);

        // Instantiate calendar widget
        $this->_calendar = new org_openpsa_widgets_calendar(date('Y', $this->_selected_time), date('m', $this->_selected_time), date('d', $this->_selected_time));
        $this->_calendar->type = org_openpsa_widgets_calendar::DAY;

        // Slots are 2 hours long
        $this->_calendar->calendar_slot_length = $this->_config->get('day_slot_length') * 60;
        $this->_calendar->start_hour = $this->_config->get('day_start_time');
        $this->_calendar->end_hour = $this->_config->get('day_end_time');
        $this->_calendar->column_width = 60;

        $this->_populate_toolbar('day');
        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => 'month/' . $this->_get_datestring() . '/',
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('month view'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/properties.png',
            )
        );
        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => 'week/' . $this->_get_datestring() . '/',
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('week view'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/properties.png',
            )
        );

        // Clicking a free slot should bring up 'new event' dialogue
        $nap = new midcom_helper_nav();
        $this_node = $nap->get_node($nap->get_current_node());

        if ($this->_root_event->can_do('midgard:create'))
        {
            $this->_calendar->reservation_div_options = array
            (
                'onclick' => org_openpsa_calendar_interface::calendar_editevent_js('__GUID__', $this_node),
            );
        }
        $this->_calendar->free_div_options = array
        (
            'onclick' => org_openpsa_calendar_interface::calendar_newevent_js($this_node, '__START__', '__RESOURCE__'),
        );

        // Populate contacts
        $this->_populate_calendar_contacts($this->_calendar->get_day_start(), $this->_calendar->get_day_end());

        $this->_request_data['calendar'] =& $this->_calendar;

        // Set the breadcrumb
        $this->add_breadcrumb('month/' . date('Y-m-01', $this->_selected_time) . '/', strftime('%B %Y', $this->_selected_time));
        $this->add_breadcrumb('day/' . date('Y-m-d', $this->_selected_time) . '/', strftime('%x', $this->_selected_time));

        midcom::get('head')->set_pagetitle(strftime("%x", $this->_selected_time));
    }

    /**
     * Show day view
     *
     * @param String $handler_id    Name of the request handler
     * @param array &$data          Public request data, passed by reference
     */
    public function _show_day($handler_id, array &$data)
    {
        $this->_request_data['selected_time'] = $this->_selected_time;
        midcom_show_style('show-day');
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
        // We're using a popup here
        midcom::get()->skip_page_style = true;

        // Get the requested event object
        $this->_request_data['event'] = new org_openpsa_calendar_event_dba($args[0]);

        // Muck schema on private events
        if (!$this->_request_data['event']->can_do('org.openpsa.calendar:read'))
        {
            foreach ($this->_datamanager->_schemadb as $schemaname => $schema)
            {
                foreach ($this->_datamanager->_schemadb[$schemaname]->fields as $fieldname => $field)
                {
                    switch ($fieldname)
                    {
                        case 'title':
                        case 'start':
                        case 'end':
                            break;
                        default:
                            $this->_datamanager->_schemadb[$schemaname]->fields[$fieldname]['hidden'] = true;
                    }
                }
            }
        }

        // Load the event to datamanager
        if (!$this->_datamanager->autoset_storage($data['event']))
        {
            throw new midcom_error('Failed to load the event in datamanager');
        }

        // Reload parent if needed
        if (array_key_exists('reload', $_GET))
        {
            midcom::get('head')->add_jsonload('window.opener.location.reload();');
        }

        // Add toolbar items
        if ($this->_request_data['view'] == 'default')
        {
            $this->_view_toolbar->add_item
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
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => 'event/delete/' . $this->_request_data['event']->guid . '/',
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('delete'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/trash.png',
                    MIDCOM_TOOLBAR_ENABLED => $data['event']->can_do('midgard:delete'),
                )
            );
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => 'javascript:window.print()',
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('print'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/printer.png',
                    MIDCOM_TOOLBAR_OPTIONS  => array
                    (
                        'rel' => 'directlink',
                    ),
                )
            );

            $relatedto_button_settings = null;

            if (midcom::get('auth')->user)
            {
                $user = midcom::get('auth')->user->get_storage();
                $relatedto_button_settings = array
                (
                    'wikinote'      => array
                    (
                        'node'  => false,
                        'wikiword'  => str_replace('/', '-', sprintf($this->_l10n->get($this->_config->get('wiki_title_skeleton')), $this->_request_data['event']->title, strftime('%x'), $user->name)),
                    ),
                );
            }
            org_openpsa_relatedto_plugin::common_node_toolbar_buttons($this->_view_toolbar, $this->_request_data['event'], $this->_component, $relatedto_button_settings);
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
        if ($handler_id == 'event_view')
        {
            // Set title to popup
            $this->_request_data['popup_title'] = sprintf($this->_l10n->get('event %s'), $this->_request_data['event']->title);

            // Show popup
            midcom_show_style('show-popup-header');
            $this->_request_data['event_dm'] =& $this->_datamanager;
            midcom_show_style('show-event');
            midcom_show_style('show-popup-footer');
        }
        else
        {
            midcom_show_style('show-event-raw');
        }
    }
}
?>
