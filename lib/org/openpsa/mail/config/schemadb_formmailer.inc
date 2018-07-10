'default' => [
    'description' => 'Formmailer',
    'l10n_db' => 'org.openpsa.mail',
    'fields'  => [
        'subject' => [
            'title'    => 'subject',
            'type'     => 'text',
            'widget'   => 'text',
        ],
        'message' => [
            'title'    => 'message',
            'type'     => 'text',
            'widget'   => 'textarea',
            'required' => true
        ],
    ]
],
