'default' => array
(
    'description' => 'wiki',
    'fields'      => array
    (
        'title' => Array
        (
            'title' => 'title',
            'storage' => 'title',
            'readonly' => true,
            'type' => 'text',
            'widget' => 'text',
        ),
        'content' => Array
        (
            // COMPONENT-REQUIRED
            'title' => 'content',
            'storage' => 'content',
            'required' => true,
            'type' => 'text',
            'type_config' => Array 
            ( 
                'output_mode' => 'markdown' 
            ),
            'widget' => 'markdown',
            'widget_config' => Array
            (
                'height' => 26,
                'width'  => 80,
            ),
        ),
        'changemessage' => Array
        (
            'title' => 'change message',
            'type' => 'rcsmessage',
            'widget' => 'text',
        ),
        'tags' => Array
        (
            'title' => 'tags',
            'type' => 'tags',
            'widget' => 'text',
        ),
    )
),
'redirect' => array
(
    'description' => 'redirection',
    'fields'      => array
    (
        'title' => Array
        (
            'title' => 'title',
            'storage' => 'title',
            'readonly' => true,
            'type' => 'text',
            'widget' => 'text',
        ),        
        'redirect' => Array
        (
            // COMPONENT-REQUIRED
            'title' => 'redirect page',
            'storage' => 'url',
            'required' => true,
            'type' => 'select',
            'type_config' => array
            (
                 'require_corresponding_option' => false,
                 'options' => array(),
            ),
            'widget' => 'chooser',
            'widget_config' => array
            (
                'class' => 'net_nemein_wiki_wikipage',
                'component' => 'net.nemein.wiki',
                'titlefield' => 'title',
                'id_field' => 'title',
                'searchfields' => array
                (
                    'title',
                    'name',
                ),
                'result_headers' => array
                (
                    array('name' => 'title', 'title' => 'title'),
                ),
                'constraints' => array
                (
                    array
                    (
                        'field' => 'topic.component',
                        'op'    => '=',
                        'value' => 'net.nemein.wiki'
                    ),
                    array
                    (
                        'field' => 'up',
                        'op'    => '=',
                        'value' => 0,
                    ),
                ),
                'orders' => array(),
            ),
        )
    )
),