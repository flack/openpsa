'organization' => array
(
    'description'   => 'organization',
    'fields'  => array
    (
        'official' => array
        (
            'title'    => 'official',
            'storage'  => 'official',
            'type'     => 'text',
            'widget'   => 'text',
            'required' => true,
            'start_fieldset' => array
            (
                'title' => 'basic information',
                'css_group' => 'area meta',
            ),
            'index_method' => 'title',
        ),
        'company_id' => array
        (
            'title'    => 'company_id',
            'storage'  => 'name',
            'type'     => 'text',
            'widget'   => 'text',
        ),
        'organization_type'  => array
        (
            'title'  => 'organization_type',
            'storage'     => 'orgOpenpsaObtype',
            'type'     => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    org_openpsa_contacts_group_dba::ORGANIZATION => 'organization',
                    org_openpsa_contacts_group_dba::DAUGHTER     => 'daughter organization',
                    org_openpsa_contacts_group_dba::DEPARTMENT   => 'department'
                ),
            ),
            'widget'       => 'radiocheckselect',
            'default' => org_openpsa_contacts_group_dba::ORGANIZATION
        ),
        'categories' => array
        (
            'title' => 'categories',
            'type'    => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    'org_openpsa_category_partner'  => 'partner',
                    'org_openpsa_category_client'   => 'client',
                    'org_openpsa_category_vendor'   => 'vendor',
                ),
                'allow_multiple' => true,
            ),

            'widget' => 'radiocheckselect'
        ),
        'notes' => array
        (
            'title' => 'notes',
            'widget'    => 'textarea',
            'type' => 'text',
            'storage'    => 'extra',
        ),
        'members' => array
        (
            'title' => 'contacts',
            'storage' => null,
            'type' => 'mnrelation',
            'type_config' => array
            (
                'mapping_class_name' => 'midcom_db_member',
                'master_fieldname' => 'gid',
                'member_fieldname' => 'uid',
                'master_is_id' => true,
            ),
            'widget' => 'autocomplete',
            'widget_config' => array
            (
                'class' => 'org_openpsa_contacts_person_dba',
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
                        'title' => 'email',
                        'name' => 'email',
                    ),
                ),

                'searchfields' => array
                (
                    'firstname',
                    'lastname',
                    'username',
                ),
                'orders' => array
                (
                    array('lastname' => 'ASC'),
                    array('firstname' => 'ASC'),
                ),
                'id_field' => 'id',
                'creation_mode_enabled' => true,
                'creation_handler' => midcom_connection::get_url('self') . "__mfa/org.openpsa.helpers/chooser/create/org_openpsa_contacts_person_dba/",
                'creation_default_key' => 'lastname',
            ),
            'index_method' => 'noindex',
            'end_fieldset' => '',
        ),
        'homepage' => array
        (
            'title'    => 'homepage',
            'storage'  => 'homepage',
            'type'     => 'text',
            'widget'   => 'url',
            'start_fieldset' => array
            (
                'title' => 'contact information',
                'css_group' => 'area contact',
            ),
        ),
        'email' => array
        (
            'title'    => 'email',
            'storage'  => 'email',
            'type'     => 'text',
            'widget'   => 'text',
            'validation' => 'email'
        ),
        'phone' => array
        (
            'title'    => 'phone',
            'storage'  => 'phone',
            'type'     => 'text',
            'widget'   => 'text',
        ),
        'fax' => array
        (
            'title'    => 'fax',
            'storage'  => 'fax',
            'type'     => 'text',
            'widget'   => 'text',
            'end_fieldset' => '',
        ),
        'street' => array
        (
            'title'    => 'street',
            'storage'  => 'street',
            'type'     => 'text',
            'widget'   => 'text',
            'start_fieldset' => array
            (
                'title' => 'visiting address',
                'css_group' => 'area contact visiting',
            ),
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
            'end_fieldset' => '',
        ),
       'postal_label' => array
        (
            'title'    => 'name',
            'storage'  => 'parameter',
            'type'     => 'text',
            'widget'   => 'text',
            'start_fieldset' => array
            (
                'title' => 'postal address',
                'css_group' => 'area contact postal',
            ),
        ),
       'postal_street' => array
        (
            'title'    => 'street',
            'storage'  => 'postalStreet',
            'type'     => 'text',
            'widget'   => 'text',
        ),
        'postal_postcode' => array
        (
            'title'    => 'postcode',
            'storage'  => 'postalPostcode',
            'type'     => 'text',
            'widget'   => 'text',
        ),
        'postal_city' => array
        (
            'title'    => 'city',
            'storage'  => 'postalCity',
            'type'     => 'text',
            'widget'   => 'text',
        ),
        'postal_country' => array
        (
            'title'    => 'country',
            'storage'  => 'postalCountry',
            'type'     => 'text',
            'widget'   => 'text',
            'end_fieldset' => '',
        ),
    )
),
'group' => array
(
    'description'   => 'group',
    'fields'  => array
    (
        'official' => array
        (
            'title'    => 'official',
            'storage'  => 'official',
            'type'     => 'text',
            'widget'   => 'text',
            'required' => true,
            'index_method' => 'title',
        ),
        'name' => array
        (
            'title'    => 'name',
            'storage'  => 'name',
            'type'     => 'text',
            'widget'   => 'text',
        ),
        'owner' => array
        (
            'title' => 'owner group',
            'storage' => 'owner',
            'type' => 'select',
            'type_config' => array
            (
                 'require_corresponding_option' => false,
                 'allow_multiple' => false,
                 'options' => array(),
            ),
            'widget' => 'autocomplete',
            'widget_config' => array
            (
                'clever_class' => 'group',
                'id_field' => 'id',
            ),
        ),
        'notes' => array
        (
            'title' => 'notes',
            'widget'    => 'textarea',
            'type' => 'text',
            'storage'    => 'extra',
        ),
        'members' => array
        (
            'title' => 'members',
            'storage' => null,
            'type' => 'mnrelation',
            'type_config' => array
            (
                'mapping_class_name' => 'midcom_db_member',
                'master_fieldname' => 'gid',
                'member_fieldname' => 'uid',
                'master_is_id' => true,
            ),
            'widget' => 'autocomplete',
            'widget_config' => array
            (
                'class' => 'org_openpsa_contacts_person_dba',
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
                        'title' => 'email',
                        'name' => 'email',
                    ),
                ),

                'searchfields' => array
                (
                    'firstname',
                    'lastname',
                    'username',
                ),
                'orders' => array
                (
                    array('lastname' => 'ASC'),
                    array('firstname' => 'ASC'),
                ),
                'id_field' => 'id',
                'creation_mode_enabled' => true,
                'creation_handler' => midcom_connection::get_url('self') . "__mfa/org.openpsa.helpers/chooser/create/org_openpsa_contacts_person_dba/",
                'creation_default_key' => 'lastname',
            ),
            'index_method' => 'noindex',
        )
    )
),
