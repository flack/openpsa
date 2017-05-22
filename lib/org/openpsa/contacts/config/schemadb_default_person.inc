'default' => array
(
    'description'   => 'person',
    'l10n_db' => 'org.openpsa.contacts',

    'fields'  => array
    (
        'salutation' => array
        (
            'title' => 'salutation',
            'storage' => 'salutation',
            'required' => true,
            'type' => 'select',
            'type_config' => array
            (
                 'options' => array
                 (
                    0 => 'mr',
                    1 => 'ms'
                 ),
            ),
            'widget' => 'radiocheckselect',
        ),
        'title' => array
        (
            'title' => 'title',
            'storage' => 'title',
            'type'  => 'text',
            'type_config' => array
            (
                'maxlength' => 255,
            ),
            'widget' => 'text',
        ),
        'firstname' => array
        (
            'title'    => 'firstname',
            'storage'  => 'firstname',
            'type'     => 'text',
            'widget'   => 'text',
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
        'country' => array
        (
            'title'    => 'country',
            'storage'  => 'country',
            'type'     => 'text',
            'widget'   => 'text',
        ),
        'person_homepage' => array
        (
            'title'    => 'homepage',
            'storage'  => 'homepage',
            'type'     => 'text',
            'widget'   => 'text',
        ),
        'email' => array
        (
            'title'    => 'email',
            'storage'  => 'email',
            'type'     => 'text',
            'widget'   => 'text',
            'validation' => 'email',
            'required' => true,
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
        ),
        'skype' => array
        (
            'title'    => 'skype name',
            'storage' => array
            (
                'location' => 'configuration',
                'domain'   => 'org.openpsa.skype',
                'name'     => 'name',
            ),
            'type'     => 'text',
            'widget'   => 'text',
        ),
        'jid' => array
        (
            'title'    => 'jabber id',
            'storage' => array
            (
                'location' => 'configuration',
                'domain'   => 'org.openpsa.jabber',
                'name'     => 'jid',
            ),
            'type'     => 'text',
            'widget'   => 'text',
            'validation' => 'email',
        ),
        'organizations' => array
        (
            'title' => 'organizations',
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
                        'op'    => '>=',
                        'value' => org_openpsa_contacts_group_dba::ORGANIZATION,
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
                    array
                    (
                        'field' => 'owner',
                        'op' => 'INTREE',
                        'value' => org_openpsa_contacts_interface::find_root_group()->id
                    )
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
        ),
        'photo' => Array
        (
            'title' => 'photo',
            'type' => 'photo',
            'type_config' => Array
            (
                'filter_chain'   => 'exifrotate()',
                'derived_images' => array
                (
                    // Intentionally this way, so that portraits can be taller
                    'view' => 'exifrotate();resize(500,600)',
                    // Use specific thumbnail rule to allow for exifrotate
                    'thumbnail' => 'exifrotate();resize(200,200)',
                ),
            ),
            'widget' => 'photo',
            'widget_config' => array
            (
                'show_title' => false
            ),
            'index_method' => 'noindex',
        ),
        'person_notes' => array
        (
            'title' => 'notes',
            'storage' => 'extra',
            'type' => 'text',
            'type_config' => array
            (
                'output_mode' => 'markdown'
            ),
            'widget' => 'markdown',
        ),
    )
),

'employee' => array
(
    'description'   => 'employee',
    'l10n_db' => 'org.openpsa.contacts',
    'fields'  => array
    (
        'firstname' => array
        (
            'title'    => 'firstname',
            'storage'  => 'firstname',
            'type'     => 'text',
            'widget'   => 'text',
            'required' => true,
        ),
        'lastname' => array
        (
            'title'    => 'lastname',
            'storage'  => 'lastname',
            'type'     => 'text',
            'widget'   => 'text',
        ),
        'homepage' => array
        (
            'title'    => 'homepage',
            'storage'  => 'homepage',
            'type'     => 'text',
            'widget'   => 'text',
        ),
        'email' => array
        (
            'title'    => 'email',
            'storage'  => 'email',
            'type'     => 'text',
            'widget'   => 'text',
            'validation' => 'email',
        ),
        'handphone' => array
        (
            'title'    => 'mobile phone',
            'storage'  => 'handphone',
            'type'     => 'text',
            'widget'   => 'text',
        ),
        'skype' => array
        (
            'title'    => 'skype name',
            'storage' => array
            (
                'location' => 'configuration',
                'domain'   => 'org.openpsa.skype',
                'name'     => 'name',
            ),
            'type'     => 'text',
            'widget'   => 'text',
        ),
        'jid' => array
        (
            'title'    => 'jabber id',
            'storage' => array
            (
                'location' => 'configuration',
                'domain'   => 'org.openpsa.jabber',
                'name'     => 'jid',
            ),
            'type'     => 'text',
            'widget'   => 'text',
            'validation' => 'email',
        ),
        'weekly_workhours' => array
        (
            'title'    => 'weekly workhours',
            'storage' => array
            (
                'location' => 'configuration',
                'domain'   => 'org.openpsa.reports.projects',
                'name'     => 'weekly_workhours',
            ),
            'type'     => 'number',
            'widget'   => 'text',
        ),
        'competence' => Array
        (
            'title' => 'competence areas',
            'storage' => null,
            'type' => 'tags',
            'widget' => 'text',
        ),
        'notes' => array
        (
            'title' => 'notes',
            'storage' => 'extra',
            'type' => 'text',
            'type_config' => array
            (
                'output_mode' => 'markdown'
            ),
            'widget' => 'markdown',
        ),
    )
),
