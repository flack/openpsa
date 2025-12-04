<?php
return [
    'default' => [
        'name' => 'default',
        'description' => 'invoice',
        'fields'      => [
            'date' => [
                'title' => 'invoice date',
                'storage'    => 'date',
                'type'    => 'date',
                'type_config' => [
                    'storage_type' => 'UNIXTIME',
                ],
                'widget'    => 'jsdate',
                'widget_config' => [
                    'show_time' => false
                ],
                'start_fieldset' => [
                    'title' => 'basic information',
                    'css_group' => 'area meta',
                ],
            ],
            'deliverydate' => [
                'title' => 'invoice delivery date',
                'storage'    => 'deliverydate',
                'type'    => 'date',
                'type_config' => [
                    'storage_type' => 'UNIXTIME',
                ],
                'widget'    => 'jsdate',
                'widget_config' => [
                    'show_time' => false
                ],
            ],
            'number' => [
                'title' => 'invoice number',
                'storage'  => 'number',
                'type'  => 'number',
                'widget'  => 'text',
                'required' => true,
            ],
            'owner' => [
                'title' => 'owner',
                'storage'  => 'owner',
                'type'  => 'select',
                'type_config' => [
                     'require_corresponding_option' => false,
                     'options' => [],
                ],
                'widget'    => 'autocomplete',
                'widget_config' => [
                    'clever_class' => 'contact',
                    'id_field'     => 'id',
                    'constraints' => [
                        [
                            'field' => 'username',
                            'op'    => '<>',
                            'value' => '',
                        ],
                    ],
                 ],
            ],
            'customerContact' => [
                'title' => 'customer contact',
                'storage'  => 'customerContact',
                'type'  => 'select',
                'type_config' => [
                     'require_corresponding_option' => false,
                     'options' => [],
                ],
                'widget'    => 'autocomplete',
                'widget_config' => [
                    'clever_class' => 'contact',
                    'id_field'     => 'id',
                    'creation_mode_enabled' => true,
                    'creation_handler' => midcom_connection::get_url('self') . "__mfa/org.openpsa.helpers/chooser/create/org_openpsa_contacts_person_dba/",
                    'creation_default_key' => 'openpsa',
                 ],
            ],
            'customer' => [
                'title' => 'customer',
                'storage'    => 'customer',
                'type'    => 'select',
                'type_config' => [
                     'require_corresponding_option' => true,
                     'options' => [],
                ],
                'widget' => 'select',
            ],
            'description' => [
                'title' => 'description',
                'storage'  => 'description',
                'type'  => 'text',
                'widget' => 'textarea',
                'end_fieldset' => '',
            ],
            'sum' => [
                'title' => 'sum',
                'storage'  => 'sum',
                'type'  => 'number',
                'widget'    => 'text',
                'default'   => 0,
                'start_fieldset' => [
                    'title' => 'invoicing information',
                    'css_group' => 'area meta',
                ],
            ],
            'vat' => [
                'title' => 'vat',
                'storage'  => 'vat',
                'type'  => 'select',
                'type_config' => [
                     'require_corresponding_option' => true,
                     'options' => [],
                ],
                'widget'    => 'select',
            ],
            'sent' => [
                'title' => 'sent',
                'storage'    => 'sent',
                'type'    => 'date',
                'type_config' => [
                    'storage_type' => 'UNIXTIME',
                ],
                'widget'    => 'jsdate',
                'widget_config' => [
                    'show_time' => false
                ],
                'hidden'    => true,
            ],
            'due' => [
                'title' => 'due',
                'storage'    => 'due',
                'type'    => 'date',
                'type_config' => [
                    'storage_type' => 'UNIXTIME',
                ],
                'widget'    => 'jsdate',
                'widget_config' => [
                    'show_time' => false
                ],
                'hidden' => true
            ],
            'paid' => [
                'title' => 'paid date',
                'storage'    => 'paid',
                'type'    => 'date',
                'type_config' => [
                    'storage_type' => 'UNIXTIME',
                ],
                'widget'    => 'jsdate',
                'widget_config' => [
                    'show_time' => false
                ],
                'hidden'    => true,
            ],
            'defaultdate' => [
                'title' => 'default',
                'storage'    => 'defaultdate',
                'type'    => 'date',
                'type_config' => [
                    'storage_type' => 'UNIXTIME',
                ],
                'widget'    => 'jsdate',
                'widget_config' => [
                    'show_time' => false
                ],
            ],
            'pdf_file' => [
                'title' => 'pdf file',
                'type'    => 'blobs',
                'widget' => 'downloads',
                'type_config' => [
                    'sortable' => false,
                    'max_count' => 1,
                ],
                'index_method' => 'attachment',
                'hidden' => true,
            ],
            'pdf_file_reminder' => [
                'title' => 'reminder',
                'type'    => 'blobs',
                'widget' => 'downloads',
                'type_config' => [
                    'sortable' => false,
                    'max_count' => 1,
                ],
                'index_method' => 'attachment',
                'hidden' => true,
            ],
            'files' => [
                'title' => 'Files',
                'type'    => 'blobs',
                'widget' => 'downloads',
                'type_config' => [
                    'sortable' => false,
                ],
                'end_fieldset' => '',
                'index_method' => 'attachment',
            ],
        ],
    ]
];