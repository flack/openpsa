'default' => array
(
    'description'   => 'person',

    'fields'  => array
    (
        'firstname' => array
        (
            'title'    => 'firstname',
            'storage'  => 'firstname',
            'type'     => 'text',
            'widget'   => 'text',
            'start_fieldset' => array
            (
                'title' => 'person_fieldset',
                'css_group' => 'area meta',
            ),
        ),
        'lastname' => array
        (
            'title'    => 'lastname',
            'storage'  => 'lastname',
            'type'     => 'text',
            'widget'   => 'text',
            'required' => true,
        ),
        'street' => array
        (
            'title'    => 'street',
            'storage'  => 'street',
            'type'     => 'text',
            'widget'   => 'text',
        ),
        'postcode' => array
        (
            'title'    => 'postcode',
            'storage'  => 'postcode',
            'type'     => 'text',
            'widget'   => 'text',
        ),
        'city' => array
        (
            'title'    => 'city',
            'storage'  => 'city',
            'type'     => 'text',
            'widget'   => 'text',
        ),
        'person_homepage' => array
        (
            'title'    => 'homepage',
            'storage'  => 'homepage',
            'type'     => 'text',
            'widget'   => 'url',
        ),
        'email' => array
        (
            'required' => true,

            'title'    => 'email',
            'storage'  => 'email',
            'type'     => 'text',
            'widget'   => 'text',
            'validation' => 'email',
        ),
        'workphone' => array
        (
            'title'    => 'work phone',
            'storage'  => 'workphone',
            'type'     => 'text',
            'widget'   => 'text',
        ),
        'handphone' => array
        (
            'title'    => 'mobile phone',
            'storage'  => 'handphone',
            'type'     => 'text',
            'widget'   => 'text',
        ),
        'homephone' => array
        (
            'title'    => 'homephone',
            'storage'  => 'homephone',
            'type'     => 'text',
            'widget'   => 'text',
            'end_fieldset' => '',
        ),
        'groups' => array
        (
            'title' => 'groups',
            'storage' => null,
            'type' => 'mnrelation',
            'type_config' => array
            (
                'mapping_class_name' => 'org_openpsa_contacts_member_dba',
                'master_fieldname' => 'uid',
                'member_fieldname' => 'gid',
                'master_is_id' => true,
                'constraints' => array
                (
                    array
                    (
                        'field' => 'orgOpenpsaObtype',
                        'op'    => '<',
                        'value' => org_openpsa_contacts_group_dba::MYCONTACTS,
                    ),
                ),
            ),
            'widget' => 'autocomplete',
            'widget_config' => array
            (
                'class' => 'org_openpsa_contacts_group_dba',
                'component' => 'org.openpsa.contacts',
                'titlefield' => 'name',

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
                    array('name' => 'ASC'),
                    array('official' => 'ASC'),
                ),
                'id_field' => 'id',
            ),
            'start_fieldset' => array
            (
                'title' => 'groups_fieldset',
                'css_group' => 'area meta',
            ),
            'end_fieldset' => '',
        ),
    )
),
