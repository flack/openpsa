// This is for a midcom_db_person object
'default' => array
(
    'description' => 'person',
    'l10n_db' => 'midgard.admin.user',
    'fields' => array
    (
        'firstname' => array
        (
            'title' => 'firstname',
            'type' => 'text',
            'widget' => 'text',
            'storage' => 'firstname',
            'index_method' => 'noindex',
        ),
        'lastname' => array
        (
            'title' => 'lastname',
            'type' => 'text',
            'widget' => 'text',
            'storage' => 'lastname',
            'index_method' => 'noindex',
        ),
        'email' => array
        (
            'title' => 'email',
            'type' => 'text',
            'widget' => 'text',
            'storage' => 'email',
            'validation' => 'email',
        ),
        'workphone' => array
        (
            'title' => 'workphone',
            'type' => 'text',
            'widget' => 'text',
            'storage' => 'workphone'
        ),
        'street' => array
        (
            'title' => 'street',
            'type' => 'text',
            'widget' => 'text',
            'storage' => 'street',
        ),
        'postcode' => array
        (
            'title' => 'postcode',
            'type' => 'text',
            'widget' => 'text',
            'storage' => 'postcode',
        ),
        'city' => array
        (
            'title' => 'city',
            'type' => 'text',
            'widget' => 'text',
            'storage' => 'city',
        ),
        'groups' => array
        (
            'title' => 'groups',
            'storage' => null,
            'type' => 'mnrelation',
            'type_config' => array
            (
                'mapping_class_name' => 'midcom_db_member',
                'master_fieldname' => 'uid',
                'member_fieldname' => 'gid',
                'master_is_id' => true,
                'allow_multiple' => true,
            ),
            'widget' => 'autocomplete',
            'widget_config' => array
            (
                'class' => 'midcom_db_group',
                'result_headers' => array
                (
                    array
                    (
                        'title' => 'name',
                        'name' => 'name',
                    ),
                    array
                    (
                        'title' => 'official',
                        'name' => 'official',
                    ),
                ),
                'searchfields' => array
                (
                    'name',
                    'official',
                ),
                'orders' => array
                (
                    array('owner' => 'ASC'),
                    array('name' => 'ASC'),
                ),
                'id_field' => 'id',
            ),
        ),
    ),
),