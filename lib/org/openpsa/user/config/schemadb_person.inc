'default' => [
    'description'   => 'person',

    'fields'  => [
        'firstname' => [
            'title'    => 'firstname',
            'storage'  => 'firstname',
            'type'     => 'text',
            'widget'   => 'text',
        ],
        'lastname' => [
            'title'    => 'lastname',
            'storage'  => 'lastname',
            'type'     => 'text',
            'widget'   => 'text',
            'required' => true,
        ],
        'street' => [
            'title'    => 'street',
            'storage'  => 'street',
            'type'     => 'text',
            'widget'   => 'text',
        ],
        'postcode' => [
            'title'    => 'postcode',
            'storage'  => 'postcode',
            'type'     => 'text',
            'widget'   => 'text',
        ],
        'city' => [
            'title'    => 'city',
            'storage'  => 'city',
            'type'     => 'text',
            'widget'   => 'text',
        ],
        'person_homepage' => [
            'title'    => 'homepage',
            'storage'  => 'homepage',
            'type'     => 'text',
            'widget'   => 'url',
        ],
        'email' => [
            'required' => true,
            'title'    => 'email',
            'storage'  => 'email',
            'type'     => 'text',
            'widget'   => 'text',
            'validation' => 'email',
        ],
        'workphone' => [
            'title'    => 'work phone',
            'storage'  => 'workphone',
            'type'     => 'text',
            'widget'   => 'text',
        ],
        'handphone' => [
            'title'    => 'mobile phone',
            'storage'  => 'handphone',
            'type'     => 'text',
            'widget'   => 'text',
        ],
        'homephone' => [
            'title'    => 'homephone',
            'storage'  => 'homephone',
            'type'     => 'text',
            'widget'   => 'text',
        ],
        'groups' => [
            'title' => 'groups',
            'storage' => null,
            'type' => 'mnrelation',
            'type_config' => [
                'mapping_class_name' => 'midcom_db_member',
                'master_fieldname' => 'uid',
                'member_fieldname' => 'gid',
                'master_is_id' => true,
                'constraints' => midcom::get()->componentloader->is_installed('org.openpsa.contacts') ? [
                	[
                        'field' => 'orgOpenpsaObtype',
                        'op'    => '<',
                        'value' => org_openpsa_contacts_group_dba::ORGANIZATION
                    ],
                ] : [],
            ],
            'widget' => 'autocomplete',
            'widget_config' => [
                'class' => 'midcom_db_group',
                'result_headers' => [
                	[
                        'name' => 'name',
                    ],
                ],
                'searchfields' => [
                    'name',
                    'official',
                ],
                'orders' => [
                    ['name' => 'ASC'],
                    ['official' => 'ASC'],
                ],
                'id_field' => 'id',
            ],
        ],
    ]
],
