'config' => array
(
    'description' => 'Default Configuration Schema', /* This is a topic */
    'fields'      => array
    (
        'allow_anonymous' => array
        (
            'title' => 'allow_anonymous',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'net.nehmer.comments',
                'name' => 'allow_anonymous',
            ),
            'type' => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    '' => 'default setting',
                    '1' => 'yes',
                    '0' => 'no',
                ),
            ),
            'widget' => 'select',
        ),

        'ratings_enable' => array
        (
            'title' => 'ratings_enable',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'net.nehmer.comments',
                'name' => 'ratings_enable',
            ),
            'type' => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    '' => 'default setting',
                    '1' => 'yes',
                    '0' => 'no',
                ),
            ),
            'widget' => 'select',
            'start_fieldset' => array
            (
                'title' => 'ratings',
            )
        ),

        'ratings_cache_to_object' => array
        (
            'title' => 'ratings_cache_to_object',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'net.nehmer.comments',
                'name' => 'ratings_cache_to_object',
            ),
            'type' => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    '' => 'default setting',
                    '1' => 'yes',
                    '0' => 'no',
                ),
            ),
            'widget' => 'select',
        ),

        'ratings_cache_to_object_property' => array
        (
            'title' => 'ratings_cache_to_object_property',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'net.nehmer.comments',
                'name' => 'ratings_cache_to_object_property',
            ),
            'type' => 'text',
            'widget' => 'text',
        ),

        'ratings_cache_total' => array
        (
            'title' => 'ratings_cache_total',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'net.nehmer.comments',
                'name' => 'ratings_cache_total',
            ),
            'type' => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    '' => 'default setting',
                    '1' => 'yes',
                    '0' => 'no',
                ),
            ),
            'widget' => 'select',
            'end_fieldset' => '',
        ),

        'comment_count_cache_to_object' => array
        (
            'title' => 'comment_count_cache_to_object',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'net.nehmer.comments',
                'name' => 'comment_count_cache_to_object',
            ),
            'type' => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    '' => 'default setting',
                    '1' => 'yes',
                    '0' => 'no',
                ),
            ),
            'widget' => 'select',
        ),

        'comment_count_cache_to_object_property' => array
        (
            'title' => 'comment_count_cache_to_object_property',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'net.nehmer.comments',
                'name' => 'comment_count_cache_to_object_property',
            ),
            'type' => 'text',
            'widget' => 'text',
        ),

        'schemadb' => array
        (
            'title' => 'schemadb',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'net.nehmer.comments',
                'name' => 'schemadb',
            ),
            'type' => 'text',
            'widget' => 'text',
        ),
    )
),
