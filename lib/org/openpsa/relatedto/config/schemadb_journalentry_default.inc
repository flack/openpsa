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
        ),
        'text' => Array
        (
            'title' => 'text',
            'storage' => 'text',
            'type' => 'text',
            'widget'  => 'textarea',
        ),
        'followUp' => Array
        (
            'title' => $_MIDCOM->i18n->get_string('followUp' , 'org.openpsa.relatedto'),
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