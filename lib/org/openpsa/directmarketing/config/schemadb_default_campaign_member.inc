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
                    ORG_OPENPSA_OBTYPE_CAMPAIGN_TESTER => 'tester',
                    ORG_OPENPSA_OBTYPE_CAMPAIGN_MEMBER => 'normal',
                    ORG_OPENPSA_OBTYPE_CAMPAIGN_MEMBER_UNSUBSCRIBED => 'unsubscribed',
                    ORG_OPENPSA_OBTYPE_CAMPAIGN_MEMBER_BOUNCED => 'bounced',
                ),
            ),
            'widget' => 'select',
            'hidden'   => true,
        )
    )
),