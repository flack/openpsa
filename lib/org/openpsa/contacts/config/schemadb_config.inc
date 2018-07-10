'config' => [
    'name'        => 'config',
    'description' => 'Default Configuration Schema', /* This is a topic */
    'fields'      => [
        'owner_organization' => [
            'title' => 'owner organization',
            'type' => 'select',
            'type_config' => [
                'allow_other' => true,
                'require_corresponding_option' => false,
            ],
            'widget' => 'autocomplete',
            'widget_config' => [
                'class'       => 'org_openpsa_contacts_group_dba',
                'component'   => 'org.openpsa.contacts',
                'titlefield'  => 'official',
                'searchfields'  => [
                    'name',
                    'official'
                ],
                'constraints' => [
                    [
                        'field' => 'orgOpenpsaObtype',
                        'op'    => '>=',
                        'value' => org_openpsa_contacts_group_dba::ORGANIZATION,
                    ],
                ],
                'result_headers' => [                    [
                        'title' => 'official',
                        'name' => 'official',
                    ],
                ],
                'orders'        => [
                    ['official'    => 'ASC'],
                ],
            ],
            'storage' => [
                'location' => 'configuration',
                'domain' => 'org.openpsa.contacts',
                'name' => 'owner_organization'
            ],
            'start_fieldset' => [
                'title' => 'basic and search settings',
            ],
        ],

        'organization_search_fields' => [
            'title' => 'organization search fields',
            'type' => 'text',
            'widget' => 'text',
            'storage' => [
                'location' => 'configuration',
                'domain' => 'org.openpsa.contacts',
                'name' => 'organization_search_fields'
            ],
        ],

        'person_search_fields' => [
            'title' => 'person search fields',
            'type' => 'text',
            'widget' => 'text',
            'storage' => [
                'location' => 'configuration',
                'domain' => 'org.openpsa.contacts',
                'name' => 'person_search_fields'
            ],
            'end_fieldset' => ''
        ],

        /* Schema settings */
        'schemadb_group' => [
            'title' => 'organization schema database',
            'type' => 'text',
            'widget' => 'text',
            'storage' => [
                'location' => 'configuration',
                'domain' => 'org.openpsa.contacts',
                'name' => 'schemadb_group'
            ],
            'start_fieldset' => [
                'title' => 'advanced schema and data settings',
            ],
        ],

        'schemadb_person' => [
            'title' => 'person schema database',
            'type' => 'text',
            'widget' => 'text',
            'storage' => [
                'location' => 'configuration',
                'domain' => 'org.openpsa.contacts',
                'name' => 'schemadb_person'
            ],
            'end_fieldset' => ''
        ],
    ],
],