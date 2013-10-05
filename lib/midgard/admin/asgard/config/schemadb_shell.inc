'default' => array
(
    'description' => 'shell schema',
    'l10n_db'     => 'midgard.admin.asgard',
    'operations' => array
    (
        'save' => 'run'
    ),
    'fields'      => array
    (
        'code' => array
        (
            'title'       => 'code',
            'storage'     => null,
            'type'        => 'php',
            'type_config' => Array
            (
                'output_mode' => 'code',
            ),
            'widget'      => 'codemirror',
            'widget_config' => Array
            (
                'height' => 30,
                'width' => '100%',
            ),
            'required' => true
        )
    )
),