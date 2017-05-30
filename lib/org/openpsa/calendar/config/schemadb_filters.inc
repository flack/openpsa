'default' => array
(
    'description' => 'filters',
    'fields' => array
    (
        'people' => array
        (
            'title' => 'people',
            'storage' => array
            (
                'location' => 'parameter',
                'domain' => 'org.openpsa.calendar.filters',
                'name' => 'people',
            ),
            'type' => 'select',
            'type_config' => array
            (
                'options' => array(),
                'allow_other' => true,
                'allow_multiple' => true,
                'multiple_storagemode' => 'serialized',
                'require_corresponding_option' => false,
            ),
            'widget' => 'autocomplete',
            'widget_config' => array
            (
                'clever_class' => 'contact',
                'constraints' => array
                (
                    array
                    (
                        'field' => 'username',
                        'op'    => '<>',
                        'value' => '',
                    ),
                ),
                'id_field' => 'guid',
            ),
        ),
        'groups' => array
        (
            'title' => 'groups',
            'storage' => array
            (
                'location' => 'parameter',
                'domain' => 'org.openpsa.calendar.filters',
                'name' => 'groups',
            ),
            'type' => 'select',
            'type_config' => array
            (
                'options' => array(),
                'allow_other' => true,
                'allow_multiple' => true,
                'multiple_storagemode' => 'serialized',
                'require_corresponding_option' => false,
            ),
            'widget' => 'autocomplete',
            'widget_config' => array
            (
                'clever_class' => 'group',
                'id_field' => 'guid',
            ),
        ),
        'resources' => array
        (
            'title' => 'resources',
            'storage' => array
            (
                'location' => 'parameter',
                'domain' => 'org.openpsa.calendar.filters',
                'name' => 'resources',
            ),
            'type' => 'select',
            'type_config' => array
            (
                'options' => array(),
                'allow_other' => true,
                'allow_multiple' => true,
                'multiple_storagemode' => 'serialized',
                'require_corresponding_option' => false,
            ),
            'widget' => 'autocomplete',
            'widget_config' => array
            (
                'class' => 'org_openpsa_calendar_resource_dba',
                'result_headers' => array
                (
                    array(
                        'name' => 'title',
                        'title' => 'title',
                    ),
                    array(
                        'name' => 'location',
                        'title' => 'location',
                    ),
                ),
                'searchfields' => array
                (
                    'title',
                    'name',
                    'location',
                ),
                'orders' => array
                (
                    array('title' => 'ASC'),
                    array('name' => 'ASC'),
                    array('location' => 'ASC'),
                ),
                'id_field' => 'id',
            ),
        ),
    ),
),
