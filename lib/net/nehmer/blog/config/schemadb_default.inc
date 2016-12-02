'default' => array
(
    'description' => 'article',
    'fields'      => array
    (
        'name' => Array
        (
            // COMPONENT-REQUIRED
            'title'   => 'url name',
            'storage' => 'name',
            'type'    => 'urlname',
            'widget'  => 'text',
            'type_config' => array
            (
                'allow_catenate' => true,
            ),
        ),
        'title' => Array
        (
            // COMPONENT-REQUIRED
            'title' => 'title',
            'storage' => 'title',
            'required' => true,
            'type' => 'text',
            'widget' => 'text',
        ),
        'abstract' => Array
        (
            // COMPONENT-REQUIRED
            'title' => 'abstract',
            'storage' => 'abstract',
            'type' => 'text',
            'widget' => 'textarea',
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
                'output_mode' => 'html'
            ),
            'widget' => 'tinymce',
        ),
        'categories' => Array
        (
            'title' => 'categories',
            'storage' => 'extra1',
            'type' => 'select',
            'widget' => 'select',
            'type_config' => Array
            (
                'options'        => Array(),
                'allow_other'    => true,
                'allow_multiple' => true,
                'multiple_storagemode' => 'imploded_wrapped',
            ),
            'widget_config' => Array
            (
                'height' => count(explode(',', midcom_baseclasses_components_configuration::get('net.nehmer.blog', 'config')->get('categories'))),
            ),
        ),
        'related' => array
        (
            'title' => 'related stories',
            'storage' => 'extra3',
            'type' => 'select',
            'type_config' => array
            (
                 'require_corresponding_option' => false,
                 'allow_multiple' => true,
                 'options' => array(),
                 'multiple_storagemode' => 'imploded_wrapped',
            ),
            'widget' => 'autocomplete',
            'widget_config' => array
            (
                'clever_class' => 'article',
            ),
        ),
        'image' => Array
        (
            'title' => 'image',
            'storage' => null,
            'type' => 'image',
            'type_config' => Array
            (
                'filter_chain' => 'resize(800,600)',
                'auto_thumbnail' => Array(200,200),
            ),
            'widget' => 'image',
            'hidden' => true,
        ),
    ),
),
