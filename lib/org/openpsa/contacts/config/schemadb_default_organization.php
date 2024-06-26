<?php
return [
    'organization' => [
        'description'   => 'organization',
        'fields'  => [
            'official' => [
                'title'    => 'official',
                'storage'  => 'official',
                'type'     => 'text',
                'widget'   => 'text',
                'required' => true,
                'start_fieldset' => [
                    'title' => 'basic information',
                    'css_group' => 'area meta',
                ],
                'index_method' => 'title',
            ],
            'company_id' => [
                'title'    => 'company_id',
                'storage'  => 'name',
                'type'     => 'text',
                'widget'   => 'text',
            ],
            'organization_type'  => [
                'title'  => 'organization_type',
                'storage'     => 'orgOpenpsaObtype',
                'type'     => 'select',
                'type_config' => [
                    'options' => [
                        org_openpsa_contacts_group_dba::ORGANIZATION => 'organization',
                        org_openpsa_contacts_group_dba::DAUGHTER     => 'daughter organization',
                        org_openpsa_contacts_group_dba::DEPARTMENT   => 'department'
                    ],
                ],
                'widget'       => 'radiocheckselect',
                'default' => org_openpsa_contacts_group_dba::ORGANIZATION
            ],
            'categories' => [
                'title' => 'categories',
                'type'    => 'select',
                'type_config' => [
                    'options' => [
                        'org_openpsa_category_partner'  => 'partner',
                        'org_openpsa_category_client'   => 'client',
                        'org_openpsa_category_vendor'   => 'vendor',
                    ],
                    'allow_multiple' => true,
                ],

                'widget' => 'radiocheckselect'
            ],
            'notes' => [
                'title' => 'notes',
                'widget'    => 'textarea',
                'type' => 'text',
                'storage'    => 'extra',
            ],
            'members' => [
                'title' => 'contacts',
                'storage' => null,
                'type' => 'mnrelation',
                'type_config' => [
                    'mapping_class_name' => midcom_db_member::class,
                    'master_fieldname' => 'gid',
                    'member_fieldname' => 'uid',
                    'master_is_id' => true,
                ],
                'widget' => 'autocomplete',
                'widget_config' => [
                    'clever_class' => 'contact',
                    'id_field' => 'id',
                    'creation_mode_enabled' => true,
                    'creation_handler' => midcom_connection::get_url('self') . "__mfa/org.openpsa.helpers/chooser/create/org_openpsa_contacts_person_dba/",
                    'creation_default_key' => 'lastname',
                ],
                'index_method' => 'noindex',
                'end_fieldset' => '',
            ],
            'homepage' => [
                'title'    => 'homepage',
                'storage'  => 'homepage',
                'type'     => 'text',
                'widget'   => 'url',
                'start_fieldset' => [
                    'title' => 'contact information',
                    'css_group' => 'area contact',
                ],
            ],
            'email' => [
                'title'    => 'email',
                'storage'  => 'email',
                'type'     => 'text',
                'widget'   => 'text',
                'validation' => 'email'
            ],
            'phone' => [
                'title'    => 'phone',
                'storage'  => 'phone',
                'type'     => 'text',
                'widget'   => 'text',
            ],
            'fax' => [
                'title'    => 'fax',
                'storage'  => 'fax',
                'type'     => 'text',
                'widget'   => 'text',
                'end_fieldset' => '',
            ],
            'street' => [
                'title'    => 'street',
                'storage'  => 'street',
                'type'     => 'text',
                'widget'   => 'text',
                'start_fieldset' => [
                    'title' => 'visiting address',
                    'css_group' => 'area contact visiting',
                ],
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
                'end_fieldset' => '',
            ],
           'postal_label' => [
                'title'    => 'name',
                'storage'  => 'parameter',
                'type'     => 'text',
                'widget'   => 'text',
                'start_fieldset' => [
                    'title' => 'postal address',
                    'css_group' => 'area contact postal',
                ],
            ],
           'postal_street' => [
                'title'    => 'street',
                'storage'  => 'postalStreet',
                'type'     => 'text',
                'widget'   => 'text',
            ],
            'postal_postcode' => [
                'title'    => 'postcode',
                'storage'  => 'postalPostcode',
                'type'     => 'text',
                'widget'   => 'text',
            ],
            'postal_city' => [
                'title'    => 'city',
                'storage'  => 'postalCity',
                'type'     => 'text',
                'widget'   => 'text',
            ],
            'postal_country' => [
                'title'    => 'country',
                'storage'  => 'postalCountry',
                'type'     => 'text',
                'widget'   => 'text',
                'end_fieldset' => '',
            ],
        ]
    ],
    'group' => [
        'description'   => 'group',
        'fields'  => [
            'official' => [
                'title'    => 'official',
                'storage'  => 'official',
                'type'     => 'text',
                'widget'   => 'text',
                'required' => true,
                'index_method' => 'title',
            ],
            'name' => [
                'title'    => 'name',
                'storage'  => 'name',
                'type'     => 'text',
                'widget'   => 'text',
            ],
            'owner' => [
                'title' => 'owner group',
                'storage' => 'owner',
                'type' => 'select',
                'type_config' => [
                     'require_corresponding_option' => false,
                     'allow_multiple' => false,
                     'options' => [],
                ],
                'widget' => 'autocomplete',
                'widget_config' => [
                    'clever_class' => 'group',
                ],
            ],
            'notes' => [
                'title' => 'notes',
                'widget'    => 'textarea',
                'type' => 'text',
                'storage'    => 'extra',
            ],
            'members' => [
                'title' => 'members',
                'storage' => null,
                'type' => 'mnrelation',
                'type_config' => [
                    'mapping_class_name' => midcom_db_member::class,
                    'master_fieldname' => 'gid',
                    'member_fieldname' => 'uid',
                    'master_is_id' => true,
                ],
                'widget' => 'autocomplete',
                'widget_config' => [
                    'clever_class' => 'contact',
                    'id_field' => 'id',
                    'creation_mode_enabled' => true,
                    'creation_handler' => midcom_connection::get_url('self') . "__mfa/org.openpsa.helpers/chooser/create/org_openpsa_contacts_person_dba/",
                    'creation_default_key' => 'lastname',
                ],
                'index_method' => 'noindex',
            ]
        ]
    ]
];
