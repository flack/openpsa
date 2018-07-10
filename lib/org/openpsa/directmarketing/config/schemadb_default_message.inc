'textemail' => [
    'description' => 'text email',
    'fields'      => [
        'title' => [
            // COMPONENT-REQUIRED
            'title'    => 'title',
            'type'     => 'text',
            'widget'   => 'text',
            'storage'  => 'title',
            'required' => true,
            'start_fieldset' => [
                'title' => 'description',
                'css_group' => 'area meta',
            ],
        ],
        'description' => [
            'title' => 'description',
            'storage' => 'description',
            'type' => 'text',
            'type_config' => [
                'output_mode' => 'markdown'
            ],
            'widget' => 'markdown',
            'end_fieldset' => ''
        ],
        'subject' => [
            'title'    => 'subject',
            'type'     => 'text',
            'widget'   => 'text',
            'storage'  => 'parameter',
            'required' => true,
            'start_fieldset' => [
                'title' => 'email message',
                'css_group' => 'area meta',
            ],
        ],
        'from' => [
            'title'    => 'from address',
            'type'     => 'text',
            'widget'   => 'text',
            'storage'  => 'parameter',
            'required' => true,
        ],
        'reply-to' => [
            'title'    => 'reply to address',
            'type'     => 'text',
            'widget'   => 'text',
            'storage'  => 'parameter',
            'validation' => 'email'
        ],
        'content' => [
            'title'    => 'content',
            'type'     => 'text',
            'type_config' => [
                'output_mode' => 'pre'
            ],
            'widget'   => 'textarea',
            'storage'  => 'parameter',
            'required' => true,
        ],
        'attachments' => [
            'title'    => 'files to attach',
            'type'     => 'blobs',
            'widget'   => 'downloads',
            'end_fieldset' => ''
        ],
    ],
    'customdata'  => [
        'org_openpsa_directmarketing_messagetype' => org_openpsa_directmarketing_campaign_message_dba::EMAIL_TEXT,
    ],
],
'htmlemail' => [
    'description' => 'html email',
    'fields'      => [
        'title' => [
            // COMPONENT-REQUIRED
            'title'    => 'title',
            'type'     => 'text',
            'widget'   => 'text',
            'storage'  => 'title',
            'required' => true,
            'start_fieldset' => [
                'title' => 'description',
                'css_group' => 'area meta',
            ],
        ],
        'description' => [
            'title' => 'description',
            'storage' => 'description',
            'type' => 'text',
            'type_config' => [
                'output_mode' => 'markdown'
            ],
            'widget' => 'markdown',
            'end_fieldset' => ''
        ],
        'subject' => [
            'title'    => 'subject',
            'type'     => 'text',
            'widget'   => 'text',
            'storage'  => 'parameter',
            'required' => true,
            'start_fieldset' => [
                'title' => 'email message',
                'css_group' => 'area meta',
            ],
        ],
        'from' => [
            'title'    => 'from address',
            'type'     => 'text',
            'widget'   => 'text',
            'storage'  => 'parameter',
            'required' => true,
        ],
        'reply-to' => [
            'title'    => 'reply to address',
            'type'     => 'text',
            'widget'   => 'text',
            'storage'  => 'parameter',
            'validation' => 'email'
        ],
        'content' => [
            'title'    => 'content',
            'type'     => 'text',
            'type_config' => [
                'output_mode' => 'html'
            ],
            'widget'   => 'tinymce',
            'storage'  => 'parameter',
            'required' => true,
            'end_fieldset' => ''
        ],
    ],
    'customdata'  => [
        'org_openpsa_directmarketing_messagetype' => org_openpsa_directmarketing_campaign_message_dba::EMAIL_HTML,
    ],
],
/* TODO: hide newsletter if midcom_helper_find_node_by_component(net.nehmer.blog) returns no results */
'htmlnewsletter' => [
    'description' => 'HTML newsletter email',
    'fields'      => [
        'substyle' => [
            'title' => 'message formatting',
            'storage' => 'parameter',
            'type' => 'select',
            'type_config' => [
                'options' => [
                    'builtin:newsletter' => 'newsletter',
                ],
            ],
            'widget' => 'select',
            'hidden' => false,
        ],
        'report_segmentation' => [
            'title' => 'report segmentation',
            'storage' => 'parameter',
            'type' => 'select',
            'type_config' => [
                'options' => [
                    'segment' => 'default segmentation',
                ],
            ],
            'widget' => 'select',
            'hidden' => false,
        ],
        'title' => [
            // COMPONENT-REQUIRED
            'title'    => 'title',
            'type'     => 'text',
            'widget'   => 'text',
            'storage'  => 'title',
            'required' => true,
        ],
        'description' => [
            'title' => 'description',
            'storage' => 'description',
            'type' => 'text',
            'type_config' => [
                'output_mode' => 'markdown'
            ],
            'widget' => 'markdown',
        ],
        'subject' => [
            'title'    => 'subject',
            'type'     => 'text',
            'widget'   => 'text',
            'storage'  => 'parameter',
            'required' => true,
        ],
        'from' => [
            'title'    => 'from address',
            'type'     => 'text',
            'widget'   => 'text',
            'storage'  => 'parameter',
            'required' => true,
        ],
        'reply-to' => [
            'title'    => 'reply to address',
            'type'     => 'text',
            'widget'   => 'text',
            'storage'  => 'parameter',
            'validation' => 'email'
        ],
        'content' => [
            'title'    => 'content',
            'type'     => 'text',
            'type_config' => [
                'output_mode' => 'html'
            ],
            'widget'   => 'tinymce',
            'storage'  => 'parameter',
            'required' => true,
        ],
        'newsitems' => [
            'title'       => 'number of news items to load',
            'storage'     => 'parameter',
            'type'        => 'select',
            'type_config' => [
                'options' => [
                    1 => '1 latest item',
                    2 => '2 latest items',
                    3 => '3 latest items',
                    4 => '4 latest items',
                    5 => '5 latest items',
                ],
            ],
            'widget'      => 'select',
            'required' => true,
        ],
    ],
    'customdata'  => [
        'org_openpsa_directmarketing_messagetype' => org_openpsa_directmarketing_campaign_message_dba::EMAIL_HTML,
    ],
],