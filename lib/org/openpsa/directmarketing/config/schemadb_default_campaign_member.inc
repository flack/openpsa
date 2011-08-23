'default' => array
(
    'description'   => 'campaign member',
    'fields'  => array
    (
        'campaign' => array
        (
            'title'    => 'campaign',
            'storage'  => 'campaign',
            'type'     => 'text',
            'widget'   => 'text',
            'hidden'   => true,
        ),
        'member_person' => array
        (
            'title'    => 'person',
            'storage'  => 'person',
            'type'     => 'text',
            'widget'   => 'text',
            'hidden'   => true,
        ),
        'member_type' => array
        (
            'title'    => 'membership status',
            'storage'  => 'orgOpenpsaObtype',
            'type'     => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    org_openpsa_directmarketing_campaign_member_dba::TESTER => 'tester',
                    org_openpsa_directmarketing_campaign_member_dba::NORMAL => 'normal',
                    org_openpsa_directmarketing_campaign_member_dba::UNSUBSCRIBED => 'unsubscribed',
                    org_openpsa_directmarketing_campaign_member_dba::BOUNCED => 'bounced',
                ),
            ),
            'widget' => 'select',
            'hidden'   => true,
        )
    )
),