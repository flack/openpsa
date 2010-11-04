'config' => array
(
    'description' => 'default configuration schema',
    'fields' => array
    (
        'smslib_api' => array
        (
            'title' => 'api for sms gateway',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'org.openpsa.directmarketing',
                'name' => 'smslib_api',
            ),
            'type' => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    'clickatell' => 'clickatell (http)',
                    'email2sms' => 'email -> sms',
                    'messto' => 'messto (http)',
                    'tambur' => 'tambur (http)',
                ),
            ),
            'widget' => 'select',
            'start_fieldset' => array
            (
                'title' => 'sms information',
                'css_group' => 'area',
            ),
        ),
        'smslib_client_id' => array
        (
            'title' => 'client/api id for the sms gateway (not used by all apis)',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'org.openpsa.directmarketing',
                'name' => 'smslib_client_id',
            ),
            'type' => 'text',
            'widget' => 'text',
        ),
        'smslib_user' => array
        (
            'title' => 'username for the sms gateway',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'org.openpsa.directmarketing',
                'name' => 'smslib_user',
            ),
            'type' => 'text',
            'widget' => 'text',
        ),
        'smslib_password' => array
        (
            'title' => 'password for the sms gateway',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'org.openpsa.directmarketing',
                'name' => 'smslib_password',
            ),
            'type' => 'text',
            'widget' => 'text',
        ),
        'smslib_uri' => array
        (
            'title' => 'url for a http sms gateway',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'org.openpsa.directmarketing',
                'name' => 'smslib_uri',
            ),
            'type' => 'text',
            'widget' => 'text',
            'start_fieldgroup' => array
            (
                'title' => 'http api information',
                'css_group' => 'area',
            ),
            'end_fieldgroup' => 1,
        ),
        'email2sms_address' => array
        (
            'title' => 'address for an email->sms gateway',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'org.openpsa.directmarketing',
                'name' => 'email2sms_address',
            ),
            'type' => 'text',
            'widget' => 'text',
            'start_fieldgroup' => array
            (
                'title' => 'email->sms api information',
                'css_group' => 'area',
            ),
            'end_fieldgroup' => 2,
        ),
        'token_size' => array
        (
            'title' => 'recipient token length',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'org.openpsa.directmarketing',
                'name' => 'token_size',
            ),
            'type' => 'number',
            'widget' => 'text',
            'default' => 15,
            'start_fieldgroup' => array
            (
                'title' => 'detectors and email configuration',
                'css_group' => 'area',
            ),
        ),
        'mail_send_backend' => array
        (
            'title' => 'backend for sending email (backend specific configurations in org.openpsa.mail)',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'org.openpsa.directmarketing',
                'name' => 'mail_send_backend',
            ),
            'type' => 'select',
            'widget' => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    'try_default' => 'system default',
                    'bouncer' => 'any bounce detector compatible',
                    'mail_smtp' => 'smtp',
                    'mail_sendmail' => 'sendmail',
                    'mail' => 'mail() function (not recommended)',
                ),
            ),
        ),
        'bouncer_address' => array
        (
            'title' => 'bounce detector address (use token to indicate place of the token)',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'org.openpsa.directmarketing',
                'name' => 'bouncer_address',
            ),
            'type' => 'text',
            'widget' => 'text',
            'start_fieldgroup' => array
            (
                'title' => 'bounce detector configuration (note: requires special mail server configuration as well)',
                'css_group' => 'area',
            ),
            'end_fieldgroup' => '',
        ),
        'linkdetector_address' => array
        (
            'title' => 'link detector base address (use token to indicate place of the token and url of link)',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'org.openpsa.directmarketing',
                'name' => 'linkdetector_address',
            ),
            'type' => 'text',
            'widget' => 'text',
            'start_fieldgroup' => array
            (
                'title' => 'link detector configuration',
                'css_group' => 'area',
            ),
            'end_fieldgroup' => 2,
        ),
    ),
),
