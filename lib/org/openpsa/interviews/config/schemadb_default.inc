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
                    org_openpsa_directmarketing_campaign_member_dba::INTERVIEWED => 'interviewed',
                    org_openpsa_directmarketing_campaign_member_dba::NORMAL => 'waiting',
                    org_openpsa_directmarketing_campaign_member_dba::UNSUBSCRIBED => 'unsubscribed',
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