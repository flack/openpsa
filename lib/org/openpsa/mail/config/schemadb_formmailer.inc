'default' => array
(
    'description' => 'Formmailer',
    'l10n_db' => 'org.openpsa.mail',
    'fields'  => array
    (
        'subject' => array
        (
            'title'    => 'subject',
            'type'     => 'text',
            'widget'   => 'text',
        ),
        'message' => array
        (
            'title'    => 'message',
            'type'     => 'text',
            'widget'   => 'textarea',
            'required' => true
        ),
    )
),
