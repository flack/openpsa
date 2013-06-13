'link' => array
(
    'description' => 'article link',
    'fields' => array
    (
        'article' => array
        (
            'title' => 'article',
            'storage' => 'article',
            'type' => 'select',
            'type_config' => array
            (
                'options' => array(),
                'allow_other' => true,
                'require_corresponding_option' => false,
            ),
            'widget' => 'autocomplete',
            'widget_config' => array
            (
                'clever_class' => 'article',
                'id_field' => 'id',
                'result_headers' => array
                (
                    array
                    (
                        'name' => 'title',
                        'title' => 'title'
                    ),
                    array
                    (
                        'name' => 'metadata.published',
                        'title' => 'date'
                    ),
                    array
                    (
                        'name' => 'name',
                        'title' => 'name'
                    ),
                ),
                'categorize_by_parent_label' => true,
                'constraints' => array
                (
                    array
                    (
                        'field' => 'name',
                        'op' => '<>',
                        'value' => 'index',
                    ),
                    array
                    (
                        'field' => 'topic.component',
                        'op' => '=',
                        'value' => 'net.nehmer.static',
                    ),
                ),
            ),
        ),
        'topic' => array
        (
            'title' => 'topic',
            'storage' => 'topic',
            'type' => 'select',
            'type_config' => array
            (
                'options' => array(),
                'allow_other' => true,
                'require_corresponding_option' => false,
            ),
            'widget' => 'autocomplete',
            'widget_config' => array
            (
                'clever_class' => 'topic',
                'id_field' => 'id',
                'result_headers' => array
                (
                    array
                    (
                        'name' => 'extra',
                        'title' => 'title'
                    ),
                    array
                    (
                        'name' => 'metadata.published',
                        'title' => 'date'
                    ),
                    array
                    (
                        'name' => 'name',
                        'title' => 'name'
                    ),
                ),
                'categorize_by_parent_label' => true,
                'constraints' => array
                (
                    array
                    (
                        'field' => 'component',
                        'op' => '=',
                        'value' => 'net.nehmer.static',
                    ),
                ),
            ),
        ),
    ),
),
