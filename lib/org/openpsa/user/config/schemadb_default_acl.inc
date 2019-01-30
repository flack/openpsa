 'default' => [
    'name'        => 'default',
    'description' => 'Permissions',
    'fields'      => [
        'centralized_toolbar' => [
            'title'       => 'enable centralized toolbar',
            'type'        => 'privilege',
            'type_config' => [
                'privilege_name' => 'midcom:centralized_toolbar',
                'assignee'       => 'SELF',
                'classname'      => 'midcom_services_toolbars',
            ],
            'widget'      => 'privilege',
            'storage'     => null,
            'start_fieldset' => [
                'title' => midcom::get()->i18n->get_string('midcom'),
                'css_group' => 'area',
            ],
        ],
        'ajax_toolbar' => [
            'title'       => 'enable ajax in toolbar',
            'type'        => 'privilege',
            'type_config' => [
                'privilege_name' => 'midcom:ajax',
                'assignee'       => 'SELF',
                'classname'      => 'midcom_services_toolbars',
            ],
            'widget'      => 'privilege',
            'storage'     => null,
        ],
        'ajax_uimessages' => [
            'title'       => 'enable ajax in uimessages',
            'type'        => 'privilege',
            'type_config' => [
                'privilege_name' => 'midcom:ajax',
                'assignee'       => 'SELF',
                'classname'      => get_class(midcom::get()->uimessages),
            ],
            'widget'      => 'privilege',
            'storage'     => null,
        ],
        'asgard_access' => [
            'title'       => 'enable asgard',
            'type'        => 'privilege',
            'type_config' => [
                'privilege_name' => 'midgard.admin.asgard:access',
                'assignee'       => 'SELF',
                'classname'      => 'midgard_admin_asgard_plugin',
            ],
            'widget'      => 'privilege',
            'storage'     => null,
        ],
        'midcom_unlock' => [
            'title'       => 'enable unlocking locked objects',
            'type'        => 'privilege',
            'type_config' => [
                'privilege_name' => 'midcom:unlock',
                'assignee'       => 'SELF',
                'classname'      => 'midcom_services_auth',
            ],
            'widget'      => 'privilege',
            'storage'     => null,
            'end_fieldset' => '',
        ],

        'calendar' => [
            'title'       => 'enable calendar',
            'type'        => 'privilege',
            'type_config' => [
                'privilege_name' => 'midgard:create',
                /* Set the calendar root event and the assignee prior to initializing DM */
            ],
            'widget'      => 'privilege',
            'storage'     => null,
            'start_fieldset' => [
                'title' => midcom::get()->i18n->get_string('org.openpsa.calendar', 'org.openpsa.calendar'),
                'css_group' => 'area',
            ],
            'end_fieldset' => '',
            'hidden' => !org_openpsa_core_siteconfig::get_instance()->node_exists('org.openpsa.calendar'),
        ],

        'projects' => [
            'title' => 'enable project creation',
            'type'    => 'privilege',
            'type_config' => [
                'privilege_name' => 'midgard:create',
                'assignee'       => 'SELF',
                'classname'      => 'org_openpsa_projects_project',
            ],

            'widget'      => 'privilege',
            'start_fieldset' => [
                'title' => midcom::get()->i18n->get_string('org.openpsa.projects', 'org.openpsa.projects'),
                'css_group' => 'area',
            ],
            'hidden' => !org_openpsa_core_siteconfig::get_instance()->node_exists('org.openpsa.projects'),
        ],
        'tasks_creation' => [
            'title' => 'enable task creation',
            'type'    => 'privilege',
            'type_config' => [
                'privilege_name' => 'midgard:create',
                'assignee'       => 'SELF',
                'classname'      => 'org_openpsa_projects_task_dba',
            ],

            'widget'      => 'privilege',
            'hidden' => !org_openpsa_core_siteconfig::get_instance()->node_exists('org.openpsa.projects'),
        ],
        'projects_tasks' => [
            'title' => 'enable task view',
            'type'    => 'privilege',
            'type_config' => [
                'privilege_name' => 'midgard:read',
                'assignee'       => 'SELF',
                'classname'      => 'org_openpsa_projects_task_dba',
            ],

            'widget'      => 'privilege',
            'end_fieldset' => '',
            'hidden' => !org_openpsa_core_siteconfig::get_instance()->node_exists('org.openpsa.projects'),
        ],

        'contact_creation' => [
            'title' => 'enable contact creation',
            'type'    => 'privilege',
            'type_config' => [
                'privilege_name' => 'midgard:create',
                'assignee'       => 'SELF',
                'classname'      => 'org_openpsa_contacts_person_dba',
                /* Set the contacts root group and assignee prior to initializing */
            ],

            'widget'      => 'privilege',
            'start_fieldset' => [
                'title' => midcom::get()->i18n->get_string('org.openpsa.contacts', 'org.openpsa.contacts'),
                'css_group' => 'area',
            ],
        ],

        'contact_editing' => [
            'title' => 'enable editing contacts created by others',
            'type'    => 'privilege',
            'type_config' => [
                'privilege_name' => 'midgard:update',
                'assignee'       => 'SELF',
                'classname'      => 'org_openpsa_contacts_person_dba',
                /* Set the contacts root group and assignee prior to initializing */
            ],

            'widget'      => 'privilege',
        ],

        'organization_creation' => [
            'title' => 'enable organization creation',
            'type'    => 'privilege',
            'type_config' => [
                'privilege_name' => 'midgard:create',
                'assignee'       => 'SELF',
                'classname'      => 'org_openpsa_contacts_group_dba',
            ],

            'widget'      => 'privilege',
        ],

        'organization_editing' => [
            'title' => 'enable editing organizations created by others',
            'type'    => 'privilege',
            'type_config' => [
                'privilege_name' => 'midgard:update',
                'assignee'       => 'SELF',
                'classname'      => 'org_openpsa_contacts_group_dba',
            ],

            'widget'      => 'privilege',
            'end_fieldset' => '',
        ],
        'usermanagement_access' => [
            'title'       => 'enable user management access',
            'type'        => 'privilege',
            'type_config' => [
                'privilege_name' => 'org.openpsa.user:access',
                'assignee'       => 'SELF',
                'classname'      => 'org_openpsa_user_interface',
            ],
            'widget'      => 'privilege',
            'storage'     => null,
            'start_fieldset' => [
                'title' => 'org.openpsa.user',
                'css_group' => 'area',
            ],
        ],
        'usermanagement_manage' => [
            'title'       => 'enable user management',
            'type'        => 'privilege',
            'type_config' => [
                'privilege_name' => 'org.openpsa.user:manage',
                'assignee'       => 'SELF',
                'classname'      => 'org_openpsa_user_interface',
            ],
            'widget'      => 'privilege',
            'storage'     => null,
            'end_fieldset' => '',
        ],
        'invoices_creation' => [
            'title' => 'enable invoice creation',
            'type'    => 'privilege',
            'type_config' => [
                'privilege_name' => 'midgard:create',
                'assignee'       => 'SELF',
                'classname'      => 'org_openpsa_invoices_invoice_dba',
            ],

            'widget'      => 'privilege',
            'start_fieldset' => [
                'title' => midcom::get()->i18n->get_string('org.openpsa.invoices', 'org.openpsa.invoices'),
                'css_group' => 'area',
            ],
            'hidden' => !org_openpsa_core_siteconfig::get_instance()->node_exists('org.openpsa.invoices'),
        ],

        'invoices_editing' => [
            'title' => 'enable editing invoices created by others',
            'type'    => 'privilege',
            'type_config' => [
                'privilege_name' => 'midgard:update',
                'assignee'       => 'SELF',
                'classname'      => 'org_openpsa_invoices_invoice_dba',
            ],

            'widget'      => 'privilege',
            'end_fieldset' => '',
            'hidden' => !org_openpsa_core_siteconfig::get_instance()->node_exists('org.openpsa.invoices'),
        ],

        'wiki_creation' => [
            'title' => 'enable wikipage creation',
            'type'    => 'privilege',
            'type_config' => [
                'privilege_name' => 'midgard:create',
                'assignee'       => 'SELF',
                'classname'      => 'net_nemein_wiki_wikipage',
            ],

            'widget'      => 'privilege',
            'start_fieldset' => [
                'title' => midcom::get()->i18n->get_string('net.nemein.wiki', 'net.nemein.wiki'),
                'css_group' => 'area',
            ],
            'hidden' => !org_openpsa_core_siteconfig::get_instance()->node_exists('net.nemein.wiki'),
        ],

        'wiki_editing' => [
            'title' => 'enable editing wikipages created by others',
            'type'    => 'privilege',
            'type_config' => [
                'privilege_name' => 'midgard:update',
                'assignee'       => 'SELF',
                'classname'      => 'net_nemein_wiki_wikipage',
            ],

            'widget'      => 'privilege',
            'end_fieldset' => '',
            'hidden' => !org_openpsa_core_siteconfig::get_instance()->node_exists('net.nemein.wiki'),
        ],

        'products_creation' => [
            'title' => 'enable product creation',
            'type'    => 'privilege',
            'type_config' => [
                'privilege_name' => 'midgard:create',
                'assignee'       => 'SELF',
                'classname'      => 'org_openpsa_products_product_dba',
            ],

            'widget'      => 'privilege',
            'start_fieldset' => [
                'title' => midcom::get()->i18n->get_string('org.openpsa.products', 'org.openpsa.products'),
                'css_group' => 'area',
            ],
            'hidden' => !org_openpsa_core_siteconfig::get_instance()->node_exists('org.openpsa.products'),
        ],

        'products_editing' => [
            'title' => 'enable editing products created by others',
            'type'    => 'privilege',
            'type_config' => [
                'privilege_name' => 'midgard:update',
                'assignee'       => 'SELF',
                'classname'      => 'org_openpsa_products_product_dba',
            ],

            'widget'      => 'privilege',
            'end_fieldset' => '',
            'hidden' => !org_openpsa_core_siteconfig::get_instance()->node_exists('org.openpsa.products'),
        ],

        'campaigns_creation' => [
            'title' => 'enable campaign creation',
            'type'    => 'privilege',
            'type_config' => [
                'privilege_name' => 'midgard:create',
                'assignee'       => 'SELF',
                'classname'      => 'org_openpsa_directmarketing_campaign_dba',
            ],

            'widget'      => 'privilege',
            'start_fieldset' => [
                'title' => midcom::get()->i18n->get_string('org.openpsa.directmarketing', 'org.openpsa.directmarketing'),
                'css_group' => 'area',
            ],
            'hidden' => !org_openpsa_core_siteconfig::get_instance()->node_exists('org.openpsa.directmarketing'),
        ],

        'campaigns_editing' => [
            'title' => 'enable editing campaigns created by others',
            'type'    => 'privilege',
            'type_config' => [
                'privilege_name' => 'midgard:update',
                'assignee'       => 'SELF',
                'classname'      => 'org_openpsa_directmarketing_campaign_dba',
            ],

            'widget'      => 'privilege',
            'end_fieldset' => '',
            'hidden' => !org_openpsa_core_siteconfig::get_instance()->node_exists('org.openpsa.directmarketing'),
        ],

        'salesproject_creation' => [
            'title' => 'enable salesproject creation',
            'type'    => 'privilege',
            'type_config' => [
                'privilege_name' => 'midgard:create',
                'assignee'       => 'SELF',
                'classname'      => 'org_openpsa_sales_salesproject_dba',
            ],

            'widget'      => 'privilege',
            'start_fieldset' => [
                'title' => midcom::get()->i18n->get_string('org.openpsa.sales', 'org.openpsa.sales'),
                'css_group' => 'area',
            ],
            'end_fieldset' => '',
            'hidden' => !org_openpsa_core_siteconfig::get_instance()->node_exists('org.openpsa.sales'),
        ],
    ]
]
