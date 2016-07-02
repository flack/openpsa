'name' => 'org.openpsa.documents',
'icon' => 'stock-icons/16x16/folder.png',
'purecode' => false,
'privileges' => array(),
'class_mapping' => array
(
    'org_openpsa_document' => 'org_openpsa_documents_document_dba',
),
'version' => '9.1.0-dev',
'package.xml' => array
(
    'summary' => 'Document management',
    'description' => 'Organize and manage documents with metadata, search inside documents.',
    'maintainers' => array
    (
        'flack' => array
        (
            'name' => 'Andreas Flack',
            'email' => 'flack@contentcontrol-berlin.de',
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
        'org.openpsa.core' => array(),
        'org.openpsa.contacts' => array(),
        'org.openpsa.helpers' => array(),
        'midcom.helper.datamanager2' => array(),
        'midcom' => array(),
    ),
),
'state' => 'stable',
