'name' => 'org.openpsa.relatedto',
'icon' => 'external-link',
'purecode' => true,
'privileges' => [],
'class_mapping' => [
    'org_openpsa_relatedto' => 'org_openpsa_relatedto_dba',
    'org_openpsa_relatedto_journal_entry' => 'org_openpsa_relatedto_journal_entry_dba',
],
'watches' => [
	[
        'classes' => [],
        'operations' => MIDCOM_OPERATION_DBA_DELETE,
    ], [
        'classes' => [
            'net_nemein_wiki_wikipage',
            'org_openpsa_documents_document_dba',
            'org_openpsa_projects_task_dba',
            'org_openpsa_calendar_event_dba',
        ],
        'operations' => MIDCOM_OPERATION_DBA_CREATE,
    ],
],
'customdata' => [
    'request_handler_plugin' => [
        'class' => 'org_openpsa_relatedto_plugin',
        'name' => 'Related information',
        'config' => '',
    ],
],
