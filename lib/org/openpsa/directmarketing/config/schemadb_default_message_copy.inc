'default' => array
(
    'description' => 'message copy',
    'operations' => array
    (
        'save' => 'copy',
        'cancel' => 'cancel',
    ),
    'fields' => array
    (
        'campaign' => array
        (
            'title' => 'campaign',
            'storage' => null,
            'type' => 'select',
            'type_config' => array
            (
                'options' => array(),
                'sortable' => false,
                'allow_multiple' => true,
                'require_corresponding_option' => false,
            ),
            'widget' => 'autocomplete',
            'widget_config' => array
            (
                'component' => 'org.openpsa.directmarketing',
                'class' => 'org_openpsa_directmarketing_campaign_dba',
                'titlefield' => 'title',
                'result_headers' => array
                (
                    array
                    (
                        'name' => 'title',
                        'title' => 'Title',
                    ),
                    array
                    (
                        'name' => 'description',
                        'title' => 'Description',
                    ),
                ),
                'searchfields' => array
                (
                    'title' => 'title',
                ),
            ),
            'required' => true,
        ),
    ),
),