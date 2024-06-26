<?php
return [
    'default' => [
        'description' => 'image popup schema',
        'l10n_db'     => 'midcom.helper.imagepopup',
        'fields' => [
            'midcom_helper_imagepopup_images' => [
                'title' => 'images',
                'storage' => null,
                'type' => 'images',
                'widget' => 'images',
                'widget_config' => [
                    'set_name_and_title_on_upload' => false
                ],
            ],

            'midcom_helper_imagepopup_files' => [
                'title' => 'files',
                'storage' => null,
                'type' => 'blobs',
                'widget' => 'downloads',
            ]
        ]
    ]
];