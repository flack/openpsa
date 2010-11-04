// This is for a midcom_db_person object
'default' => array 
(
    'description' => 'person',
    'l10n_db' => 'midcom.admin.user',
    'fields' => array 
    (
        'username' => array 
        (
            // COMPONENT-REQUIRED
            'title' => 'username',
            'storage' => 'username',
            'type' => 'text',
            'widget' => 'text',
            'index_method' => 'noindex',
        ),
        'password' => array 
        (
            // COMPONENT-REQUIRED
            'title' => 'password',
            'storage' => 'password',
            'type' => 'password',
            'widget' => 'password',
            'index_method' => 'noindex',
        ),
    )
)