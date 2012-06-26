'default' => array
(
    'description' => 'directory',
    'fields'      => array
    (
        // Metadata
        'extra' => array
        (
            'title' => 'Title',
            'storage'    => 'extra',
            'type'    => 'text',
            'widget'    => 'text',
            'required'    => true,
            'start_fieldset' => array
            (
                'title' => 'directory',
                'css_group' => 'area',
            ),
            'end_fieldset' => '',
        ),
        'orgOpenpsaAccesstype' => array
        (
            'title' => 'Access type',
            'storage' => array
            (
                'location'      => 'configuration',
                'domain' => 'org.openpsa.core',
                'name'    => 'orgOpenpsaAccesstype',
             ),
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
            'title' => 'workgroup',
            'storage' => array
            (
                'location'      => 'configuration',
                'domain' => 'org.openpsa.core',
                'name'    => 'orgOpenpsaOwnerWg',
             ),
            'type'     => 'select',
            'type_config' => array
            (
                'options' => org_openpsa_helpers_list::workgroups(),
            ),
            'widget' => 'select',
            'end_fieldset' => '',
            'index_merge_with_content' => false,
        ),
    )
),