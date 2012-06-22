'config' => array (
    'name'        => 'config',
    'description' => 'Default Configuration Schema',
    'fields'      => array (
            'imp_global_uri' => array ( // This option must be defined (though it can be hidden) for the component to work
                'description'   => 'Global (fully qualified) URL for Horde/Imp login',
                'datatype'      => 'text',
                'location'      => 'config',
                'config_domain' => 'org.openpsa.imp',
                'config_key'    => 'imp_global_uri',
            ),
    ),
),
