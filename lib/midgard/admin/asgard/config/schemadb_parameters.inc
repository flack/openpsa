'parameters' => array
(
    'description' => 'object parameters schema',
    'l10n_db'     => 'midgard.admin.asgard',
    'fields'      => array
    (
        'guid' => Array
        (
            // COMPONENT-REQUIRED
            'title' => 'guid',
            'storage' => 'guid',
            'type' => 'text',
            'widget' => 'text',
            'readonly' => 'true',
        ),
        'parameters' => Array
        (
            'title' => 'parameters',
            'storage' => null,
            'type' => 'composite',
            'type_config' => Array
            (
                'child_class'                 => 'midcom_baseclasses_database_parameter',
                'child_schemadb'              => 'file:/midgard/admin/asgard/config/schemadb_parameter.inc',
                'child_foreign_key_fieldname' => 'parentguid',
                'orders'                      => array
                (
                    'domain' => 'ASC',
                    'name' => 'ASC',
                ),
                'parent_key_fieldname'        => 'guid',
                'style_element_name'          => 'parameter',
                'area_element'                => 'tr',
            ),
            'widget' => 'composite',
        ),
    )
)