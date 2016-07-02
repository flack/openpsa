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
'version' => '9.1.0-dev',
'state' => 'stable',
'package.xml' => array
(
    'summary' => 'Group calendar',
    'description' => 'Group calendar for workgroups.',
    'maintainers' => array
    (
        'rambo' => array
        (
            'name' => 'Eero af Heurlin',
            'email' => 'eero.afheurlin@iki.fi',
            'role' => 'lead',
        ),
        'bergie' => array
        (
            'name' => 'Henri Bergius',
            'email' => 'henri.bergius@iki.fi',
            'role' => 'lead',
            'active' => 'no',
        ),
        'adrenalin' => array
        (
            'name' => 'Arttu Manninen',
            'email' => 'arttu@kaktus.cc',
            'role' => 'developer',
        ),
    ),
    'dependencies' => array
    (
        'midcom.helper.datamanager2' => array(),
        'org.openpsa.core' => array(),
        'org.openpsa.mail' => array(),
        'org.openpsa.helpers' => array(),
        'org.openpsa.widgets' => array(),
        'org.openpsa.contacts' => array(),
        'org.openpsa.notifications' => array(),
        'midcom' => array(),
    ),
),
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

