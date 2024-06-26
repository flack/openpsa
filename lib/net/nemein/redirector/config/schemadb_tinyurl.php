<?php
use Symfony\Component\HttpFoundation\Response;

return [
    'tinyurl' => [
        'description' => 'tinyurl',
        'fields' => [
            'title' => [
                'title' => 'title',
                'storage' => 'title',
                'widget' => 'text',
                'type' => 'text',
                'required' => true,
            ],
            'name' => [
                'title' => 'url name',
                'storage' => 'name',
                'widget' => 'text',
                'type' => 'text',
                'default' => net_nemein_redirector_tinyurl_dba::generate(),
                'required' => true,
            ],
            'redirection_code' => [
                'title' => 'redirection http code',
                'storage' => 'code',
                'type' => 'select',
                'type_config' => [
                    'options' => [
                        Response::HTTP_MOVED_PERMANENTLY => Response::$statusTexts[Response::HTTP_MOVED_PERMANENTLY],
                        Response::HTTP_FOUND => Response::$statusTexts[Response::HTTP_FOUND],
                        Response::HTTP_GONE => Response::$statusTexts[Response::HTTP_GONE],
                    ],
                ],
                'widget' => 'select',
                'default' => Response::HTTP_MOVED_PERMANENTLY,
            ],
            'url' => [
                'title' => 'url',
                'storage' => 'url',
                'type' => 'text',
                'widget' => 'text',
                'required' => true,
            ],
            'description' => [
                'title' => 'description',
                'storage' => 'description',
                'type' => 'text',
                'type_config' => [
                    'output_mode' => 'html',
                ],
                'widget' => 'textarea',
            ],
        ],
    ]
];