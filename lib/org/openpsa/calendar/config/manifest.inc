'name' => 'org.openpsa.calendar',
'icon' => 'calendar',
'purecode' => false,
'privileges' => [
    'read' => MIDCOM_PRIVILEGE_ALLOW,
    'reserve' => MIDCOM_PRIVILEGE_ALLOW,
],
'class_mapping' => [
    'org_openpsa_event' => 'org_openpsa_calendar_event_dba',
    'org_openpsa_eventmember' => 'org_openpsa_calendar_event_member_dba',
    'org_openpsa_calendar_resource' => 'org_openpsa_calendar_resource_dba',
    'org_openpsa_calendar_event_resource' => 'org_openpsa_calendar_event_resource_dba',
],
'description' => 'Group calendar for workgroups.',
'customdata' => [
    'midcom.services.cron' => [
        [
            'handler' => 'org_openpsa_calendar_cron_reporthours',
            'recurrence' => MIDCOM_CRON_HOUR,
        ],
    ],
    'org.openpsa.notifications' => [
        'event_update' => [
            'default' => 'email',
        ],
        'event_add' => [
            'default' => 'email',
        ],
        'event_remove' => [
            'default' => 'email',
        ],
        'event_cancel' => [
            'default' => 'email',
        ],
    ],
],
