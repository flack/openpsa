'name' => 'org.openpsa.contacts',
'purecode' => false,
'privileges' => array(),
'class_mapping' => array
(
    'org_openpsa_organization' => 'org_openpsa_contacts_group_dba',
    'org_openpsa_person' => 'org_openpsa_contacts_person_dba',
    'net_nehmer_buddylist_entry_db' => 'org_openpsa_contacts_buddy_dba',
),
'version' => '8.09.9',
'state' => 'stable',
'package.xml' => array
(
    'summary' => 'Contact and user manager',
    'description' => 'Application for managing contact persons and organizations, with support for user management and automatic finding of duplicates',
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
    ),
    'dependencies' => array
    (
        'midcom.helper.datamanager2' => array(),
        'org.openpsa.core' => array(),
        'org.openpsa.helpers' => array(),
        'org.openpsa.contactwidget' => array(),
        'org.openpsa.qbpager' => array(),
        'org.openpsa.relatedto' => array(),
        'org.openpsa.notifications' => array(),
        'net.nehmer.buddylist' => array(),
        'org.routamc.positioning' => array(),
        'midcom' => array(),
        'midcom.services.at' => array(),
    ),
),
'customdata' => array
(
    'midcom.services.cron' => array
    (
        array
        (
            'handler' => 'org_openpsa_contacts_cron_duplicates_mark',
            'recurrence' => MIDCOM_CRON_DAY,
        ),
        array
        (
            'handler' => 'org_openpsa_contacts_cron_duplicates_clean',
            'recurrence' => MIDCOM_CRON_DAY,
        ),
    ),
),
