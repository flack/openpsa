'default' => [
    'description'   => 'person',
    'l10n_db' => 'org.openpsa.contacts',

    'fields'  => [
        'salutation' => [
            'title' => 'salutation',
            'storage' => 'salutation',
            'required' => true,
            'type' => 'select',
            'type_config' => [
                 'options' => [
                    0 => 'mr',
                    1 => 'ms'
                 ],
            ],
            'widget' => 'radiocheckselect',
        ],
        'title' => [
            'title' => 'title',
            'storage' => 'title',
            'type'  => 'text',
            'type_config' => [
                'maxlength' => 255,
            ],
            'widget' => 'text',
        ],
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
        'country' => [
            'title'    => 'country',
            'storage'  => 'country',
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
            'title'    => 'email',
            'storage'  => 'email',
            'type'     => 'text',
            'widget'   => 'text',
            'validation' => 'email',
            'required' => true,
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
        'skype' => [
            'title'    => 'skype name',
            'storage' => [
                'location' => 'configuration',
                'domain'   => 'org.openpsa.skype',
                'name'     => 'name',
            ],
            'type'     => 'text',
            'widget'   => 'text',
        ],
        'jid' => [
            'title'    => 'jabber id',
            'storage' => [
                'location' => 'configuration',
                'domain'   => 'org.openpsa.jabber',
                'name'     => 'jid',
            ],
            'type'     => 'text',
            'widget'   => 'text',
            'validation' => 'email',
        ],
        'organizations' => [
            'title' => 'organizations',
            'storage' => null,
            'type' => 'mnrelation',
            'type_config' => [
                'mapping_class_name' => 'org_openpsa_contacts_member_dba',
                'master_fieldname' => 'uid',
                'member_fieldname' => 'gid',
                'master_is_id' => true,
                'constraints' => [
                    [
                        'field' => 'orgOpenpsaObtype',
                        'op'    => '>=',
                        'value' => org_openpsa_contacts_group_dba::ORGANIZATION,
                    ],
                ],
            ],
            'widget' => 'autocomplete',
            'widget_config' => [
                'clever_class' => 'organization',
            ],
        ],
        'groups' => [
            'title' => 'groups',
            'storage' => null,
            'type' => 'mnrelation',
            'type_config' => [
                'mapping_class_name' => 'org_openpsa_contacts_member_dba',
                'master_fieldname' => 'uid',
                'member_fieldname' => 'gid',
                'master_is_id' => true,
                'constraints' => [
                    [
                        'field' => 'orgOpenpsaObtype',
                        'op'    => '<',
                        'value' => org_openpsa_contacts_group_dba::MYCONTACTS,
                    ], [
                        'field' => 'owner',
                        'op' => 'INTREE',
                        'value' => org_openpsa_contacts_interface::find_root_group()->id
                     ]
                ],
            ],
            'widget' => 'autocomplete',
            'widget_config' => [
                'class' => 'org_openpsa_contacts_group_dba',
                'component' => 'org.openpsa.contacts',
                'result_headers' => [
                    [
                        'title' => 'name',
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
        'photo' => [
            'title' => 'photo',
            'type' => 'photo',
            'type_config' => [
                'filter_chain'   => 'exifrotate()',
                'derived_images' => [
                    // Intentionally this way, so that portraits can be taller
                    'view' => 'exifrotate();resize(500,600)',
                    // Use specific thumbnail rule to allow for exifrotate
                    'thumbnail' => 'exifrotate();resize(200,200)',
                ],
            ],
            'widget' => 'photo',
            'widget_config' => [
                'show_title' => false
            ],
            'index_method' => 'noindex',
        ],
        'person_notes' => [
            'title' => 'notes',
            'storage' => 'extra',
            'type' => 'text',
            'type_config' => [
                'output_mode' => 'markdown'
            ],
            'widget' => 'markdown',
        ],
    ]
],

'employee' => [
    'description'   => 'employee',
    'l10n_db' => 'org.openpsa.contacts',
    'fields'  => [
        'firstname' => [
            'title'    => 'firstname',
            'storage'  => 'firstname',
            'type'     => 'text',
            'widget'   => 'text',
            'required' => true,
        ],
        'lastname' => [
            'title'    => 'lastname',
            'storage'  => 'lastname',
            'type'     => 'text',
            'widget'   => 'text',
        ],
        'homepage' => [
            'title'    => 'homepage',
            'storage'  => 'homepage',
            'type'     => 'text',
            'widget'   => 'url',
        ],
        'email' => [
            'title'    => 'email',
            'storage'  => 'email',
            'type'     => 'text',
            'widget'   => 'text',
            'validation' => 'email',
        ],
        'handphone' => [
            'title'    => 'mobile phone',
            'storage'  => 'handphone',
            'type'     => 'text',
            'widget'   => 'text',
        ],
        'skype' => [
            'title'    => 'skype name',
            'storage' => [
                'location' => 'configuration',
                'domain'   => 'org.openpsa.skype',
                'name'     => 'name',
            ],
            'type'     => 'text',
            'widget'   => 'text',
        ],
        'jid' => [
            'title'    => 'jabber id',
            'storage' => [
                'location' => 'configuration',
                'domain'   => 'org.openpsa.jabber',
                'name'     => 'jid',
            ],
            'type'     => 'text',
            'widget'   => 'text',
            'validation' => 'email',
        ],
        'weekly_workhours' => [
            'title'    => 'weekly workhours',
            'storage' => [
                'location' => 'configuration',
                'domain'   => 'org.openpsa.reports.projects',
                'name'     => 'weekly_workhours',
            ],
            'type'     => 'number',
            'widget'   => 'text',
        ],
        'competence' => [
            'title' => 'competence areas',
            'storage' => null,
            'type' => 'tags',
            'widget' => 'text',
        ],
        'notes' => [
            'title' => 'notes',
            'storage' => 'extra',
            'type' => 'text',
            'type_config' => [
                'output_mode' => 'markdown'
            ],
            'widget' => 'markdown',
        ],
    ]
],
