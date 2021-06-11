'name' => 'org.openpsa.contacts',
'purecode' => false,
'privileges' => [],
'class_mapping' => [
    'org_openpsa_organization' => 'org_openpsa_contacts_group_dba',
    'org_openpsa_person' => 'org_openpsa_contacts_person_dba',
    'org_openpsa_member' => 'org_openpsa_contacts_member_dba',
],
'description' => 'Application for managing contact persons and organizations, with support for user management and automatic finding of duplicates',
'customdata' => [
    'midcom.services.cron' => [
        [
            'handler' => 'org_openpsa_contacts_cron_duplicates_mark',
            'recurrence' => MIDCOM_CRON_DAY,
        ],
    ],
],
