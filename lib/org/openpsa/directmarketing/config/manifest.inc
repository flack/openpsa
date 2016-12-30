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
'description' => 'Application for sending emails and newsletters to registered users.',
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
