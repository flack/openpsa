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
            ),
            'required'    => true,
        ),
        'abstract' => array
        (
            'title' => 'Abstract',
            'type'    => 'text',
            'widget' => 'textarea',
            'storage'    => 'abstract',
            'widget_text_inputstyle' => 'longtext',
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
             ),
             'default'     => midcom::get()->auth->user->id,
        ),
    )
),