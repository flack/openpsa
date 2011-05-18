'name' => 'org.openpsa.notifications',
'icon' => 'org.openpsa.notifications/notification-system.png',
'purecode' => true,
'privileges' => array(),
'version' => '9.0pre',
'state' => 'stable',
'class_mapping' => array
(
    'org_openpsa_notifications_notification' => 'org_openpsa_notifications_notification_dba',
),
'package.xml' => array
(
    'summary' => 'Notification manager',
    'description' => 'System for sending notifications to users based on their preferences',
    'maintainers' => array
    (
        'bergie' => array
        (
            'name' => 'Henri Bergius',
            'email' => 'henri.bergius@iki.fi',
            'role' => 'lead',
        ),
    ),
    'dependencies' => array
    (
        'org.openpsa.mail' => array(),
        'midcom' => array(),
    ),
),
'customdata' => array
(
    'request_handler_plugin' => array
    (
        'class' => 'org_openpsa_notifications_preferences',
        'src' => 'file:/org/openpsa/notifications/preferences.php',
        'name' => 'Notification settings',
        'config' => '',
    ),
),