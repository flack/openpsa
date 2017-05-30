'default' => array
(
    'name' => 'default',
    'description' => 'event',
    'fields' => array
    (
        //Hidden field for orgOpenpsaObtype
        'object_type' => array
        (
            'title' => 'OpenPSA type',
            'type' => 'number',
            'storage' => 'orgOpenpsaObtype',
            'widget' =>  'text',
            'default' => org_openpsa_calendar_event_dba::OBTYPE_EVENT,
            'hidden' => true,
        ),
        'title' => array
        (
            'title' => 'title',
            'storage' => 'title',
            'type' => 'text',
            'widget' =>  'text',
            'required' => true,
        ),
        'location' => array
        (
            'title' => 'location',
            'storage' => 'location',
            'type' => 'text',
            'widget' =>  'text',
        ),
        'start' => array
        (
            'title' => 'start time',
            'storage' => 'start',
            'type' => 'date',
            'type_config' => array
            (
                'storage_type' => 'UNIXTIME',
            ),
            'widget' => 'jsdate',
            'required' => true,
        ),
        'end' => array
        (
            'title' => 'end time',
            'storage' => 'end',
            'type' => 'date',
            'type_config' => array
            (
                'storage_type' => 'UNIXTIME',
                'later_than' => 'start',
            ),
            'widget' => 'jsdate',
            'required' => true,
        ),
        'description' => array
        (
            'title' => 'description',
            'storage' => 'description',
            'type' => 'text',
            'type_config' => array
            (
                'output_mode' => 'markdown',
            ),
            'widget' => 'markdown',
        ),
        'participants' => array
        (
            'title' => 'participants',
            'storage' => null,
            'type' => 'mnrelation',
            'type_config' => array
            (
                'mapping_class_name' => 'org_openpsa_calendar_event_member_dba',
                'master_fieldname' => 'eid',
                'member_fieldname' => 'uid',
                'master_is_id' => true,
            ),
            'widget' => 'autocomplete',
            'widget_config' => array
            (
                'clever_class' => 'contact',
                'id_field' => 'id',
            ),
        ),
        'resources' => array
        (
            'title' => 'resources',
            'storage' => null,
            'type' => 'mnrelation',
            'type_config' => array
            (
                'mapping_class_name' => 'org_openpsa_calendar_event_resource_dba',
                'master_fieldname' => 'event',
                'member_fieldname' => 'resource',
                'master_is_id' => true,
            ),
            'widget' => 'autocomplete',
            'widget_config' => array
            (
                'class' => 'org_openpsa_calendar_resource_dba',
                'id_field' => 'id',
                'searchfields' => array
                (
                    'title',
                    'location'
                ),
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
            ),
        ),
        'orgOpenpsaAccesstype' => array
        (
            'title' => 'access type',
            'storage' => 'orgOpenpsaAccesstype',
            'type' => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    org_openpsa_core_acl::ACCESS_PUBLIC => midcom::get()->i18n->get_string('public', 'org.openpsa.core'),
                    org_openpsa_core_acl::ACCESS_PRIVATE => midcom::get()->i18n->get_string('private', 'org.openpsa.core'),
                ),
            ),
            'widget' => 'select',
        ),
        'busy' => array
        (
            'title' => 'dont allow overlapping',
            'storage' => 'busy',
            'type' => 'boolean',
            'widget' => 'checkbox',
            'default' => 1,
        ),
    ),
),

'private' => array
(
    'name' => 'private',
    'description' => 'event',
    'fields' => array
    (
        'title' => array
        (
            'title' => 'title',
            'storage' => 'title',
            'type' => 'text',
            'widget' =>  'text',
            'required' => true,
        ),
        'start' => array
        (
            'title' => 'start time',
            'storage' => 'start',
            'type' => 'date',
            'type_config' => array
            (
                'storage_type' => 'UNIXTIME',
            ),
            'widget' => 'jsdate',
            'required' => true,
        ),
        'end' => array
        (
            'title' => 'end time',
            'storage' => 'end',
            'type' => 'date',
            'type_config' => array
            (
                'storage_type' => 'UNIXTIME',
                'later_than' => 'start',
            ),
            'widget' => 'jsdate',
            'required' => true,
        ),
    ),
),