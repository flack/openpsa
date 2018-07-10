'default' => [
    'description' => 'reminder',
    'l10n_db' => 'org.openpsa.relatedto',
    'fields'      => [
        'title' => [
            'title' => 'title',
            'storage' => 'title',
            'type' => 'text',
            'widget'  => 'text',
            'required' => true
        ],
        'text' => [
            'title' => 'text',
            'storage' => 'text',
            'type' => 'text',
            'widget'  => 'textarea',
        ],
        'followup' => [
            'title' => 'followup',
            'storage' => 'followUp',
            'type' => 'date',
            'type_config' => [
                'storage_type' => 'UNIXTIME',
            ],
            'widget' => 'jsdate',
        ],
        'closed' => [
            'title' => 'finished',
            'storage' => 'closed',
            'type'        => 'boolean',
            'widget'      => 'checkbox',
        ],
    ]
],