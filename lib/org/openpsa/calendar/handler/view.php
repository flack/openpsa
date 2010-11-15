<?php
/**
 * @package org.openpsa.calendar
 * @author Nemein Oy http://www.nemein.com/
 * @version $Id: view.php 25755 2010-04-23 23:12:11Z flack $
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
     * @access private
     * @var midcom_helper_datamanager2_datamanager
     */
    var $_datamanager;

    /**
     * The calendar widget we're working on
     *
     * @var org_openpsa_calendarwidget
     * @access private
     */
    private $_calendar = null;

    /**
     * The calendar root event
     *
     * @var midcom_db_event
     * @access private
     */
    private $_root_event = null;

    /**
     * The time to show
     *
     * @var int
     * @access private
     */
    private $_selected_time = null;

    /**
     * Constructor. Connect to the parent class constructor.
     */
    function __construct()
    {
        parent::__construct();
    }

    /**
     * Initialization of the handler class
     */
    function _on_initialize()
    {
        $_MIDCOM->load_library('org.openpsa.calendarwidget');
        $_MIDCOM->load_library('midcom.helper.datamanager2');

        // Load schema database
        $schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb'));

        $this->_datamanager = new midcom_helper_datamanager2_datamanager($schemadb);

        $this->_root_event = org_openpsa_calendar_interface::find_root_event();
    }

    /**
     * Populate the toolbar
     *
     * @access private
     * @param String $today_path    Path to the today's calendar
     */
    private function _populate_toolbar($path = null)
    {
        // 'New event' should always be in toolbar
        $nap = new midcom_helper_nav();
        $this_node = $nap->get_node($nap->get_current_node());
        if ($_MIDCOM->auth->can_do('midgard:create', $this->_root_event))
        {
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => '#',
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('create event'),
                    MIDCOM_TOOLBAR_HELPTEXT => null,
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_new-event.png',
                    MIDCOM_TOOLBAR_ENABLED => true,
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
                    MIDCOM_TOOLBAR_HELPTEXT => null,
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/up.png',
                    MIDCOM_TOOLBAR_ENABLED => true,
                )
            );
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => $path . '/' . $next . '/',
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('next'),
                    MIDCOM_TOOLBAR_HELPTEXT => null,
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/down.png',
                    MIDCOM_TOOLBAR_ENABLED => true,
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

        $prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
        $static_prefix = MIDCOM_STATIC_URL . '/midcom.helper.datamanager2/jscript-calendar';
        $lang = $_MIDCOM->i18n->get_current_language();

        $_MIDCOM->add_jsfile("{$static_prefix}/calendar_stripped.js");
        $_MIDCOM->add_jsfile("{$static_prefix}/lang/calendar-{$lang}.js");
        $_MIDCOM->add_jsfile("{$static_prefix}/calendar-setup.js");

        $dateopts = date('Y', $this->_selected_time).', ';
        $dateopts .= date('n', $this->_selected_time) - 1;
        $dateopts .= ', ' . date('j', $this->_selected_time);

        $_MIDCOM->add_jscript('
var openPsaShowMonthSelectorCalendarShown = false;
var openPsaShowMonthSelectorCalendarInitialized = false;
function openPsaShowMonthSelectorHandler(calendar)
{
    if (calendar.dateClicked)
    {
        var y = calendar.date.getFullYear();
        var m = new String(calendar.date.getMonth() + 1);
        if (m.length == 1)
        {
            m = \'0\' + m;
        }
        var d = calendar.date.getDate();
        // Relocate to correct view
        window.location = "' . $prefix . $path . '/" + y + "-" + m + "-" + d + "/";
    }
}

function openPsaShowMonthSelectorCalendar()
{
    Calendar.setup
    (
        {
            flat        : "org_openpsa_calendar_calendarwidget",
            flatCallback: openPsaShowMonthSelectorHandler,
            firstDay    : 1,
            date        : new Date(' . $dateopts . ')
        }
    );
}

function openPsaShowMonthSelector()
{
    calendarArea = document.getElementById("org_openpsa_calendar_calendarwidget");
    if (openPsaShowMonthSelectorCalendarShown)
    {
        calendarArea.style.display = "none";
        openPsaShowMonthSelectorCalendarShown = false;
    }
    else
    {
        calendarArea.style.display = "block";
        if (!openPsaShowMonthSelectorCalendarInitialized)
        {
            openPsaShowMonthSelectorCalendar();
            openPsaShowMonthSelectorCalendarInitialized = true;
        }
        openPsaShowMonthSelectorCalendarShown = true;
    }
}
        ');

        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => '#',
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('go to'),
                MIDCOM_TOOLBAR_HELPTEXT => null,
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/web-calendar.png',
                MIDCOM_TOOLBAR_ENABLED => true,
                MIDCOM_TOOLBAR_OPTIONS  => array
                (
                    'rel' => 'directlink',
                    'onclick' => 'javascript:openPsaShowMonthSelector();',
                ),
            )
        );
        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "{$path}/" . $this->_get_datestring(time()) . '/',
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('today'),
                MIDCOM_TOOLBAR_HELPTEXT => null,
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/web-calendar.png',
                MIDCOM_TOOLBAR_ENABLED => true,
            )
        );
        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "filters/?org_openpsa_calendar_returnurl={$_MIDGARD['uri']}",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('choose calendars'),
                MIDCOM_TOOLBAR_HELPTEXT => null,
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/properties.png',
                MIDCOM_TOOLBAR_ENABLED => true,
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
     * @access private
     * @param midcom_db_person $resource
     * @param int $from Start time
     * @param int $to End time
     */
    private function _populate_calendar_resource($resource, $from, $to)
    {
        $resource_array = array
        (
            'name' => $resource->firstname . ' ' . $resource->lastname,
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
                    $user = $_MIDCOM->auth->get_user($event->metadata->creator);
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

                if ($event->orgOpenpsaAccesstype == ORG_OPENPSA_ACCESSTYPE_PRIVATE)
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
     * @access private
     * @param int $from    Start time
     * @param int $to      End time
     */
    private function _populate_calendar_contacts($from, $to)
    {
        $shown_persons = array();

        $user = $_MIDCOM->auth->user->get_storage();

        if (   $this->_config->get('always_show_self')
            || $user->parameter('org_openpsa_calendar_show', $user->guid))
        {
            // Populate the user himself first, but only if they can create events
            $this->_calendar->_resources[$user->guid] = $this->_populate_calendar_resource($user, $from, $to);
        }

        $shown_persons[$user->id] = true;

        $subscribed_contacts = $user->list_parameters('org_openpsa_calendar_show');

        // Backwards compatibility
        foreach ($subscribed_contacts as $guid => $subscribed)
        {
            $person = new midcom_db_person($guid);
            $this->_calendar->_resources[$person->guid] = $this->_populate_calendar_resource($person, $from, $to);
            $shown_persons[$person->id] = true;
        }

        // Backwards compatibility
        if ($this->_config->get('always_show_group'))
        {
            // Add this group to display as well
            $additional_group = & $_MIDCOM->auth->get_group($this->_config->get('always_show_group'));
            if ($additional_group)
            {
                $members = $additional_group->list_members();
                foreach ($members as $person)
                {
                    if (array_key_exists($person->id, $shown_persons))
                    {
                        continue;
                    }
                    $person_object = $person->get_storage();
                    $this->_calendar->_resources[$person_object->guid] = $this->_populate_calendar_resource($person_object, $from, $to);
                    $shown_persons[$person->id] = true;
                }
            }
        }

        // New UI for showing resources
        foreach ($user->list_parameters('org.openpsa.calendar.filters') as $type => $value)
        {
            $selected = @unserialize($value);

            // Skip empty
            if (   !$selected
                || empty($selected))
            {
                continue;
            }

            // Include each type
            switch ($type)
            {
                case 'people':
                    foreach ($selected as $guid)
                    {
                        $person = new midcom_db_person($guid);

                        if (   isset($shown_persons[$person->id])
                            && $shown_persons[$person->id] === true)
                        {
                            continue;
                        }

                        $this->_calendar->_resources[$person->guid] = $this->_populate_calendar_resource($person, $from, $to);
                        $shown_persons[$person->id] = true;
                    }
                    break;

                case 'groups':
                    foreach ($selected as $guid)
                    {
                        // Get the group
                        $group = new midcom_db_group($guid);

                        if (   !$group
                            || !$group->guid)
                        {
                            continue;
                        }

                        // Get the members
                        $mc = midcom_db_member::new_collector('gid', $group->id);
                        $mc->add_value_property('uid');
                        $mc->add_order('metadata.score', 'DESC');
                        $mc->execute();

                        $keys = $mc->list_keys();

                        foreach ($keys as $membership_guid => $array)
                        {
                            $user_id = $mc->get_subkey($membership_guid, 'uid');

                            if (   isset($shown_persons[$user_id])
                                && $shown_persons[$user_id] === true)
                            {
                                continue;
                            }

                            $person = new midcom_db_person($user_id);
                            $this->_calendar->_resources[$person->guid] = $this->_populate_calendar_resource($person, $from, $to);
                            $shown_persons[$person->id] = true;
                        }
                    }
                    break;
            }
        }
    }

    function _load_event($guid)
    {
        $event = new org_openpsa_calendar_event_dba($guid);
        if (   !is_object($event)
            || $event->guid == "")
        {
            return false;
        }
        return $event;
    }

    /**
     * Month view
     *
     * @access public
     * @param String $handler_id    Name of the request handler
     * @param array $args           Variable arguments
     * @param array &$data          Public request data, passed by reference
     * @return boolean              Indicating success
     */
    function _handler_month($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_valid_user();
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
                // We couldn't generate a date
                return false;
            }
        }

        // Instantiate calendar widget
        $this->_calendar = new org_openpsa_calendarwidget(date('Y', $this->_selected_time), date('m', $this->_selected_time), date('d', $this->_selected_time));
        $this->_calendar->type = ORG_OPENPSA_CALENDARWIDGET_MONTH;
        $this->_calendar->cell_height = 100;
        $this->_calendar->column_width = 60;

        $this->_populate_toolbar('month');
        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => 'week/' . $this->_get_datestring() . '/',
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('week view'),
                MIDCOM_TOOLBAR_HELPTEXT => null,
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/properties.png',
                MIDCOM_TOOLBAR_ENABLED => true,
            )
        );
        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => 'day/' . $this->_get_datestring() . '/',
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('day view'),
                MIDCOM_TOOLBAR_HELPTEXT => null,
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/properties.png',
                MIDCOM_TOOLBAR_ENABLED => true,
            )
        );

        // Clicking a free slot should bring up 'new event' dialogue
        $nap = new midcom_helper_nav();
        $this_node = $nap->get_node($nap->get_current_node());

        $this->_calendar->reservation_div_options = array
        (
            'onclick' => org_openpsa_calendar_interface::calendar_editevent_js('__GUID__', $this_node),
        );
        if ($_MIDCOM->auth->can_do('midgard:create', $this->_root_event))
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
        $tmp = array();

        $tmp[] = array
        (
            MIDCOM_NAV_URL => 'year/' . date('Y-01-01', $this->_calendar->get_week_start()) . '/',
            MIDCOM_NAV_NAME => strftime('%Y', $this->_selected_time),
        );
        $tmp[] = array
        (
            MIDCOM_NAV_URL => 'month/' . date('Y-m-01', $this->_calendar->get_week_start()) . '/',
            MIDCOM_NAV_NAME => strftime('%B', $this->_selected_time),
        );

        $_MIDCOM->set_custom_context_data('midcom.helper.nav.breadcrumb', $tmp);

        $_MIDCOM->set_pagetitle(strftime("%B %Y", $this->_selected_time));

        return true;
    }

    /**
     * Show the month view
     *
     * @access public
     * @param String $handler_id    Name of the request handler
     * @param array &$data          Public request data, passed by reference
     */
    function _show_month($handler_id, &$data)
    {
        $this->_request_data['selected_time'] = $this->_selected_time;
        midcom_show_style('show-month');
    }

    /**
     * Week view
     *
     * @access public
     * @param String $handler_id    Name of the request handler
     * @param array $args           Variable arguments
     * @param array &$data          Public request data, passed by reference
     * @return boolean              Indicating success
     */
    function _handler_week($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_valid_user();
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
                // We couldn't generate a date
                return false;
            }
        }

        // Instantiate calendar widget
        $this->_calendar = new org_openpsa_calendarwidget(date('Y', $this->_selected_time), date('m', $this->_selected_time), date('d', $this->_selected_time));

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
                MIDCOM_TOOLBAR_HELPTEXT => null,
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/properties.png',
                MIDCOM_TOOLBAR_ENABLED => true,
            )
        );
        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => 'day/' . $this->_get_datestring() . '/',
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('day view'),
                MIDCOM_TOOLBAR_HELPTEXT => null,
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/properties.png',
                MIDCOM_TOOLBAR_ENABLED => true,
            )
        );

        // Clicking a free slot should bring up 'new event' dialogue
        $nap = new midcom_helper_nav();
        $this_node = $nap->get_node($nap->get_current_node());

        $this->_calendar->reservation_div_options = array
        (
            'onclick' => org_openpsa_calendar_interface::calendar_editevent_js('__GUID__', $this_node),
        );
        if ($_MIDCOM->auth->can_do('midgard:create', $this->_root_event))
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
        $tmp = array();

        $tmp[] = array
        (
            MIDCOM_NAV_URL => 'year/' . date('Y-01-01', $week_start) . '/',
            MIDCOM_NAV_NAME => strftime('%Y', $week_start),
        );
        $tmp[] = array
        (
            MIDCOM_NAV_URL => 'month/' . date('Y-m-01', $week_start) . '/',
            MIDCOM_NAV_NAME => strftime('%B', $week_start),
        );
        $tmp[] = array
        (
            MIDCOM_NAV_URL => "week/{$args[0]}/",
            MIDCOM_NAV_NAME => sprintf($this->_l10n->get("week #%s %s"), strftime("%W", $week_start), strftime("%Y", $week_start)),
        );

        $_MIDCOM->set_custom_context_data('midcom.helper.nav.breadcrumb', $tmp);

        $_MIDCOM->set_pagetitle(sprintf($this->_l10n->get("week #%s %s"), strftime("%W", $this->_selected_time), strftime("%Y", $this->_selected_time)));

        return true;
    }

    /**
     * Show the week view
     *
     * @access public
     * @param String $handler_id    Name of the request handler
     * @param array $args           Variable arguments
     * @param array &$data          Public request data, passed by reference
     * @return boolean              Indicating success
     */
    function _show_week($handler_id, &$data)
    {
        $this->_request_data['selected_time'] = $this->_selected_time;
        midcom_show_style('show-week');
    }

    /**
     * Day view
     *
     * @access public
     * @param String $handler_id    Name of the request handler
     * @param array $args           Variable arguments
     * @param array &$data          Public request data, passed by reference
     * @return boolean              Indicating success
     */
    function _handler_day($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_valid_user();
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
                // We couldn't generate a date
                return false;
            }
        }

        // Instantiate calendar widget
        $this->_calendar = new org_openpsa_calendarwidget(date('Y', $this->_selected_time), date('m', $this->_selected_time), date('d', $this->_selected_time));
        $this->_calendar->type = ORG_OPENPSA_CALENDARWIDGET_DAY;

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
                MIDCOM_TOOLBAR_HELPTEXT => null,
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/properties.png',
                MIDCOM_TOOLBAR_ENABLED => true,
            )
        );
        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => 'week/' . $this->_get_datestring() . '/',
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('week view'),
                MIDCOM_TOOLBAR_HELPTEXT => null,
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/properties.png',
                MIDCOM_TOOLBAR_ENABLED => true,
            )
        );

        // Clicking a free slot should bring up 'new event' dialogue
        $nap = new midcom_helper_nav();
        $this_node = $nap->get_node($nap->get_current_node());

        if ($_MIDCOM->auth->can_do('midgard:create', $this->_root_event))
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
        $tmp = array();

        $tmp[] = array
        (
            MIDCOM_NAV_URL => 'year/' . date('Y-01-01', $this->_selected_time) . '/',
            MIDCOM_NAV_NAME => strftime('%Y', $this->_selected_time),
        );
        $tmp[] = array
        (
            MIDCOM_NAV_URL => 'month/' . date('Y-m-01', $this->_selected_time) . '/',
            MIDCOM_NAV_NAME => strftime('%B', $this->_selected_time),
        );
        $tmp[] = array
        (
            MIDCOM_NAV_URL => "day/{$args[0]}/",
            MIDCOM_NAV_NAME => strftime('%x', $this->_selected_time),
        );
        $_MIDCOM->set_custom_context_data('midcom.helper.nav.breadcrumb', $tmp);

        $_MIDCOM->set_pagetitle(strftime("%x", $this->_selected_time));

        return true;
    }

    /**
     * Show day view
     *
     * @access public
     * @param String $handler_id    Name of the request handler
     * @param array &$data          Public request data, passed by reference
     */
    function _show_day($handler_id, &$data)
    {
        $this->_request_data['selected_time'] = $this->_selected_time;
        midcom_show_style('show-day');
    }

    /**
     * Handle the single event view
     *
     * @access public
     * @param String $handler_id    Name of the request handler
     * @param array $args           Variable arguments
     * @param array &$data          Public request data, passed by reference
     * @return boolean              Indicating success
     */
    function _handler_event($handler_id, $args, &$data)
    {
        // We're using a popup here
        $_MIDCOM->skip_page_style = true;

        // Get the requested event object
        $this->_request_data['event'] = $this->_load_event($args[0]);
        if (!$this->_request_data['event'])
        {
            return false;
        }
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
            return false;
        }

        // Reload parent if needed
        if (array_key_exists('reload', $_GET))
        {
            $_MIDCOM->add_jsonload('window.opener.location.reload();');
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
                    MIDCOM_TOOLBAR_HELPTEXT => null,
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/edit.png',
                    MIDCOM_TOOLBAR_ENABLED => $_MIDCOM->auth->can_do('midgard:update', $this->_request_data['event']),
                    MIDCOM_TOOLBAR_ACCESSKEY => 'e',
                )
            );
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => 'event/delete/' . $this->_request_data['event']->guid . '/',
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('delete'),
                    MIDCOM_TOOLBAR_HELPTEXT => null,
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/trash.png',
                    MIDCOM_TOOLBAR_ENABLED => $_MIDCOM->auth->can_do('midgard:delete', $this->_request_data['event']),
                )
            );
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => 'javascript:window.print()',
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('print'),
                    MIDCOM_TOOLBAR_HELPTEXT => null,
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/printer.png',
                    MIDCOM_TOOLBAR_ENABLED => true,
                    MIDCOM_TOOLBAR_OPTIONS  => array
                    (
                        'rel' => 'directlink',
                    ),
                )
            );

            $relatedto_button_settings = null;

            if ($_MIDCOM->auth->user)
            {
                $user = $_MIDCOM->auth->user->get_storage();
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
        return true;
    }


    /**
     * Show a single event
     *
     * @access public
     * @param String $handler_id    Name of the request handler
     * @param array &$data          Public request data, passed by reference
     */
    function _show_event($handler_id, &$data)
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