'default' => [
    'description' => 'wiki',
    'fields'      => [
        'title' => [
            'title' => 'title',
            'storage' => 'title',
            'readonly' => true,
            'type' => 'text',
            'widget' => 'text',
        ],
        'content' => [
            // COMPONENT-REQUIRED
            'title' => 'content',
            'storage' => 'content',
            'required' => true,
            'type' => 'text',
            'type_config' => [
                'output_mode' => 'markdown'
            ],
            'widget' => 'markdown',
            'widget_config' => [
                'height' => 26,
                'width'  => 80,
            ],
        ],
        'changemessage' => [
            'title' => 'change message',
            'type' => 'rcsmessage',
            'widget' => 'text',
        ],
        'tags' => [
            'title' => 'tags',
            'type' => 'tags',
            'widget' => 'text',
        ],
	]
],
'redirect' => [
    'description' => 'redirection',
    'fields'      => [
        'title' => [
            'title' => 'title',
            'storage' => 'title',
            'readonly' => true,
            'type' => 'text',
            'widget' => 'text',
        ],
        'redirect' => [
            // COMPONENT-REQUIRED
            'title' => 'redirect page',
            'storage' => 'url',
            'required' => true,
            'type' => 'select',
            'type_config' => [
                 'require_corresponding_option' => false,
                 'options' => [],
            ],
            'widget' => 'autocomplete',
            'widget_config' => [
                'class' => 'net_nemein_wiki_wikipage',
                'titlefield' => 'title',
                'id_field' => 'name',
                'searchfields' => [
                    'title',
                    'name',
                ],
                'constraints' => [
                    [
                        'field' => 'topic.component',
                        'op'    => '=',
                        'value' => 'net.nemein.wiki'
                    ], [
                        'field' => 'up',
                        'op'    => '=',
                        'value' => 0,
                    ],
                ],
                'orders' => [],
            ],
    	]
 	]
],