'name' => 'org.openpsa.sales',
'icon' => 'money',
'purecode' => false,
'class_mapping' => array
(
    'org_openpsa_salesproject' => 'org_openpsa_sales_salesproject_dba',
    'org_openpsa_salesproject_deliverable' => 'org_openpsa_sales_salesproject_deliverable_dba',
    'org_openpsa_offer' => 'org_openpsa_sales_salesproject_offer_dba',
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
