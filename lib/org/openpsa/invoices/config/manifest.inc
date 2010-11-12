'name' => 'org.openpsa.invoices',
'icon' => 'stock-icons/16x16/stock_mail-open.png',
'purecode' => false,
'version' => '8.09.9',
'state' => 'stable',
'privileges' => array(),
'class_definitions' => array
(
    'midcom_dba_classes.inc',
),
'watches' => array
(
    array
    (
        'classes' => array(
            'org_openpsa_contacts_person_dba',
            'org_openpsa_contacts_group_dba',
            ),
        'operations' => MIDCOM_OPERATION_DBA_DELETE,
    ),
),
'package.xml' => array
(
    'summary' => 'Invoice management',
    'description' => 'Simple invoicing status tracker',
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
        'midcom.helper.datamanager2' => array(),
        'org.openpsa.contactwidget' => array(),
        'org.openpsa.contacts' => array(),
        'org.openpsa.projects' => array(),
        'org.openpsa.relatedto' => array(),
        'org.openpsa.helpers' => array(),
        'midcom' => array(),
    ),
),
