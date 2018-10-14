//the name of page used for jquery ui-tab, the style is passed in templates/OpenPsa2
'ui_page' => 'uitab',

/* search box */
'search_providers' => [
    'midcom.helper.search' => 'result/',
    'org.openpsa.contacts' => [
        'route' => 'search/',
        'autocomplete' => true
    ],
    'org.openpsa.documents' => 'search/',
    'org.openpsa.invoices' => 'goto/',
],

/* contact widget */
'jabber_enable_presence' => true,
'jabber_edgar_url'       => '',
'gravatar_enable'        => false,
'skype_enable_presence'  => true,
