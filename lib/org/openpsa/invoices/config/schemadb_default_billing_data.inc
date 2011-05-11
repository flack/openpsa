'default' => array
(
    'description' => 'invoice data',
    'l10n_db' => 'org.openpsa.invoices',
    'fields'  => array
    (
        'use_contact_address' => array
        (
            'title'    => 'use contact address',
            'storage'  => 'useContactAddress',
            'type'     => 'boolean',
            'widget'   => 'checkbox',
        ),
        'recipient' => array
        (
            'title'    => 'recipient',
            'storage'  => 'recipient',
            'type'     => 'text',
            'widget'   => 'textarea',
            'start_fieldset' => array
            (
                'title' => 'invoice address',
                'css_group' => 'invoice_adress area meta',
            ),
        ),
        'street' => array
        (
            'title'    => $_MIDCOM->i18n->get_string('street' , 'org.openpsa.contacts'),
            'storage'  => 'street',
            'type'     => 'text',
            'widget'   => 'text',
        ),
        'postcode' => array
        (
            'title'    => $_MIDCOM->i18n->get_string('postcode' , 'org.openpsa.contacts'),
            'storage'  => 'postcode',
            'type'     => 'text',
            'widget'   => 'text',
        ),
        'city' => array
        (
            'title'    => $_MIDCOM->i18n->get_string('city' , 'org.openpsa.contacts'),
            'storage'  => 'city',
            'type'     => 'text',
            'widget'   => 'text',
        ),
        'country' => array
        (
            'title'    => $_MIDCOM->i18n->get_string('country' , 'org.openpsa.contacts'),
            'storage'  => 'country',
            'type'     => 'text',
            'widget'   => 'text',
        ),
        'email' => array
        (
            'title'    => $_MIDCOM->i18n->get_string('email' , 'org.openpsa.contacts'),
            'storage'  => 'email',
            'type'     => 'text',
            'widget'   => 'text',
            'end_fieldset' => '',
        ),
        'account_number' => array
        (
            'title'    => 'account number',
            'storage'  => 'accountNumber',
            'type'     => 'text',
            'widget'   => 'text',
            'start_fieldset' => array
            (
                'title' => 'billing data',
                'css_group' => 'area meta',
            ),
        ),
        'bank_code' => array
        (
            'title'    => 'bank code',
            'storage'  => 'bankCode',
            'type'     => 'text',
            'widget'   => 'text',
        ),
        'iban' => array
        (
            'title'    => 'IBAN',
            'storage'  => 'iban',
            'type'     => 'text',
            'widget'   => 'text',
        ),
        'bic' => array
        (
            'title'    => 'BIC',
            'storage'  => 'bic',
            'type'     => 'text',
            'widget'   => 'text',
        ),
        'bankName' => array
        (
            'title'    => 'name of bank',
            'storage'  => 'bankName',
            'type'     => 'text',
            'widget'   => 'text',
        ),
        'vatno' => array
        (
            'title'    => 'VAT No.',
            'storage'  => 'vatNo',
            'type'     => 'text',
            'widget'   => 'text',
        ),
        'due' => array
        (
            'title'    => 'due',
            'storage'  => 'due',
            'type'     => 'text',
            'widget'   => 'text',
        ),
        'vat' => array
        (
            'title'    => 'vat',
            'storage'  => 'vat',
            'type'     => 'select',
            'type_config' => Array
            (
                'options' => Array(),
            ),
            'widget'   => 'select',
            'end_fieldset' => '',
        ),

        'sendingoption' => array
        (
            'title' => 'sending option',
            'storage'    => 'sendingoption',

            'type'    => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    1 => 'send manually',
                    2 => 'send per email',
                ),
            ),
            'widget'      => 'radiocheckselect',

            'start_fieldset' => array
            (
                'title' => 'invoice options',
                'css_group' => 'invoice_options area meta',
            ),
            'end_fieldset' => '',

        ),
    )
),
