'default' => [
    'description' => 'directory',
    'fields'      => [
        // Metadata
        'extra' => [
            'title' => 'Title',
            'storage'    => 'extra',
            'type'    => 'text',
            'widget'    => 'text',
            'required'    => true,
            'start_fieldset' => [
                'title' => 'directory',
                'css_group' => 'area',
            ],
            'end_fieldset' => '',
        ],
        'orgOpenpsaAccesstype' => [
            'title' => 'Access type',
            'storage' => [
                'location'      => 'configuration',
                'domain' => 'org.openpsa.core',
                'name'    => 'orgOpenpsaAccesstype',
             ],
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
            'title' => 'workgroup',
            'storage' => [
                'location'      => 'configuration',
                'domain' => 'org.openpsa.core',
                'name'    => 'orgOpenpsaOwnerWg',
             ],
            'type'     => 'select',
            'type_config' => [
                'options' => org_openpsa_helpers_list::workgroups(),
            ],
            'widget' => 'select',
            'end_fieldset' => '',
            'index_merge_with_content' => false,
        ],
    ]
],