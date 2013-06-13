'link' => array
(
    'description' => 'topic link',
    'fields' => array
    (
        'topic' => array
        (
            'title' => 'topic',
            'storage' => 'symlink',
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
            'required' => true,
        ),
    ),
),
