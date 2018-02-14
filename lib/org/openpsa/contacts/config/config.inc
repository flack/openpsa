'schemadb_group' => 'file:/org/openpsa/contacts/config/schemadb_default_organization.inc',
'schemadb_person' => 'file:/org/openpsa/contacts/config/schemadb_default_person.inc',

'schemadb_config' => 'file:/org/openpsa/contacts/config/schemadb_config.inc',

'person_search_fields' => 'username,firstname,lastname,email',
'organization_search_fields' => 'official,email,name,customerId',
'enable_duplicate_search' => false, /* NOTE: These are *very* heavy cron jobs run daily */
'auto_wildcards' => 'end',
'owner_organization' => null, // Set to GUID of org_openpsa_organization

'group_merge_configuration' => array(),

'person_merge_configuration' => array
(
    'org_openpsa_calendar_event_member_dba' => array
    (
        'uid' => array
        (
            'target' => 'id',
            'duplicate_check' => 'eid'
        )
    ),
    'org_openpsa_calendar_event_dba' => array(),
    'midcom_db_member' => array
    (
        'uid' => array
        (
            'target' => 'id',
            'duplicate_check' => 'gid'
        )
    ),
    'org_openpsa_contacts_person_dba' => array(),
    'org_openpsa_contacts_group_dba' => array(),
    'org_openpsa_contacts_role_dba' => array
    (
        'person' => array
        (
            'target' => 'id',
            'duplicate_check' => 'check_duplicates'
        )
    ),

    'org_openpsa_directmarketing_campaign_member_dba' => array
    (
        'person' => array
        (
            'target' => 'id',
            'duplicate_check' => 'check_duplicate_membership'
        )
    ),
    'org_openpsa_directmarketing_campaign_messagereceipt_dba' => array
    (
        'person' => array
        (
            'target' => 'id',
        )
    ),
    'org_openpsa_directmarketing_link_log_dba' => array
    (
        'person' => array
        (
            'target' => 'id',
        )
    ),
    'org_openpsa_directmarketing_campaign_dba' => array(),
    'org_openpsa_directmarketing_campaign_message_dba' => array(),
    'org_openpsa_invoices_invoice_dba' => array
    (
        'customerContact' => array
        (
            'target' => 'id'
        )
    ),
    'org_openpsa_invoices_billing_data_dba' => array
    (
        'linkGuid' => array
        (
            'target' => 'guid',
            'duplicate_check' => 'linkGuid'
        )
    ),
    'org_openpsa_projects_task_resource_dba' => array
    (
        'person' => array
        (
            'target' => 'id',
            'duplicate_check' => 'task'
        )
    ),
    'org_openpsa_projects_task_status_dba' => array
    (
        'targetPerson' => array
        (
            'target' => 'id',
        )
    ),
    'org_openpsa_expenses_hour_report_dba' => array
    (
        'person' => array
        (
            'target' => 'id',
        )
    ),
    'org_openpsa_projects_task_dba' => array
    (
        'manager' => array
        (
            'target' => 'id',
        )
    ),
    'org_openpsa_relatedto_dba' => array
    (
        'fromGuid' => array
        (
            'target' => 'guid',
            'duplicate_check' => 'toGuid'
        ),
        'toGuid' => array
        (
            'target' => 'guid',
            'duplicate_check' => 'fromGuid'
        )
    ),
    'org_openpsa_sales_salesproject_dba' => array
    (
        'customerContact' => array
        (
            'target' => 'id'
        ),
        'owner' => array
        (
            'target' => 'id'
        )
    ),
),