'name' => 'org.openpsa.sales',
'icon' => 'stock-icons/16x16/currency.png',
'purecode' => false,
'class_mapping' => array
(
    'org_openpsa_salesproject' => 'org_openpsa_sales_salesproject_dba',
    'org_openpsa_salesproject_deliverable' => 'org_openpsa_sales_salesproject_deliverable_dba',
),
'privileges' => array(),
'description' => 'Sales process manager',
'customdata' => array
(
    'org.openpsa.notifications' => array
    (
        'new_subscription_cycle' => array
        (
            'default' => 'email',
        ),
        'new_notification_message' => array
        (
            'default' => 'email',
        )
    ),
),
