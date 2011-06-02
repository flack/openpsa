'default_workgroup_filter' => 'all',
'owner_organization' => null, // Set to GUID of org_openpsa_organization

//the name of page used for jquery ui-tab - if the page doesn't exist
// it will be created in siteconfig - the style is passed in templates/OpenPsa2
'ui_page' => 'uitab',

'jqgrid_version' => '4.0.0',

'search_providers' => array
(
    'midcom.helper.search' => 'result/',
    'org.openpsa.contacts' => 'search/',
    'org.openpsa.documents' => 'search/',
    'org.openpsa.invoices' => 'goto/',
),

//Components for which siteconfig should cache URL information
'siteconfig_components' => array
(
    'org.openpsa.calendar' => 'org.openpsa.calendar',
    'org.openpsa.contacts' => 'org.openpsa.contacts',
    'org.openpsa.documents' => 'org.openpsa.documents',
    'org.openpsa.expenses' => 'org.openpsa.expenses',
    'org.openpsa.invoices' => 'org.openpsa.invoices',
    'org.openpsa.projects' => 'org.openpsa.projects',
    'org.openpsa.products' => 'org.openpsa.products',
    'org.openpsa.reports' => 'org.openpsa.reports',
    'org.openpsa.sales' => 'org.openpsa.sales',
    'org.openpsa.user' => 'org.openpsa.user',
    'net.nemein.wiki' => 'net.nemein.wiki',
    'midcom.helper.search' => 'midcom.helper.search',
),