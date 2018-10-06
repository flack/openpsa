'default' => [
    'name' => 'default',
    'description' => 'event',
    'fields' => [
        //Hidden field for orgOpenpsaObtype
        'object_type' => [
            'title' => 'OpenPSA type',
            'type' => 'number',
            'storage' => 'orgOpenpsaObtype',
            'widget' =>  'text',
            'default' => org_openpsa_calendar_event_dba::OBTYPE_EVENT,
            'hidden' => true,
        ],
        'title' => [
            'title' => 'title',
            'storage' => 'title',
            'type' => 'text',
            'widget' =>  'text',
            'required' => true,
        ],
        'location' => [
            'title' => 'location',
            'storage' => 'location',
            'type' => 'text',
            'widget' =>  'text',
        ],
        'start' => [
            'title' => 'start time',
            'storage' => 'start',
            'type' => 'date',
            'type_config' => [
                'storage_type' => 'UNIXTIME',
            ],
            'widget' => 'jsdate',
            'required' => true,
        ],
        'end' => [
            'title' => 'end time',
            'storage' => 'end',
            'type' => 'date',
            'type_config' => [
                'storage_type' => 'UNIXTIME',
                'later_than' => 'start',
            ],
            'widget' => 'jsdate',
            'required' => true,
        ],
        'description' => [
            'title' => 'description',
            'storage' => 'description',
            'type' => 'text',
            'type_config' => [
                'output_mode' => 'markdown',
            ],
            'widget' => 'markdown',
        ],
        'participants' => [
            'title' => 'participants',
            'storage' => null,
            'type' => 'mnrelation',
            'type_config' => [
                'mapping_class_name' => 'org_openpsa_calendar_event_member_dba',
                'master_fieldname' => 'eid',
                'member_fieldname' => 'uid',
                'master_is_id' => true,
            ],
            'widget' => 'autocomplete',
            'widget_config' => [
                'clever_class' => 'contact',
                'id_field' => 'id',
            ],
        ],
        'resources' => [
            'title' => 'resources',
            'storage' => null,
            'type' => 'mnrelation',
            'type_config' => [
                'mapping_class_name' => 'org_openpsa_calendar_event_resource_dba',
                'master_fieldname' => 'event',
                'member_fieldname' => 'resource',
                'master_is_id' => true,
            ],
            'widget' => 'autocomplete',
            'widget_config' => [
                'class' => 'org_openpsa_calendar_resource_dba',
                'id_field' => 'id',
                'searchfields' => [
                    'title',
                    'location'
                ],
                'result_headers' => [
                    [
                        'name' => 'location',
                        'title' => 'location',
                    ],
                ],
            ],
        ],
        'orgOpenpsaAccesstype' => [
            'title' => 'access type',
            'storage' => 'orgOpenpsaAccesstype',
            'type' => 'select',
            'type_config' => [
                'options' => [
                    org_openpsa_core_acl::ACCESS_PUBLIC => midcom::get()->i18n->get_string('public', 'org.openpsa.core'),
                    org_openpsa_core_acl::ACCESS_PRIVATE => midcom::get()->i18n->get_string('private', 'org.openpsa.core'),
                ],
            ],
            'widget' => 'select',
        ],
        'busy' => [
            'title' => 'dont allow overlapping',
            'storage' => 'busy',
            'type' => 'boolean',
            'widget' => 'checkbox',
            'default' => 1,
        ],
    ],
],

'private' => [
    'name' => 'private',
    'description' => 'event',
    'fields' => [
        'title' => [
            'title' => 'title',
            'storage' => 'title',
            'type' => 'text',
            'widget' =>  'text',
            'required' => true,
        ],
        'start' => [
            'title' => 'start time',
            'storage' => 'start',
            'type' => 'date',
            'type_config' => [
                'storage_type' => 'UNIXTIME',
            ],
            'widget' => 'jsdate',
            'required' => true,
        ],
        'end' => [
            'title' => 'end time',
            'storage' => 'end',
            'type' => 'date',
            'type_config' => [
                'storage_type' => 'UNIXTIME',
                'later_than' => 'start',
            ],
            'widget' => 'jsdate',
            'required' => true,
        ],
    ],
],