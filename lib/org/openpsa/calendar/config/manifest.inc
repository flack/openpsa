'name' => 'org.openpsa.calendar',
'purecode' => false,
'privileges' => array
(
    'read' => MIDCOM_PRIVILEGE_ALLOW,
    'reserve' => MIDCOM_PRIVILEGE_ALLOW,
),
'class_mapping' => array
(
    'org_openpsa_event' => 'org_openpsa_calendar_event_dba',
    'org_openpsa_eventmember' => 'org_openpsa_calendar_event_member_dba',
    'org_openpsa_calendar_resource' => 'org_openpsa_calendar_resource_dba',
    'org_openpsa_calendar_event_resource' => 'org_openpsa_calendar_event_resource_dba',
),
'version' => '9.1.0',
'state' => 'stable',
'description' => 'Group calendar for workgroups.',
'customdata' => array
(
    'midcom.services.cron' => array
    (
        array
        (
            'handler' => 'org_openpsa_calendar_cron_reporthours',
            'recurrence' => MIDCOM_CRON_HOUR,
        ),
    ),
    'org.openpsa.notifications' => array
    (
        'event_update' => array
        (
            'default' => 'email',
        ),
        'event_add' => array
        (
            'default' => 'email',
        ),
        'event_remove' => array
        (
            'default' => 'email',
        ),
        'event_cancel' => array
        (
            'default' => 'email',
        ),
    ),
),

