'name' => 'org.openpsa.directmarketing',
'icon' => 'stock-icons/16x16/stock_mail.png',
'purecode' => false,
'class_mapping' => array
(
    'org_openpsa_campaign' => 'org_openpsa_directmarketing_campaign_dba',
    'org_openpsa_campaign_member' => 'org_openpsa_directmarketing_campaign_member_dba',
    'org_openpsa_campaign_message' => 'org_openpsa_directmarketing_campaign_message_dba',
    'org_openpsa_campaign_message_receipt' => 'org_openpsa_directmarketing_campaign_messagereceipt_dba',
    'org_openpsa_link_log' => 'org_openpsa_directmarketing_link_log_dba',
),
'version' => '9.0.0+git',
'state' => 'stable',
'package.xml' => array
(
    'summary' => 'Direct marketing and mailing lists',
    'description' => 'Application for sending emails and newsletters to registered users.',
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
        'netblade' => array
        (
            'name' => 'Oskari Kokko',
            'email' => 'oskari.kokko@iki.fi',
            'role' => 'lead',
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
        'org.openpsa.core' => array(),
        'org.openpsa.contacts' => array(),
        'org.openpsa.mail' => array(),
        'org.openpsa.helpers' => array(),
        'midcom.helper.datamanager2' => array(),
        'org.openpsa.widgets' => array(),
        'org.openpsa.qbpager' => array(),
        'midcom.services.at' => array(),
        'midcom' => array(),
    ),
),
'customdata' => array
(
    'midcom.services.cron' => array
    (
        array
        (
            'handler' => 'org_openpsa_directmarketing_cron_updatemembers',
            'recurrence' => MIDCOM_CRON_DAY,
        ),
        array
        (
            'handler' => 'org_openpsa_directmarketing_cron_cleanup',
            'recurrence' => MIDCOM_CRON_DAY,
        ),
        array
        (
            'handler' => 'org_openpsa_directmarketing_cron_cleartokens',
            'recurrence' => MIDCOM_CRON_DAY,
        ),
    ),
),
'watches' => array
(
    array
    (
        'classes' => array
        (
            'midcom_db_person',
            'org_openpsa_contacts_person_dba',
            'org_openpsa_directmarketing_campaign_dba'
        ),
        'operations' => MIDCOM_OPERATION_DBA_DELETE,
    ),
),
