'default' => array
(
    'description' => 'reminder',
    'l10n_db' => 'org.openpsa.relatedto',
    'fields'      => array
    (
        'title' => Array
        (
            'title' => 'title',
            'storage' => 'title',
            'type' => 'text',
            'widget'  => 'text',
            'required' => true
        ),
        'text' => Array
        (
            'title' => 'text',
            'storage' => 'text',
            'type' => 'text',
            'widget'  => 'textarea',
        ),
        'followup' => Array
        (
            'title' => 'followup',
            'storage' => 'followUp',
            'type' => 'date',
            'type_config' => array
            (
                'storage_type' => 'UNIXTIME',
            ),
            'widget' => 'jsdate',
        ),
        'closed' => Array
        (
            'title' => 'finished',
            'storage' => 'closed',
            'type'        => 'boolean',
            'widget'      => 'checkbox',
        ),
    )
),