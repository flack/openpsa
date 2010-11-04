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
            'default' => ORG_OPENPSA_OBTYPE_EVENT,
            'hidden' => true,
        ),
        'title' => array
        (
            'title' => 'title',
            'storage' => 'title',
            'type' => 'text',
            'widget' =>  'text',
            'start_fieldset' => array
            (
                'title' => 'event information',
                'css_group' => 'area',
            ),
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
                'earlier_field' => 'start',
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
            'widget' => 'textarea',
            'end_fieldset' => '',
        ),
        'participants' => array
        (
            'title' => 'participants',
            'storage' => null,
            'type' => 'mnrelation',
            'type_config' => array
            (
                'mapping_class_name' => 'midcom_db_eventmember',
                'master_fieldname' => 'eid',
                'member_fieldname' => 'uid',
                'master_is_id' => true,
            ),
            'widget' => 'chooser',
            'widget_config' => array
            (
                'clever_class' => 'contact',
                'id_field' => 'id',
            ),
            'start_fieldset' => array
            (
                'title' => 'participants',
                'css_group' => 'area',
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
                    ORG_OPENPSA_ACCESSTYPE_PUBLIC => 'public',
                    ORG_OPENPSA_ACCESSTYPE_PRIVATE => 'private',
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
            'end_fieldset' => '',
        ),
    ),
),