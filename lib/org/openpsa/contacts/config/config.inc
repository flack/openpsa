'schemadb_group' => 'file:/org/openpsa/contacts/config/schemadb_default_organization.inc',
'schemadb_person' => 'file:/org/openpsa/contacts/config/schemadb_default_person.inc',

'schemadb_config' => 'file:/org/openpsa/contacts/config/schemadb_config.inc',

'person_search_fields' => 'username,firstname,lastname,email',
'organization_search_fields' => 'official,email,name',
'enable_duplicate_search' => false, /* NOTE: These are *very* heavy cron jobs run daily */
'auto_wildcards' => 'end',
'owner_organization' => null, // Set to GUID of org_openpsa_organization

'group_merge_configuration' => [],

'person_merge_configuration' => [
    'org_openpsa_calendar_event_member_dba' => [
        'uid' => [
            'target' => 'id',
            'duplicate_check' => 'eid'
        ]
    ],
    'org_openpsa_calendar_event_dba' => [],
    'midcom_db_member' => [
        'uid' => [
            'target' => 'id',
            'duplicate_check' => 'gid'
        ]
    ],
    'org_openpsa_contacts_person_dba' => [],
    'org_openpsa_contacts_group_dba' => [],

    'org_openpsa_directmarketing_campaign_member_dba' => [
        'person' => [
            'target' => 'id',
            'duplicate_check' => 'check_duplicate_membership'
        ]
    ],
    'org_openpsa_directmarketing_campaign_messagereceipt_dba' => [
        'person' => [
            'target' => 'id',
        ]
    ],
    'org_openpsa_directmarketing_link_log_dba' => [
        'person' => [
            'target' => 'id',
        ]
    ],
    'org_openpsa_directmarketing_campaign_dba' => [],
    'org_openpsa_directmarketing_campaign_message_dba' => [],
    'org_openpsa_invoices_invoice_dba' => [
        'customerContact' => [
            'target' => 'id'
        ]
    ],
    'org_openpsa_invoices_billing_data_dba' => [
        'linkGuid' => [
            'target' => 'guid',
            'duplicate_check' => 'linkGuid'
        ]
    ],
    'org_openpsa_projects_role_dba' => [
        'person' => [
            'target' => 'id',
            'duplicate_check' => 'check_duplicates'
        ]
    ],
    'org_openpsa_projects_task_resource_dba' => [
        'person' => [
            'target' => 'id',
            'duplicate_check' => 'task'
        ]
    ],
    'org_openpsa_projects_task_status_dba' => [
        'targetPerson' => [
            'target' => 'id',
        ]
    ],
    'org_openpsa_expenses_hour_report_dba' => [
        'person' => [
            'target' => 'id',
        ]
    ],
    'org_openpsa_projects_task_dba' => [
        'manager' => [
            'target' => 'id',
        ]
    ],
    'org_openpsa_relatedto_dba' => [
        'fromGuid' => [
            'target' => 'guid',
            'duplicate_check' => 'toGuid'
        ],
        'toGuid' => [
            'target' => 'guid',
            'duplicate_check' => 'fromGuid'
        ]
    ],
    'org_openpsa_sales_salesproject_dba' => [
        'customerContact' => [
            'target' => 'id'
        ],
        'owner' => [
            'target' => 'id'
        ]
    ],
],