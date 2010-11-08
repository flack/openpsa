'default' => array
(
    'description' => 'parameter',
    'l10n_db'     => 'midgard.admin.asgard',
    'fields'      => array
    (
        'domain' => array
        (
            'title' => 'domain',
            'storage' => 'domain',
            'type' => 'text',
            'widget' => 'text',
            'required' => true,
        ),
        'name' => array
        (
            'title' => 'name',
            'storage' => 'name',
            'type' => 'text',
            'widget' => 'text',
            'required' => true,
        ),
        'value' => array
        (
            'title' => 'value',
            'storage' => 'value',
            'type' => 'text',
            'widget' => 'textarea',
            'required' => true,
        ),
    )
),