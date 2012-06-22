'config' => array
(
    'name'        => 'config',
    'description' => 'Default Configuration Schema', /* This is a topic */
    'fields'      => array
    (
        /* Schema settings */
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
            'start_fieldset' => array
            (
                'title' => 'advanced schema and data settings',
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
        ),

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
        ),
    ),
),