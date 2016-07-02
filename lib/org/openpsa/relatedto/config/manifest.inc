'name' => 'org.openpsa.relatedto',
'icon' => 'stock-icons/16x16/hidden.png',
'purecode' => true,
'version' => '9.1.0-dev',
'state' => 'stable',
'privileges' => array(),
'class_mapping' => array
(
    'org_openpsa_relatedto' => 'org_openpsa_relatedto_dba',
    'org_openpsa_relatedto_journal_entry' => 'org_openpsa_relatedto_journal_entry_dba',
),
'watches' => array
(
    array
    (
        'classes' => array(),
        'operations' => MIDCOM_OPERATION_DBA_DELETE,
    ),
    array
    (
        'classes' => array
        (
            'net_nemein_wiki_wikipage',
            'org_openpsa_documents_document_dba',
            'org_openpsa_projects_task_dba',
            'org_openpsa_calendar_event_dba',
        ),
        'operations' => MIDCOM_OPERATION_DBA_CREATE,
    ),
),
'package.xml' => array
(
    'summary' => 'Library for handling "related to" links',
    'maintainers' => array
    (
        'rambo' => array
        (
            'name' => 'Eero af Heurlin',
            'email' => 'eero.afheurlin@iki.fi',
            'role' => 'lead',
        ),
        'flack' => array
        (
            'name' => 'Andreas Flack',
            'email' => 'flack@contentcontrol-berlin.de',
            'role' => 'developer',
        ),
        'bergie' => array
        (
            'name' => 'Henri Bergius',
            'email' => 'henri.bergius@iki.fi',
            'role' => 'developer',
            'active' => 'no',
        ),
    ),
    'dependencies' => array
    (
        'org.openpsa.helpers' => array(),
        'org.openpsa.widgets' => array(),
        'midcom' => array(),
    ),
),
'customdata' => array
(
    'request_handler_plugin' => array
    (
        'class' => 'org_openpsa_relatedto_plugin',
        'name' => 'Related information',
        'config' => '',
    ),
),
