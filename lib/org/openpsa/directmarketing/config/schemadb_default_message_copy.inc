'default' => [
    'description' => 'message copy',
    'operations' => [
        'save' => 'copy',
        'cancel' => 'cancel',
    ],
    'fields' => [
        'campaign' => [
            'title' => 'choose the target campaign',
            'storage' => null,
            'type' => 'select',
            'type_config' => [
                'options' => [],
                'sortable' => false,
                'allow_multiple' => true,
                'require_corresponding_option' => false,
            ],
            'widget' => 'autocomplete',
            'widget_config' => [
                'component' => 'org.openpsa.directmarketing',
                'class' => 'org_openpsa_directmarketing_campaign_dba',
                'titlefield' => 'title',
                'result_headers' => [
                    [
                        'name' => 'description',
                        'title' => 'Description',
                    ],
                ],
                'searchfields' => [
                    'title' => 'title',
                ],
            ],
            'required' => true,
        ],
    ],
],