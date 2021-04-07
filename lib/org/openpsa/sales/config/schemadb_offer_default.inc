'default' => [
    'description' => 'salesproject',
    'fields'      => [
    'designation' => [
            'title' => 'designation',
            'storage' => 'designation',
            'type' => 'text',
            'widget' => 'text',
        ],
        'introduction' => [
            'title' => 'introduction',
            'storage' => 'introduction',
            'type' => 'text',
            'type_config' => [
                'output_mode' => 'markdown'
            ],

            'widget' => 'textarea',
            'required' => true,

        ],
        'notice' => [
            'title' => 'notice',
            'storage' => 'notice',
            'type' => 'text',
            'type_config' => [
                'output_mode' => 'markdown'
            ],
            'widget' => 'textarea',
        ],
        'deliverables' => [
            'title' => 'deliverables',
            'storage' => 'deliverables',
            'type' => 'select',
            'type_config' => [
                'allow_multiple' => true
            ],
            'widget' => 'autocomplete',
            'widget_config' => [
                'class' => 'org_openpsa_sales_salesproject_deliverable_dba',
                'titlefield' => 'title',
                'sortable' => true,
                'constraints' => [],
                'searchfields' => [
                    'title',
                ],
                'orders' => [
                    ['title' => 'ASC'],
                ],
                'id_field' => 'id',
            ],
        ],
    ]
],