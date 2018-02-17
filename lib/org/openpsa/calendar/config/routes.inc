// This will redirect to the selected mode
// Match /
'frontpage' => array
(
    'handler' => array('org_openpsa_calendar_handler_calendar', 'frontpage'),
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
// Match /event/new
'new_event' => array
(
    'handler' => array('org_openpsa_calendar_handler_event_create', 'create'),
    'fixed_args' => array('event', 'new'),
),
// Match /event/new/<resource>
'new_event_with_resource' => array
(
    'handler' => array('org_openpsa_calendar_handler_event_create', 'create'),
    'fixed_args' => array('event', 'new'),
    'variable_args' => 1,
),
// Match /event/raw/<guid>
'event_view_raw' => array
(
    'handler' => array('org_openpsa_calendar_handler_event_view', 'event'),
    'fixed_args' => array('event', 'raw'),
    'variable_args' => 1,
),
// Match /event/edit/<guid>
'event_edit' => array
(
    'handler' => array('org_openpsa_calendar_handler_event_admin', 'edit'),
    'fixed_args' => array('event', 'edit'),
    'variable_args' => 1,
),
// Match /event/delete/<guid>
'event_delete' => array
(
    'handler' => array('org_openpsa_calendar_handler_event_admin', 'delete'),
    'fixed_args' => array('event', 'delete'),
    'variable_args' => 1,
),
// Match /event/move/<guid>
'event_move' => array
(
    'handler' => array('org_openpsa_calendar_handler_event_admin', 'move'),
    'fixed_args' => array('event', 'move'),
    'variable_args' => 1,
),
// Match /event/<guid>
'event_view' => array
(
    'handler' => array('org_openpsa_calendar_handler_event_view', 'event'),
    'fixed_args' => 'event',
    'variable_args' => 1,
),

// Match /resource/new
'new_resource' => array
(
    'handler' => array('org_openpsa_calendar_handler_resource', 'create'),
    'fixed_args' => array('resource', 'new'),
),

// Match /json/
'calendar_view_json' => array
(
    'handler' => array('org_openpsa_calendar_handler_calendar', 'json'),
    'fixed_args' => 'json',
),
// Match /<mode>/<date>
'calendar_view_mode_date' => array
(
    'handler' => array('org_openpsa_calendar_handler_calendar', 'calendar'),
    'variable_args' => 2,
),

// Match /config/
'config' => Array
(
    'handler' => Array('midcom_baseclasses_components_handler_configuration', 'config'),
    'fixed_args' => Array('config'),
),
