'config' => array
(
    'name'        => 'config',
    'description' => 'Default Configuration Schema', /* This is a topic */
    'fields'      => array
    (
        'owner_organization' => array
        (
            'title' => 'owner organization',
            'type' => 'select',
            'type_config' => array
            (
                'allow_other' => true,
                'require_corresponding_option' => false,
            ),
            'widget' => 'autocomplete',
            'widget_config' => array
            (
                'class'       => 'org_openpsa_contacts_group_dba',
                'component'   => 'org.openpsa.contacts',
                'titlefield'  => 'official',
                'searchfields'  => array
                (
                    'name',
                    'official'
                ),
                'constraints' => array
                (
                    array
                    (
                        'field' => 'orgOpenpsaObtype',
                        'op'    => '>=',
                        'value' => org_openpsa_contacts_group_dba::ORGANIZATION,
                    ),
                ),
                'result_headers' => array
                (
                    array
                    (
                        'title' => 'official',
                        'name' => 'official',
                    ),
                ),
                'orders'        => array
                (
                    array('official'    => 'ASC'),
                ),
            ),
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'org.openpsa.contacts',
                'name' => 'owner_organization'
            ),
            'start_fieldset' => array
            (
                'title' => 'basic and search settings',
            ),
        ),

        'organization_search_fields' => array
        (
            'title' => 'organization search fields',
            'type' => 'text',
            'widget' => 'text',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'org.openpsa.contacts',
                'name' => 'organization_search_fields'
            ),
        ),

        'person_search_fields' => array
        (
            'title' => 'person search fields',
            'type' => 'text',
            'widget' => 'text',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'org.openpsa.contacts',
                'name' => 'person_search_fields'
            ),
            'end_fieldset' => ''
        ),

        /* Schema settings */
        'schemadb_group' => array
        (
            'title' => 'organization schema database',
            'type' => 'text',
            'widget' => 'text',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'org.openpsa.contacts',
                'name' => 'schemadb_group'
            ),
            'start_fieldset' => array
            (
                'title' => 'advanced schema and data settings',
            ),
        ),

        'schemadb_person' => array
        (
            'title' => 'person schema database',
            'type' => 'text',
            'widget' => 'text',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'org.openpsa.contacts',
                'name' => 'schemadb_person'
            ),
            'end_fieldset' => ''
        ),
    ),
),