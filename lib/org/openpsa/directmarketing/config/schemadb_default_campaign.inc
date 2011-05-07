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
                    ORG_OPENPSA_OBTYPE_CAMPAIGN       => 'normal campaign',
                    ORG_OPENPSA_OBTYPE_CAMPAIGN_SMART => 'smart campaign',
                ),
            ),
            'widget'      => 'select',
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
            'widget' => 'textarea',
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
                'allow_multiple' => true,
                'require_corresponding_option' => false,
                'additional_fields' => array
                (
                    'orgOpenpsaObtype' => ORG_OPENPSA_OBTYPE_CAMPAIGN_TESTER,
                ),
            ),
            'widget' => 'chooser',
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
                'constraints' => array
                (
                    array
                    (
                        'field' => 'email',
                        'op' => '<>',
                        'value' => '',
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
                'options' => $GLOBALS['org_openpsa_core_acl_options'],
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