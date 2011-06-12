'default' => array (

    'name'        => 'default',
    'description' => 'notifications',
    'l10n_db'     => 'org.openpsa.notifications',
    'fields'      => array (
        'org_openpsa_calendar_event_update' => array
        (
            'title' => 'org.openpsa.calendar:event_update',
            'type'    => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    '' => 'inherit',
                    'none' => 'none',
                    'email' => 'email',
                ),
            ),
            'storage'    => array
            (
                'location' => 'parameter',
                'domain' => 'org.openpsa.notifications',
                'name'    => 'org.openpsa.calendar:event_update',
            ),

            'widget'      => 'radiocheckselect',
            'start_fieldset' => array
            (
                'title' => 'org.openpsa.calendar',
                'css_group' => 'area',
            ),
        ),

        'org_openpsa_calendar_event_add' => array
        (
            'title' => 'org.openpsa.calendar:event_add',
            'type'    => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    '' => 'inherit',
                    'none' => 'none',
                    'email' => 'email',
                ),
            ),
            'storage'    => array
            (
                'location' => 'parameter',
                'domain' => 'org.openpsa.notifications',
                'name'    => 'org.openpsa.calendar:event_add',
            ),

            'widget'      => 'radiocheckselect',
        ),

        'org_openpsa_calendar_event_remove' => array
        (
            'title' => 'org.openpsa.calendar:event_remove',
            'type'    => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    '' => 'inherit',
                    'none' => 'none',
                    'email' => 'email',
                ),
            ),
            'storage'    => array
            (
                'location' => 'parameter',
                'domain' => 'org.openpsa.notifications',
                'name'    => 'org.openpsa.calendar:event_remove',
            ),

            'widget'      => 'radiocheckselect',
        ),

        'org_openpsa_calendar_event_cancel' => array
        (
            'title' => 'org.openpsa.calendar:event_cancel',
            'type'    => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    '' => 'inherit',
                    'none' => 'none',
                    'email' => 'email',
                ),
            ),
            'storage'    => array
            (
                'location' => 'parameter',
                'domain' => 'org.openpsa.notifications',
                'name'    => 'org.openpsa.calendar:event_cancel',
            ),

            'widget'      => 'radiocheckselect',
            'end_fieldset' => '',
        ),
        'org_openpsa_projects_hour_reports_approved' => array
        (
            'title' => 'org.openpsa.projects:hour_reports_approved',
            'type'    => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    '' => 'inherit',
                    'none' => 'none',
                    'email' => 'email',
                ),
            ),
            'storage'    => array
            (
                'location' => 'parameter',
                'domain' => 'org.openpsa.notifications',
                'name'    => 'org.openpsa.projects:hour_reports_approved',
            ),

            'widget'      => 'radiocheckselect',
            'start_fieldset' => array
            (
                'title' => 'org.openpsa.projects',
                'css_group' => 'area',
            ),
            'end_fieldset' => '',
        ),

        'org_openpsa_sales_new_subscription_cycle' => array
        (
            'title' => 'org.openpsa.sales:new_subscription_cycle',
            'type'    => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    '' => 'inherit',
                    'none' => 'none',
                    'email' => 'email',
                ),
            ),
            'storage'    => array
            (
                'location' => 'parameter',
                'domain' => 'org.openpsa.notifications',
                'name'    => 'org.openpsa.sales:new_subscription_cycle',
            ),

            'widget'      => 'radiocheckselect',
            'start_fieldset' => array
            (
                'title' => 'org.openpsa.sales',
                'css_group' => 'area',
            ),
            'end_fieldset' => '',
        ),

        'net_nemein_wiki_page_updated' => array
        (
            'title' => 'net.nemein.wiki:page_updated',
            'type'    => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    '' => 'inherit',
                    'none' => 'none',
                    'email' => 'email',
                ),
            ),
            'storage'    => array
            (
                'location' => 'parameter',
                'domain' => 'org.openpsa.notifications',
                'name'    => 'net.nemein.wiki:page_updated',
            ),
            'widget'      => 'radiocheckselect',

            'start_fieldset' => array
            (
                'title' => 'net.nemein.wiki',
                'css_group' => 'area',
            ),
            'end_fieldset' => '',
        ),
    )
),