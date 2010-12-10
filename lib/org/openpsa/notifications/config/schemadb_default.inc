'default' => array (

    'name'        => 'default',
    'description' => 'notifications',
    'l10n_db'     => 'org.openpsa.notifications',
    'fields'      => array (
        'org_openpsa_calendar_event_update' => array
        (
            'description' => 'org.openpsa.calendar:event_update',
            'datatype'    => 'text',
            'location'    => 'config',
            'config_domain' => 'org.openpsa.notifications',
            'config_key'    => 'org.openpsa.calendar:event_update',
            'widget'      => 'select',
            'widget_select_choices' => array
            (
                '' => 'inherit',
                'none' => 'none',
                'email' => 'email',
                'nabaztag' => 'nabaztag',
            ),
            'start_fieldset' => array
            (
                'title' => 'org.openpsa.calendar',
                'css_group' => 'area',
            ),
        ),
        'org_openpsa_calendar_event_add' => array (
            'description' => 'org.openpsa.calendar:event_add',
            'datatype'    => 'text',
            'location'    => 'config',
            'config_domain' => 'org.openpsa.notifications',
            'config_key'    => 'org.openpsa.calendar:event_add',
            'widget'      => 'select',
            'widget_select_choices' => array(
                '' => 'inherit',
                'none' => 'none',
                'email' => 'email',
                'nabaztag' => 'nabaztag',
            ),
        ),
        'org_openpsa_calendar_event_remove' => array (
            'description' => 'org.openpsa.calendar:event_remove',
            'datatype'    => 'text',
            'location'    => 'config',
            'config_domain' => 'org.openpsa.notifications',
            'config_key'    => 'org.openpsa.calendar:event_remove',
            'widget'      => 'select',
            'widget_select_choices' => array(
                '' => 'inherit',
                'none' => 'none',
                'email' => 'email',
                'nabaztag' => 'nabaztag',
            ),
        ),
        'org_openpsa_calendar_event_cancel' => array (
            'description' => 'org.openpsa.calendar:event_cancel',
            'datatype'    => 'text',
            'location'    => 'config',
            'config_domain' => 'org.openpsa.notifications',
            'config_key'    => 'org.openpsa.calendar:event_cancel',
            'widget'      => 'select',
            'widget_select_choices' => array(
                '' => 'inherit',
                'none' => 'none',
                'email' => 'email',
                'nabaztag' => 'nabaztag',
            ),
            'end_fieldset' => '',
        ),
        'org_openpsa_projects_hour_reports_approved' => array
        (
            'description' => 'org.openpsa.projects:hour_reports_approved',
            'datatype'    => 'text',
            'location'    => 'config',
            'config_domain' => 'org.openpsa.notifications',
            'config_key'    => 'org.openpsa.projects:hour_reports_approved',
            'widget'      => 'select',
            'widget_select_choices' => array
            (
                '' => 'inherit',
                'none' => 'none',
                'email' => 'email',
                'nabaztag' => 'nabaztag',
            ),
            'start_fieldset' => array
            (
                'title' => 'org.openpsa.projects',
                'css_group' => 'area',
            ),
            'end_fieldset' => '',
        ),
        'org_openpsa_sales_new_subscription_cycle' => array
        (
            'description' => 'org.openpsa.sales:new_subscription_cycle',
            'datatype'    => 'text',
            'location'    => 'config',
            'config_domain' => 'org.openpsa.notifications',
            'config_key'    => 'org.openpsa.sales:new_subscription_cycle',
            'widget'      => 'select',
            'widget_select_choices' => array
            (
                '' => 'inherit',
                'none' => 'none',
                'email' => 'email',
                'nabaztag' => 'nabaztag',
            ),
            'start_fieldset' => array
            (
                'title' => 'org.openpsa.sales',
                'css_group' => 'area',
            ),
            'end_fieldset' => '',
        ),
        'net_nemein_approvenotifier_expires_today' => array
        (
            'description' => 'net.nemein.approvenotifier:expires_today',
            'datatype'    => 'text',
            'location'    => 'config',
            'config_domain' => 'org.openpsa.notifications',
            'config_key'    => 'net.nemein.approvenotifier:expires_today',
            'widget'      => 'select',
            'widget_select_choices' => array
            (
                '' => 'inherit',
                'none' => 'none',
                'email' => 'email',
                'nabaztag' => 'nabaztag',
            ),
            'start_fieldset' => array
            (
                'title' => 'net.nemein.approvenotifier',
                'css_group' => 'area',
            ),
        ),
        'net_nemein_approvenotifier_expire_advance_notification' => array
        (
            'description' => 'net.nemein.approvenotifier:expire_advance_notification',
            'datatype'    => 'text',
            'location'    => 'config',
            'config_domain' => 'org.openpsa.notifications',
            'config_key'    => 'net.nemein.approvenotifier:expire_advance_notification',
            'widget'      => 'select',
            'widget_select_choices' => array
            (
                '' => 'inherit',
                'none' => 'none',
                'email' => 'email',
                'nabaztag' => 'nabaztag',
            ),
            'end_fieldset' => '',
        ),
        'net_nemein_wiki_page_updated' => array
        (
            'description' => 'net.nemein.wiki:page_updated',
            'datatype'    => 'text',
            'location'    => 'config',
            'config_domain' => 'org.openpsa.notifications',
            'config_key'    => 'net.nemein.wiki:page_updated',
            'widget'      => 'select',
            'widget_select_choices' => array
            (
                '' => 'inherit',
                'none' => 'none',
                'email' => 'email',
                'nabaztag' => 'nabaztag',
            ),
            'start_fieldset' => array
            (
                'title' => 'net.nemein.wiki',
                'css_group' => 'area',
            ),
            'end_fieldset' => '',
        ),
    )
),