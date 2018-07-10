'name' => 'org.openpsa.sales',
'icon' => 'money',
'purecode' => false,
'class_mapping' => [
    'org_openpsa_salesproject' => 'org_openpsa_sales_salesproject_dba',
    'org_openpsa_salesproject_deliverable' => 'org_openpsa_sales_salesproject_deliverable_dba',
    'org_openpsa_offer' => 'org_openpsa_sales_salesproject_offer_dba',
],
'privileges' => [],
'description' => 'Sales process manager',
'customdata' => [
    'org.openpsa.notifications' => [
        'new_subscription_cycle' => [
            'default' => 'email',
        ],
        'new_notification_message' => [
            'default' => 'email',
        ]
    ],
],
