// This will redirect to the selected mode
// Match /
'frontpage' => [
    'handler' => ['org_openpsa_calendar_handler_calendar', 'frontpage'],
],

// Match /filters
'filters_edit' => [
    'handler' => ['org_openpsa_calendar_handler_filters', 'edit'],
    'fixed_args' => ['filters'],
],

// Match /agenda/day/<timestamp>
'agenda_day' => [
    'handler' => ['org_openpsa_calendar_handler_calendar', 'day'],
    'fixed_args' => ['agenda', 'day'],
    'variable_args' => 1,
],

// Match /ical/events/<username>
'ical_user_feed' => [
    'handler' => ['org_openpsa_calendar_handler_ical', 'user_events'],
    'fixed_args' => ['ical', 'events'],
    'variable_args' => 1,
],

// Match /ical/busy/<username>
'ical_user_busy' => [
    'handler' => ['org_openpsa_calendar_handler_ical', 'user_busy'],
    'fixed_args' => ['ical', 'busy'],
    'variable_args' => 1,
],
// Match /event/new
'new_event' => [
    'handler' => ['org_openpsa_calendar_handler_event_create', 'create'],
    'fixed_args' => ['event', 'new'],
],
// Match /event/new/<resource>
'new_event_with_resource' => [
    'handler' => ['org_openpsa_calendar_handler_event_create', 'create'],
    'fixed_args' => ['event', 'new'],
    'variable_args' => 1,
],
// Match /event/raw/<guid>
'event_view_raw' => [
    'handler' => ['org_openpsa_calendar_handler_event_view', 'event'],
    'fixed_args' => ['event', 'raw'],
    'variable_args' => 1,
],
// Match /event/edit/<guid>
'event_edit' => [
    'handler' => ['org_openpsa_calendar_handler_event_admin', 'edit'],
    'fixed_args' => ['event', 'edit'],
    'variable_args' => 1,
],
// Match /event/delete/<guid>
'event_delete' => [
    'handler' => ['org_openpsa_calendar_handler_event_admin', 'delete'],
    'fixed_args' => ['event', 'delete'],
    'variable_args' => 1,
],
// Match /event/move/<guid>
'event_move' => [
    'handler' => ['org_openpsa_calendar_handler_event_admin', 'move'],
    'fixed_args' => ['event', 'move'],
    'variable_args' => 1,
],
// Match /event/<guid>
'event_view' => [
    'handler' => ['org_openpsa_calendar_handler_event_view', 'event'],
    'fixed_args' => 'event',
    'variable_args' => 1,
],

// Match /resource/new
'new_resource' => [
    'handler' => ['org_openpsa_calendar_handler_resource', 'create'],
    'fixed_args' => ['resource', 'new'],
],

// Match /json/
'calendar_view_json' => [
    'handler' => ['org_openpsa_calendar_handler_json', 'json'],
    'fixed_args' => 'json',
],
// Match /<mode>/<date>
'calendar_view_mode_date' => [
    'handler' => ['org_openpsa_calendar_handler_calendar', 'calendar'],
    'variable_args' => 2,
],

// Match /config/
'config' => [
    'handler' => ['midcom_baseclasses_components_handler_configuration', 'config'],
    'fixed_args' => ['config'],
],
