'name' => 'org.openpsa.sales',
'icon' => 'stock-icons/16x16/currency.png',
'purecode' => false,
'version' => '8.09.9',
'state' => 'stable',
'class_definitions' => array
(
    'org_openpsa_salesproject' => 'org_openpsa_sales_salesproject_dba',
    'org_openpsa_salesproject_member' => 'org_openpsa_sales_salesproject_member_dba',
    'org_openpsa_salesproject_deliverable' => 'org_openpsa_sales_salesproject_deliverable_dba',
),
'privileges' => array(),
'package.xml' => array
(
    'summary' => 'Sales',
    'description' => 'Sales process manager',
    'maintainers' => array
    (
        'bergie' => array
        (
            'name' => 'Henri Bergius',
            'email' => 'henri.bergius@iki.fi',
            'role' => 'lead',
            'active' => 'no',
        ),
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
    ),
    'dependencies' => array
    (
        'midcom.helper.datamanager2' => array(),
        'midcom.services.at' => array(),
        'org.openpsa.core' => array(),
        'org.openpsa.contacts' => array(),
        'org.openpsa.contactwidget' => array(),
        'net.nemein.tag' => array(),
        'org.openpsa.calendar' => array(),
        'org.openpsa.helpers' => array(),
        'org.openpsa.projects' => array(),
        'org.openpsa.products' => array(),
        'org.openpsa.invoices' => array(),
        'Calendar' => array
        (
            'channel' => 'pear.php.net',
        ),
        'midcom' => array(),
    ),
),
'customdata' => array
(
    'org.openpsa.notifications' => array
    (
        'new_subscription_cycle' => array
        (
            'default' => 'email',
        ),
    ),
),
