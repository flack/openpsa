'default' => array
(
    'description' => 'campaign',
    'fields'      => array
    (
        // Metadata
        'title' => array
        (
            // COMPONENT-REQUIRED
            'title'    => 'title',
            'type'     => 'text',
            'widget'   => 'text',
            'storage'  => 'title',
            'required' => true,
        ),
        'type' => array
        (
            'title'       => 'type',
            'storage'     => 'orgOpenpsaObtype',
            'type'        => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    org_openpsa_directmarketing_campaign_dba::TYPE_NORMAL => 'normal campaign',
                    org_openpsa_directmarketing_campaign_dba::TYPE_SMART => 'smart campaign',
                ),
            ),
            'default'     => org_openpsa_directmarketing_campaign_dba::TYPE_NORMAL,
            'widget'      => 'radiocheckselect',
        ),
        'description' => array
        (
            'title' => 'description',
            'storage' => 'description',
            'type' => 'text',
            'type_config' => array
            (
                'output_mode' => 'markdown'
            ),
            'widget' => 'markdown',
        ),
        'testers' => array
        (
            'title'       => 'testers',
            'storage'     => null,
            'type'        => 'mnrelation',
            'type_config' => array
            (
                'mapping_class_name' => 'org_openpsa_directmarketing_campaign_member_dba',
                'master_fieldname' => 'campaign',
                'master_is_id' => true,
                'member_fieldname' => 'person',
                'options' => array(),
                'additional_fields' => array
                (
                    'orgOpenpsaObtype' => org_openpsa_directmarketing_campaign_member_dba::TESTER,
                ),
                'constraints' => array
                (
                    array
                    (
                        'field' => 'email',
                        'op' => '<>',
                        'value' => '',
                    ),
                ),
            ),
            'widget' => 'autocomplete',
            'widget_config' => array
            (
                'clever_class' => 'contact',
                'id_field' => 'id',
                'result_headers' => array
                (
                    array
                    (
                        'name' => 'name',
                        'title' => 'name',
                    ),
                    array
                    (
                        'name' => 'email',
                        'title' => 'email',
                    ),
                    array
                    (
                        'name' => 'handphone',
                        'title' => 'handphone',
                    ),
                    array
                    (
                        'name' => 'username',
                        'title' => 'username',
                    ),
                ),
                'searchfields' => array
                (
                    'firstname',
                    'lastname',
                    'email',
                    'username',
                    'handphone',
                ),
                'auto_wildcards' => 'both',
            ),
        ),
        'orgOpenpsaAccesstype' => array
        (
            'title'       => 'access type',
            'storage'     => 'orgOpenpsaAccesstype',
            'type'        => 'select',
            'type_config' => array
            (
                'options' => org_openpsa_core_acl::get_options(),
            ),
            'widget'      => 'select',
        ),
        'orgOpenpsaOwnerWg' => array
        (
            'title'       => 'workgroup',
            'storage'     => 'orgOpenpsaOwnerWg',
            'type'        => 'select',
            'type_config' => array
            (
                'options' => org_openpsa_helpers_list::workgroups(),
            ),
            'widget'      => 'select',
        ),
    )
),