'config' => array
(
    'description' => 'Default Configuration Schema', /* This is a topic */
    'fields' => array
    (
        /* redirection settings */
        'redirection_type' => array
        (
            'title' => 'redirection type',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'net.nemein.redirector',
                'name' => 'redirection_type',
            ),
            'type' => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    'subnode' => 'first subfolder',
                    'node' => 'selected node',
                    'url' => 'url entered below',
                    'permalink' => 'permalink guid',
                ),
            ),
            'widget' => 'select',
            'start_fieldset' => array
            (
                'title' => 'redirection settings',
            ),
        ),
        'redirection_url' => array
        (
            'title' => 'redirection url',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'net.nemein.redirector',
                'name' => 'redirection_url',
            ),
            'type' => 'text',
            'widget' => 'text',
        ),
        'redirection_node' => array
        (
            'title' => 'redirection node',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'net.nemein.redirector',
                'name' => 'redirection_node',
            ),
            'type' => 'select',
            'type_config' => array
            (
                'options' => array(),
                'allow_other' => true,
                'require_corresponding_option' => false,
            ),
            'widget' => 'chooser',
            'widget_config' => array
            (
                'class'       => 'midcom_db_topic',
                'component'   => 'net.nemein.redirector',
                'titlefield'  => 'extra',
                'id_field'     => 'guid',
                'searchfields' => array
                (
                    'title',
                    'extra',
                    'component',
                    'name',
                ),
                'constraints' => array
                (
                    array
                    (
                        'field' => 'extra',
                        'op' => '<>',
                        'value' => '',
                    ),
                    array
                    (
                        'field' => 'up',
                        'op' => 'INTREE',
                        'value' => midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ROOTTOPICID),
                    ),
                ),
                'result_headers' => array
                (
                    array
                    (
                        'name' => 'extra',
                        'title' => 'extra',
                    ),
                    array
                    (
                        'name' => 'name',
                        'title' => 'name',
                    ),
                    array
                    (
                        'name' => 'component',
                        'title' => 'component',
                    ),
                ),
                'orders' => array
                (
                    array
                    (
                        'title' => 'ASC',
                    ),
                    array
                    (
                        'extra' => 'ASC',
                    ),
                    array
                    (
                        'name' => 'ASC',
                    ),
                ),
            ),
        ),
        'redirection_guid' => array
        (
            'title' => 'permalink guid',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'net.nemein.redirector',
                'name' => 'redirection_guid',
            ),
            'type' => 'text',
            'widget' => 'text',
        ),
        'redirection_code' => array
        (
            'title' => 'redirection http code',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'net.nemein.redirector',
                'name' => 'redirection_code',
            ),
            'type' => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    301 => '301 Moved Permanently',
                    302 => '302 Found',
                    410 => '410 Gone',
                ),
            ),
            'widget' => 'select',
            'default' => 301,
        ),
        'redirection_metatag' => array
        (
            'title' => 'redirect with metatag',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'net.nemein.redirector',
                'name' => 'redirection_metatag',
            ),
            'type' => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    '' => 'default setting',
                    1 => 'yes',
                    0 => 'no',
                ),
            ),
            'widget' => 'select',
        ),
        'redirection_metatag_speed' => array
        (
            'title' => 'metatag refresh speed',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'net.nemein.redirector',
                'name' => 'redirection_metatag_speed',
            ),
            'type' => 'text',
            'widget' => 'text',
        ),
        'admin_redirection' => array
        (
            'title' => 'redirect administrators',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'net.nemein.redirector',
                'name' => 'admin_redirection',
            ),
            'type' => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    '' => 'default setting',
                    1 => 'yes',
                    0 => 'no',
                ),
            ),
            'widget' => 'select',
            'end_fieldset' => 1,
        ),
    ),
),