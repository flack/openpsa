// Match /month/<date>
'month_view_with_date' => array
(
    'handler' => array('org_openpsa_calendar_handler_view', 'month'),
    'fixed_args' => 'month',
    'variable_args' => 1,
),
// Match /month/
'month_view' => array
(
    'handler' => array('org_openpsa_calendar_handler_view', 'month'),
    'fixed_args' => 'month',
),
// Match /week/<date>
'week_view_with_date' => array
(
    'handler' => array('org_openpsa_calendar_handler_view', 'week'),
    'fixed_args' => 'week',
    'variable_args' => 1,
),
// Match /week/
'week_view' => array
(
    'handler' => array('org_openpsa_calendar_handler_view', 'week'),
    'fixed_args' => 'week',
),
// Match /day/<date>
'day_view_with_date' => array
(
    'handler' => array('org_openpsa_calendar_handler_view', 'day'),
    'fixed_args' => 'day',
    'variable_args' => 1,
),
// Match /day/
'day_view' => array
(
    'handler' => array('org_openpsa_calendar_handler_view', 'day'),
    'fixed_args' => 'day',
),
// Match /event/new/<person_guid>/<timestamp>
'new_event_for_person_with_time' => array
(
    'handler' => array('org_openpsa_calendar_handler_create', 'create'),
    'fixed_args' => array('event', 'new'),
    'variable_args' => 2,
),
// Match /event/new/<person_guid>
'new_event_for_person' => array
(
    'handler' => array('org_openpsa_calendar_handler_create', 'create'),
    'fixed_args' => array('event', 'new'),
    'variable_args' => 1,
),
// Match /event/new
'new_event' => array
(
    'handler' => array('org_openpsa_calendar_handler_create', 'create'),
    'fixed_args' => array('event', 'new'),
),
// Match /event/raw/<guid>
'event_view_raw' => array
(
    'handler' => array('org_openpsa_calendar_handler_view', 'event'),
    'fixed_args' => array('event', 'raw'),
    'variable_args' => 1,
),
// Match /event/edit/<guid>
'event_edit' => array
(
    'handler' => array('org_openpsa_calendar_handler_admin', 'edit'),
    'fixed_args' => array('event', 'edit'),
    'variable_args' => 1,
),
// Match /event/delete/<guid>
'event_delete' => array
(
    'handler' => array('org_openpsa_calendar_handler_admin', 'delete'),
    'fixed_args' => array('event', 'delete'),
    'variable_args' => 1,
),

// Match /event/<guid>
'event_view' => array
(
    'handler' => array('org_openpsa_calendar_handler_view', 'event'),
    'fixed_args' => 'event',
    'variable_args' => 1,
),

// This will redirect to the selected mode
// Match /
'frontpage' => array
(
    'handler' => 'frontpage',
),

// Match /filters
'filters_edit' => array
(
    'handler' => Array('org_openpsa_calendar_handler_filters', 'edit'),
    'fixed_args' => Array('filters'),
),

// Match /agenda/day/<timestamp>
'agenda_day' => array
(
    'handler' => Array('org_openpsa_calendar_handler_agenda', 'day'),
    'fixed_args' => Array('agenda', 'day'),
    'variable_args' => 1,
),

// Match /ical/events/<username>
'ical_user_feed' => array
(
    'handler' => Array('org_openpsa_calendar_handler_ical', 'user_events'),
    'fixed_args' => Array('ical', 'events'),
    'variable_args' => 1,
),

// Match /ical/busy/<username>
'ical_user_busy' => array
(
    'handler' => Array('org_openpsa_calendar_handler_ical', 'user_busy'),
    'fixed_args' => Array('ical', 'busy'),
    'variable_args' => 1,
),

// Match /config/
'config' => Array
(
    'handler' => Array('midcom_baseclasses_components_handler_configuration', 'config'),
    'fixed_args' => Array('config'),
),
