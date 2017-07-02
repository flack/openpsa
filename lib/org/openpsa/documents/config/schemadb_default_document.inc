'default' => array (

    'name'        => 'default',
    'description' => 'Document',
    'fields'      => array
    (
        'title' => array
        (
            'title' => 'Title',
            'type'    => 'text',
            'widget' => 'text',
            'storage'    => 'title',
            'start_fieldset' => array
            (
                'title' => 'Document',
                'css_group' => 'area',
            )
        ),
        'abstract' => array
        (
            'title' => 'Abstract',
            'type'    => 'text',
            'widget' => 'textarea',
            'storage'    => 'abstract',
        ),
        'document' => array
        (
            'title' => 'Document',
            'type'    => 'blobs',
            'widget' => 'downloads',
            'type_config' => array
            (
                'max_count' => 1,
                'sortable' => false,
            ),
            'end_fieldset' => '',
            'index_method' => 'attachment',
            'required' => true
        ),
        'status_select' => array
        (
            'title' => 'document status',
            'storage'    => 'docStatus',
            'type'     => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    org_openpsa_documents_document_dba::STATUS_DRAFT  => 'draft',
                    org_openpsa_documents_document_dba::STATUS_REVIEW => 'review',
                    org_openpsa_documents_document_dba::STATUS_FINAL  => 'final',
                ),
            ),
            'widget'       => 'radiocheckselect',
            'index_merge_with_content' => false,
            'start_fieldset' => array
            (
                'title' => 'Metadata',
                'css_group' => 'area meta',
            ),
            'default' => org_openpsa_documents_document_dba::STATUS_DRAFT
        ),
        'keywords' => array
        (
            'title' => 'Keywords',
            'type'    => 'text',
            'widget' => 'text',
            'storage'    => 'keywords',
        ),
        'author' => array
        (
            'title' => 'author',
            'storage'    => 'author',
            'type'    => 'select',
            'type_config' => array
            (
                 'require_corresponding_option' => false,
                 'options' => array(),
            ),
            'widget'      => 'autocomplete',
            'widget_config' => array
            (
                'class'       => 'org_openpsa_contacts_person_dba',
                'component'   => 'org.openpsa.contacts',
                'titlefield'  => 'name',
                'id_field'     => 'id',
                'searchfields'  => array
                (
                    'firstname',
                    'lastname',
                    'username',
                ),
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
                'orders'        => array
                (
                    array('lastname'    => 'ASC'),
                    array('firstname'    => 'ASC'),

                ),
                'creation_mode_enabled' => true,
                'creation_handler' => midcom_connection::get_url('self') . "__mfa/org.openpsa.helpers/chooser/create/org_openpsa_contacts_person_dba/",
                'creation_default_key' => 'lastname',
             ),
        ),
        //Hidden field for orgOpenpsaObtype
        'object_type'  => array
        (
            'title' => 'object type',
            'default'  => org_openpsa_documents_document_dba::OBTYPE_DOCUMENT,
            'hidden'   => true,
            //'readonly'   => true,
            'storage' => 'orgOpenpsaObtype',
            'type' => 'number',
            'widget' => 'text',
        ),
        'topic' => array
        (
            'title' => 'directory',
            'storage'    => 'topic',
            'type'    => 'select',
            'type_config' => array
            (
                 'require_corresponding_option' => false,
                 'allow_multiple' => false,
            ),
            'widget'      => 'autocomplete',
            'widget_config' => array
            (
                'class'       => 'org_openpsa_documents_directory',
                'component'   => 'org.openpsa.documents',
                'titlefield'  => 'name',
                'id_field'     => 'id',
                'constraints' => array
                (
                    array
                    (
                        'field' => 'component',
                        'op'    => '=',
                        'value' => 'org.openpsa.documents',
                    ),
                ),
                'searchfields'  => array
                (
                    'extra',
                    'name',
                ),
                'result_headers' => array
                (
                    array
                    (
                        'title' => 'title',
                        'name' => 'extra',
                    ),
                ),
                'orders'        => array
                (
                    array('extra'    => 'ASC'),
                    array('name'    => 'ASC'),
                ),
             ),
            'required' => true,
            'index_merge_with_content' => false,
            'end_fieldset' => '',
        ),
        'orgOpenpsaAccesstype' => array
        (
            'title' => 'Access type',
            'storage'    => 'orgOpenpsaAccesstype',
            'type'     => 'select',
            'type_config' => array
            (
                'options' => org_openpsa_core_acl::get_options(),
            ),
            'widget'       => 'select',
            'start_fieldset' => array
            (
                'title' => 'Access control',
                'css_group' => 'area acl',
            ),
            'index_merge_with_content' => false,
        ),
        'orgOpenpsaOwnerWg' => array
        (
            'title' => 'Workgroup',
            'storage'    => 'orgOpenpsaOwnerWg',
            'type'     => 'select',
            'type_config' => array
            (
                'options' => org_openpsa_helpers_list::workgroups(),
            ),
            'widget'       => 'select',
            'end_fieldset' => '',
            'index_merge_with_content' => false,
        ),
    )
),