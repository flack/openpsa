'default' => [

    'name'        => 'default',
    'description' => 'notifications',
    'l10n_db'     => 'org.openpsa.notifications',
    'fields'      => [
        'org_openpsa_calendar_event_update' => [
            'title' => 'org.openpsa.calendar:event_update',
            'type'    => 'select',
            'type_config' => [
                'options' => [
                    '' => 'inherit',
                    'none' => 'none',
                    'email' => 'email',
                ],
            ],
            'storage'    => [
                'location' => 'parameter',
                'domain' => 'org.openpsa.notifications',
                'name'    => 'org.openpsa.calendar:event_update',
            ],

            'widget'      => 'radiocheckselect',
            'start_fieldset' => [
                'title' => 'org.openpsa.calendar',
                'css_group' => 'area',
            ],
        ],

        'org_openpsa_calendar_event_add' => [
            'title' => 'org.openpsa.calendar:event_add',
            'type'    => 'select',
            'type_config' => [
                'options' => [
                    '' => 'inherit',
                    'none' => 'none',
                    'email' => 'email',
                ],
            ],
            'storage'    => [
                'location' => 'parameter',
                'domain' => 'org.openpsa.notifications',
                'name'    => 'org.openpsa.calendar:event_add',
            ],

            'widget'      => 'radiocheckselect',
        ],

        'org_openpsa_calendar_event_remove' => [
            'title' => 'org.openpsa.calendar:event_remove',
            'type'    => 'select',
            'type_config' => [
                'options' => [
                    '' => 'inherit',
                    'none' => 'none',
                    'email' => 'email',
                ],
            ],
            'storage'    => [
                'location' => 'parameter',
                'domain' => 'org.openpsa.notifications',
                'name'    => 'org.openpsa.calendar:event_remove',
            ],

            'widget'      => 'radiocheckselect',
        ],

        'org_openpsa_calendar_event_cancel' => [
            'title' => 'org.openpsa.calendar:event_cancel',
            'type'    => 'select',
            'type_config' => [
                'options' => [
                    '' => 'inherit',
                    'none' => 'none',
                    'email' => 'email',
                ],
            ],
            'storage'    => [
                'location' => 'parameter',
                'domain' => 'org.openpsa.notifications',
                'name'    => 'org.openpsa.calendar:event_cancel',
            ],

            'widget'      => 'radiocheckselect',
            'end_fieldset' => '',
        ],
        'org_openpsa_projects_hour_reports_approved' => [
            'title' => 'org.openpsa.projects:hour_reports_approved',
            'type'    => 'select',
            'type_config' => [
                'options' => [
                    '' => 'inherit',
                    'none' => 'none',
                    'email' => 'email',
                ],
            ],
            'storage'    => [
                'location' => 'parameter',
                'domain' => 'org.openpsa.notifications',
                'name'    => 'org.openpsa.projects:hour_reports_approved',
            ],

            'widget'      => 'radiocheckselect',
            'start_fieldset' => [
                'title' => 'org.openpsa.projects',
                'css_group' => 'area',
            ],
            'end_fieldset' => '',
        ],

        'org_openpsa_sales_new_subscription_cycle' => [
            'title' => 'org.openpsa.sales:new_subscription_cycle',
            'type'    => 'select',
            'type_config' => [
                'options' => [
                    '' => 'inherit',
                    'none' => 'none',
                    'email' => 'email',
                ],
            ],
            'storage'    => [
                'location' => 'parameter',
                'domain' => 'org.openpsa.notifications',
                'name'    => 'org.openpsa.sales:new_subscription_cycle',
            ],

            'widget'      => 'radiocheckselect',
            'start_fieldset' => [
                'title' => 'org.openpsa.sales',
                'css_group' => 'area',
            ],
            'end_fieldset' => '',
        ],

        'net_nemein_wiki_page_updated' => [
            'title' => 'net.nemein.wiki:page_updated',
            'type'    => 'select',
            'type_config' => [
                'options' => [
                    '' => 'inherit',
                    'none' => 'none',
                    'email' => 'email',
                ],
            ],
            'storage'    => [
                'location' => 'parameter',
                'domain' => 'org.openpsa.notifications',
                'name'    => 'net.nemein.wiki:page_updated',
            ],
            'widget'      => 'radiocheckselect',

            'start_fieldset' => [
                'title' => 'net.nemein.wiki',
                'css_group' => 'area',
            ],
            'end_fieldset' => '',
        ],
    ]
],