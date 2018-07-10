'default' => [
    'description'   => 'campaign member',
    'fields'  => [
        'campaign' => [
            'title'    => 'campaign',
            'storage'  => 'campaign',
            'type'     => 'text',
            'widget'   => 'text',
            'hidden'   => true,
        ],
        'member_person' => [
            'title'    => 'person',
            'storage'  => 'person',
            'type'     => 'text',
            'widget'   => 'text',
            'hidden'   => true,
        ],
        'member_type' => [
            'title'    => 'membership status',
            'storage'  => 'orgOpenpsaObtype',
            'type'     => 'select',
            'type_config' => [
                'options' => [
                    org_openpsa_directmarketing_campaign_member_dba::TESTER => 'tester',
                    org_openpsa_directmarketing_campaign_member_dba::NORMAL => 'normal',
                    org_openpsa_directmarketing_campaign_member_dba::UNSUBSCRIBED => 'unsubscribed',
                    org_openpsa_directmarketing_campaign_member_dba::BOUNCED => 'bounced',
                ],
            ],
            'widget' => 'select',
            'hidden'   => true,
        ]
    ]
],