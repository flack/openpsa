<?php
return [
    'default' => [
        'description' => 'invoice data',
        'l10n_db' => 'org.openpsa.invoices',
        'fields'  => [
            'use_contact_address' => [
                'title'    => 'use contact address',
                'storage'  => 'useContactAddress',
                'type'     => 'boolean',
                'widget'   => 'checkbox',
            ],
            'recipient' => [
                'title'    => 'recipient',
                'storage'  => 'recipient',
                'type'     => 'text',
                'widget'   => 'textarea',
                'start_fieldset' => [
                    'title' => 'invoice address',
                    'css_group' => 'invoice_address area meta',
                ],
            ],
            'street' => [
                'title'    => midcom::get()->i18n->get_string('street', 'org.openpsa.contacts'),
                'storage'  => 'street',
                'type'     => 'text',
                'widget'   => 'text',
            ],
            'postcode' => [
                'title'    => midcom::get()->i18n->get_string('postcode', 'org.openpsa.contacts'),
                'storage'  => 'postcode',
                'type'     => 'text',
                'widget'   => 'text',
            ],
            'city' => [
                'title'    => midcom::get()->i18n->get_string('city', 'org.openpsa.contacts'),
                'storage'  => 'city',
                'type'     => 'text',
                'widget'   => 'text',
            ],
            'country' => [
                'title'    => midcom::get()->i18n->get_string('country', 'org.openpsa.contacts'),
                'storage'  => 'country',
                'type'     => 'text',
                'widget'   => 'text',
            ],
            'email' => [
                'title'    => midcom::get()->i18n->get_string('email', 'org.openpsa.contacts'),
                'storage'  => 'email',
                'type'     => 'text',
                'widget'   => 'text',
                'validation' => 'email',
                'end_fieldset' => '',
            ],
            'account_number' => [
                'title'    => 'account number',
                'storage'  => 'accountNumber',
                'type'     => 'text',
                'widget'   => 'text',
                'start_fieldset' => [
                    'title' => 'account data',
                    'css_group' => 'area meta',
                ],
            ],
            'bankName' => [
                'title'    => 'name of bank',
                'storage'  => 'bankName',
                'type'     => 'text',
                'widget'   => 'text',
            ],
            'bank_code' => [
                'title'    => 'bank code',
                'storage'  => 'bankCode',
                'type'     => 'text',
                'widget'   => 'text',
            ],
            'iban' => [
                'title'    => 'IBAN',
                'storage'  => 'iban',
                'type'     => 'text',
                'widget'   => 'text',
            ],
            'bic' => [
                'title'    => 'BIC',
                'storage'  => 'bic',
                'type'     => 'text',
                'widget'   => 'text',
                'end_fieldset' => '',
            ],
            'taxid' => [
                'title'    => 'tax identification number',
                'storage'  => 'taxId',
                'type'     => 'text',
                'widget'   => 'text',
                'start_fieldset' => [
                    'title' => 'billing data',
                    'css_group' => 'area meta',
                ],
            ],
            'vatno' => [
                'title'    => 'vat reg no',
                'storage'  => 'vatNo',
                'type'     => 'text',
                'widget'   => 'text',
            ],
            'due' => [
                'title'    => 'payment target',
                'storage'  => 'due',
                'type'     => 'text',
                'widget'   => 'text',
            ],
            'vat' => [
                'title'    => 'vat',
                'storage'  => 'vat',
                'type'     => 'select',
                'type_config' => [
                    'options' => [],
                ],
                'widget'   => 'select',
            ],

            'sendingoption' => [
                'title' => 'sending option',
                'storage'    => 'sendingoption',
                'type'    => 'select',
                'type_config' => [
                    'options' => [
                        1 => 'send manually',
                        2 => 'send per email',
                    ],
                ],
                'widget'      => 'radiocheckselect',
            ],
            'remarks' => [
                'title'    => 'remarks',
                'storage'  => 'remarks',
                'type'     => 'text',
                'widget'   => 'textarea',
                'end_fieldset' => '',
            ],
        ]
    ]
];
