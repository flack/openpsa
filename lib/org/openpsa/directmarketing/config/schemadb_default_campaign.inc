'default' => [
    'description' => 'campaign',
    'fields'      => [
        // Metadata
        'title' => [
            // COMPONENT-REQUIRED
            'title'    => 'title',
            'type'     => 'text',
            'widget'   => 'text',
            'storage'  => 'title',
            'required' => true,
        ],
        'type' => [
            'title'       => 'type',
            'storage'     => 'orgOpenpsaObtype',
            'type'        => 'select',
            'type_config' => [
                'options' => [
                    org_openpsa_directmarketing_campaign_dba::TYPE_NORMAL => 'normal campaign',
                    org_openpsa_directmarketing_campaign_dba::TYPE_SMART => 'smart campaign',
                ],
            ],
            'default'     => org_openpsa_directmarketing_campaign_dba::TYPE_NORMAL,
            'widget'      => 'radiocheckselect',
        ],
        'description' => [
            'title' => 'description',
            'storage' => 'description',
            'type' => 'text',
            'type_config' => [
                'output_mode' => 'markdown'
            ],
            'widget' => 'markdown',
        ],
        'testers' => [
            'title'       => 'testers',
            'storage'     => null,
            'type'        => 'mnrelation',
            'type_config' => [
                'mapping_class_name' => 'org_openpsa_directmarketing_campaign_member_dba',
                'master_fieldname' => 'campaign',
                'master_is_id' => true,
                'member_fieldname' => 'person',
                'options' => [],
                'additional_fields' => [
                    'orgOpenpsaObtype' => org_openpsa_directmarketing_campaign_member_dba::TESTER,
                ],
                'constraints' => [
                    [
                        'field' => 'email',
                        'op' => '<>',
                        'value' => '',
                    ],
                ],
            ],
            'widget' => 'autocomplete',
            'widget_config' => [
                'clever_class' => 'contact',
                'id_field' => 'id',
                'result_headers' => [
                    [
                        'name' => 'email',
                    ], [
                        'name' => 'handphone',
                    ], [
                        'name' => 'username',
                    ],
                ],
                'searchfields' => [
                    'firstname',
                    'lastname',
                    'email',
                    'username',
                    'handphone',
                ],
                'auto_wildcards' => 'both',
            ],
        ],
    ]
],