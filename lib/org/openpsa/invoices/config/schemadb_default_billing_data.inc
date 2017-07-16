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
            'title'    => midcom::get()->i18n->get_string('street', 'org.openpsa.contacts'),
            'storage'  => 'street',
            'type'     => 'text',
            'widget'   => 'text',
        ),
        'postcode' => array
        (
            'title'    => midcom::get()->i18n->get_string('postcode', 'org.openpsa.contacts'),
            'storage'  => 'postcode',
            'type'     => 'text',
            'widget'   => 'text',
        ),
        'city' => array
        (
            'title'    => midcom::get()->i18n->get_string('city', 'org.openpsa.contacts'),
            'storage'  => 'city',
            'type'     => 'text',
            'widget'   => 'text',
        ),
        'country' => array
        (
            'title'    => midcom::get()->i18n->get_string('country', 'org.openpsa.contacts'),
            'storage'  => 'country',
            'type'     => 'text',
            'widget'   => 'text',
        ),
        'email' => array
        (
            'title'    => midcom::get()->i18n->get_string('email', 'org.openpsa.contacts'),
            'storage'  => 'email',
            'type'     => 'text',
            'widget'   => 'text',
            'validation' => 'email'
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
                'title' => 'account data',
                'css_group' => 'area meta',
            ),
        ),
        'bankName' => array
        (
            'title'    => 'name of bank',
            'storage'  => 'bankName',
            'type'     => 'text',
            'widget'   => 'text',
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
            'end_fieldset' => '',
        ),
        'taxid' => array
        (
            'title'    => 'tax identification number',
            'storage'  => 'taxId',
            'type'     => 'text',
            'widget'   => 'text',
            'start_fieldset' => array
            (
                'title' => 'billing data',
                'css_group' => 'area meta',
            ),
        ),
        'vatno' => array
        (
            'title'    => 'vat reg no',
            'storage'  => 'vatNo',
            'type'     => 'text',
            'widget'   => 'text',
        ),
        'due' => array
        (
            'title'    => 'payment target',
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
        ),
        'remarks' => array
        (
            'title'    => 'remarks',
            'storage'  => 'remarks',
            'type'     => 'text',
            'widget'   => 'textarea',
            'end_fieldset' => '',
        ),
    )
),
