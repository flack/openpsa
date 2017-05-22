'textemail' => array (
    'description' => 'text email',
    'fields'      => array
    (
        'title' => array
        (
            // COMPONENT-REQUIRED
            'title'    => 'title',
            'type'     => 'text',
            'widget'   => 'text',
            'storage'  => 'title',
            'required' => true,
            'start_fieldset' => array
            (
                'title' => 'description',
                'css_group' => 'area meta',
            ),
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
            'end_fieldset' => ''
        ),
        'subject' => array
        (
            'title'    => 'subject',
            'type'     => 'text',
            'widget'   => 'text',
            'storage'  => 'parameter',
            'required' => true,
            'start_fieldset' => array
            (
                'title' => 'email message',
                'css_group' => 'area meta',
            ),
        ),
        'from' => array
        (
            'title'    => 'from address',
            'type'     => 'text',
            'widget'   => 'text',
            'storage'  => 'parameter',
            'required' => true,
        ),
        'reply-to' => array
        (
            'title'    => 'reply to address',
            'type'     => 'text',
            'widget'   => 'text',
            'storage'  => 'parameter',
        ),
        'content' => array
        (
            'title'    => 'content',
            'type'     => 'text',
            'type_config' => array
            (
                'output_mode' => 'pre'
            ),
            'widget'   => 'textarea',
            'storage'  => 'parameter',
            'required' => true,
        ),
        'attachments' => array
        (
            'title'    => 'files to attach',
            'type'     => 'blobs',
            'widget'   => 'downloads',
            'end_fieldset' => ''
        ),
    ),
    'customdata'  => array
    (
        'org_openpsa_directmarketing_messagetype' => org_openpsa_directmarketing_campaign_message_dba::EMAIL_TEXT,
    ),
),
'htmlemail' => array (
    'description' => 'html email',
    'fields'      => array
    (
        'title' => array
        (
            // COMPONENT-REQUIRED
            'title'    => 'title',
            'type'     => 'text',
            'widget'   => 'text',
            'storage'  => 'title',
            'required' => true,
            'start_fieldset' => array
            (
                'title' => 'description',
                'css_group' => 'area meta',
            ),
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
            'end_fieldset' => ''
        ),
        'subject' => array
        (
            'title'    => 'subject',
            'type'     => 'text',
            'widget'   => 'text',
            'storage'  => 'parameter',
            'required' => true,
            'start_fieldset' => array
            (
                'title' => 'email message',
                'css_group' => 'area meta',
            ),
        ),
        'from' => array
        (
            'title'    => 'from address',
            'type'     => 'text',
            'widget'   => 'text',
            'storage'  => 'parameter',
            'required' => true,
        ),
        'reply-to' => array
        (
            'title'    => 'reply to address',
            'type'     => 'text',
            'widget'   => 'text',
            'storage'  => 'parameter',
        ),
        'content' => array
        (
            'title'    => 'content',
            'type'     => 'text',
            'type_config' => array
            (
                'output_mode' => 'html'
            ),
            'widget'   => 'tinymce',
            'storage'  => 'parameter',
            'required' => true,
            'end_fieldset' => ''
        ),
    ),
    'customdata'  => array
    (
        'org_openpsa_directmarketing_messagetype' => org_openpsa_directmarketing_campaign_message_dba::EMAIL_HTML,
    ),
),
/* TODO: hide newsletter if midcom_helper_find_node_by_component(net.nehmer.blog) returns no results */
'htmlnewsletter' => array (
    'description' => 'HTML newsletter email',
    'fields'      => array
    (
        'substyle' => array (
            'title' => 'message formatting',
            'storage' => 'parameter',
            'type' => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    'builtin:newsletter' => 'newsletter',
                ),
            ),
            'widget' => 'select',
            'hidden' => false,
        ),
        'report_segmentation' => array (
            'title' => 'report segmentation',
            'storage' => 'parameter',
            'type' => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    'segment' => 'default segmentation',
                ),
            ),
            'widget' => 'select',
            'hidden' => false,
        ),
        'title' => array
        (
            // COMPONENT-REQUIRED
            'title'    => 'title',
            'type'     => 'text',
            'widget'   => 'text',
            'storage'  => 'title',
            'required' => true,
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
        'subject' => array
        (
            'title'    => 'subject',
            'type'     => 'text',
            'widget'   => 'text',
            'storage'  => 'parameter',
            'required' => true,
        ),
        'from' => array
        (
            'title'    => 'from address',
            'type'     => 'text',
            'widget'   => 'text',
            'storage'  => 'parameter',
            'required' => true,
        ),
        'reply-to' => array
        (
            'title'    => 'reply to address',
            'type'     => 'text',
            'widget'   => 'text',
            'storage'  => 'parameter',
        ),
        'content' => array
        (
            'title'    => 'content',
            'type'     => 'text',
            'type_config' => array
            (
                'output_mode' => 'html'
            ),
            'widget'   => 'tinymce',
            'storage'  => 'parameter',
            'required' => true,
        ),
        'newsitems' => array
        (
            'title'       => 'number of news items to load',
            'storage'     => 'parameter',
            'type'        => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    1 => '1 latest item',
                    2 => '2 latest items',
                    3 => '3 latest items',
                    4 => '4 latest items',
                    5 => '5 latest items',
                ),
            ),
            'widget'      => 'select',
            'required' => true,
        ),
    ),
    'customdata'  => array
    (
        'org_openpsa_directmarketing_messagetype' => org_openpsa_directmarketing_campaign_message_dba::EMAIL_HTML,
    ),
),