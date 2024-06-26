<?php
return [
    'default' => [
        'description' => 'article',
        'fields'      => [
            'name' => [
                // COMPONENT-REQUIRED
                'title'   => 'url name',
                'storage' => 'name',
                'type'    => 'urlname',
                'widget'  => 'text',
                'type_config' => [
                    'allow_catenate' => true,
                ],
            ],
            'title' => [
                // COMPONENT-REQUIRED
                'title' => 'title',
                'storage' => 'title',
                'required' => true,
                'type' => 'text',
                'widget' => 'text',
            ],
            'abstract' => [
                // COMPONENT-REQUIRED
                'title' => 'abstract',
                'storage' => 'abstract',
                'type' => 'text',
                'widget' => 'textarea',
            ],
            'content' => [
                // COMPONENT-REQUIRED
                'title' => 'content',
                'storage' => 'content',
                'required' => true,
                'type' => 'text',
                'type_config' => [
                    'output_mode' => 'html'
                ],
                'widget' => 'tinymce',
            ],
            'categories' => [
                'title' => 'categories',
                'storage' => 'extra1',
                'type' => 'select',
                'widget' => 'select',
                'type_config' => [
                    'options'        => [],
                    'allow_other'    => true,
                    'allow_multiple' => true,
                    'multiple_storagemode' => 'imploded_wrapped',
                ],
                'widget_config' => [
                    'height' => count(explode(',', midcom_baseclasses_components_configuration::get('net.nehmer.blog', 'config')->get('categories'))),
                ],
            ],
            'related' => [
                'title' => 'related stories',
                'storage' => 'extra3',
                'type' => 'select',
                'type_config' => [
                     'require_corresponding_option' => false,
                     'allow_multiple' => true,
                     'options' => [],
                     'multiple_storagemode' => 'imploded_wrapped',
                ],
                'widget' => 'autocomplete',
                'widget_config' => [
                    'clever_class' => 'article',
                ],
            ],
            'image' => [
                'title' => 'image',
                'storage' => null,
                'type' => 'image',
                'type_config' => [
                    'filter_chain' => 'resize(800,600)',
                    'auto_thumbnail' => [200,200],
                ],
                'widget' => 'image',
                'hidden' => true,
            ],
        ],
    ]
];
