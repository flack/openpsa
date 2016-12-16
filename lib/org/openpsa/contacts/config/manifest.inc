'name' => 'org.openpsa.contacts',
'purecode' => false,
'privileges' => array(),
'class_mapping' => array
(
    'org_openpsa_organization' => 'org_openpsa_contacts_group_dba',
    'org_openpsa_person' => 'org_openpsa_contacts_person_dba',
    'org_openpsa_role' => 'org_openpsa_contacts_role_dba',
    'org_openpsa_contacts_list' => 'org_openpsa_contacts_list_dba',
    'org_openpsa_member' => 'org_openpsa_contacts_member_dba',
),
'version' => '9.1.0',
'state' => 'stable',
'description' => 'Application for managing contact persons and organizations, with support for user management and automatic finding of duplicates',
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
