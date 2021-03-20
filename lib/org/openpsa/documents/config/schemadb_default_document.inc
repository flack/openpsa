'default' => [

    'name'        => 'default',
    'description' => 'Document',
    'fields'      => [
        'title' => [
            'title' => 'Title',
            'type'    => 'text',
            'widget' => 'text',
            'storage'    => 'title',
            'start_fieldset' => [
                'title' => 'Document',
                'css_group' => 'area',
            ]
        ],
        'abstract' => [
            'title' => 'Abstract',
            'type'    => 'text',
            'widget' => 'textarea',
            'storage'    => 'abstract',
        ],
        'document' => [
            'title' => 'Document',
            'type'    => 'blobs',
            'widget' => 'downloads',
            'type_config' => [
                'max_count' => 1,
                'sortable' => false,
            ],
            'end_fieldset' => '',
            'index_method' => 'attachment',
            'required' => true
        ],
        'status_select' => [
            'title' => 'document status',
            'storage'    => 'docStatus',
            'type'     => 'select',
            'type_config' => [
                'options' => [
                    org_openpsa_documents_document_dba::STATUS_DRAFT  => 'draft',
                    org_openpsa_documents_document_dba::STATUS_REVIEW => 'review',
                    org_openpsa_documents_document_dba::STATUS_FINAL  => 'final',
                ],
            ],
            'widget'       => 'radiocheckselect',
            'index_merge_with_content' => false,
            'start_fieldset' => [
                'title' => 'Metadata',
                'css_group' => 'area meta',
            ],
            'default' => org_openpsa_documents_document_dba::STATUS_DRAFT
        ],
        'keywords' => [
            'title' => 'Keywords',
            'type'    => 'text',
            'widget' => 'text',
            'storage'    => 'keywords',
        ],
        'author' => [
            'title' => 'author',
            'storage'    => 'author',
            'type'    => 'select',
            'type_config' => [
                 'require_corresponding_option' => false,
                 'options' => [],
            ],
            'widget'      => 'autocomplete',
            'widget_config' => [
                'clever_class'       => 'contact',
                'id_field'     => 'id',
                'creation_mode_enabled' => midcom::get()->componentloader->is_installed('org.openpsa.contacts'),
                'creation_handler' => midcom_connection::get_url('self') . "__mfa/org.openpsa.helpers/chooser/create/org_openpsa_contacts_person_dba/",
                'creation_default_key' => 'lastname',
             ],
        ],
        'topic' => [
            'title' => 'directory',
            'storage'    => 'topic',
            'type'    => 'select',
            'type_config' => [
                 'require_corresponding_option' => false,
                 'allow_multiple' => false,
            ],
            'widget'      => 'autocomplete',
            'widget_config' => [
                'class'       => 'org_openpsa_documents_directory',
                'component'   => 'org.openpsa.documents',
                'id_field'     => 'id',
                'constraints' => [
                    [
                        'field' => 'component',
                        'op'    => '=',
                        'value' => 'org.openpsa.documents',
                    ],
                ],
                'searchfields'  => [
                    'extra',
                    'name',
                ],
                'result_headers' => [
                    [
                        'title' => 'title',
                        'name' => 'name',
                    ],
                ],
                'orders'        => [
                    ['extra'    => 'ASC'],
                    ['name'    => 'ASC'],
                ],
             ],
            'required' => true,
            'index_merge_with_content' => false,
            'end_fieldset' => '',
        ],
        'orgOpenpsaAccesstype' => [
            'title' => 'Access type',
            'storage'    => 'orgOpenpsaAccesstype',
            'type'     => 'select',
            'type_config' => [
                'options' => org_openpsa_core_acl::get_options(),
            ],
            'widget'       => 'select',
            'start_fieldset' => [
                'title' => 'Access control',
                'css_group' => 'area acl',
            ],
            'index_merge_with_content' => false,
        ],
        'orgOpenpsaOwnerWg' => [
            'title' => 'Workgroup',
            'storage'    => 'orgOpenpsaOwnerWg',
            'type'     => 'select',
            'type_config' => [
                'options' => org_openpsa_helpers_list::workgroups(),
            ],
            'widget'       => 'select',
            'end_fieldset' => '',
            'index_merge_with_content' => false,
        ],
    ]
],