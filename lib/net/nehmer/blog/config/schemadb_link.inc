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
            ),
        ),
    ),
),
