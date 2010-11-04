'default' => array
(
    'description' => 'interview',
    'fields'      => array
    (
        'content' => Array
        (
            // COMPONENT-REQUIRED
            'title' => 'interview',
            'storage' => 'parameter',
            'type' => 'text',
            'type_config' => Array
            (
                'output_mode' => 'markdown'
            ),
            'widget' => 'textarea',
        ),
        'orgOpenpsaObtype' => Array
        (
            // COMPONENT-REQUIRED
            'title' => 'status',
            'storage' => 'orgOpenpsaObtype',
            'type' => 'select',
            'required' => true,
            'type_config' => Array
            (
                'options' => Array
                (
                    ORG_OPENPSA_OBTYPE_CAMPAIGN_MEMBER_INTERVIEWED => 'interviewed',
                    ORG_OPENPSA_OBTYPE_CAMPAIGN_MEMBER => 'waiting',
                    ORG_OPENPSA_OBTYPE_CAMPAIGN_MEMBER_UNSUBSCRIBED => 'unsubscribed',
                ),
            ),
            'widget' => 'select',
        ),
        'suspended' => Array
        (
            // COMPONENT-REQUIRED
            'title' => 'suspended until',
            'storage' => 'suspended',
            'type' => 'date',
            'type_config' => Array ( 'storage_type' => 'UNIXTIME' ),
            'widget' => 'jsdate',
        ),
    )
),