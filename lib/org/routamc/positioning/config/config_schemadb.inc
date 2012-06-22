'config' => array
(
    'description' => 'component configuration',
    'l10n_db'     => 'org.routamc.positioning',
    'fields'      => array
    (
        'map_provider' => Array
        (
            'title' => 'map_provider',
            'storage' => null,
            'type' => 'select',
            'widget' => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    'openlayers' => 'OpenStreetMap via OpenLayers',
                    'google' => 'Google Maps',
                    'openstreetmap' => 'OpenStreetMap via Google Maps',
                    'microsoft' => 'Microsoft Virtual Earth',
                ),
            ),
        ),
    )
),