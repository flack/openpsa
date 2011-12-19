'name' => 'midcom.helper.activitystream',
'icon' => 'midcom.helper.activitystream/activitystream-16.png',
'purecode' => true,
'privileges' => array(),
'class_mapping' => array
(
 'midcom_helper_activitystream_activity' => 'midcom_helper_activitystream_activity_dba',
),
'watches' => array
(
    array
    (
        'classes' => array(),
        'operations' => MIDCOM_OPERATION_DBA_CREATE 
                | MIDCOM_OPERATION_DBA_UPDATE 
                | MIDCOM_OPERATION_DBA_DELETE,
    ),
),
'version' => '9.0beta2+git',
'state' => 'stable',
'package.xml' => array
(
    'summary' => 'Activity Log',
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
        'midcom' => array(),
    ),
),
'customdata' => array
(
),
