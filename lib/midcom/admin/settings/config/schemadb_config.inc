'default' => array
(
    'name' => 'default',
    'description' => 'MidCOM Configuration schema',
    'l10n_db' => 'midcom.admin.settings',
    'fields' => array
    (

        // Core configuration

        'midcom_root_topic_guid' => array
        (
            'title' => 'midcom_root_topic_guid',
            'required' => true,
            'type' => 'select',
            'type_config' => array
            (
                 'require_corresponding_option' => false,
                 'options' => array(),
            ),
            'widget' => 'chooser',
            'widget_config' => array
            (
                'class'       => 'midcom_db_topic',
                'component'   => 'midgard.admin.asgard',
                'titlefield'  => 'extra',
                'id_field'     => 'guid',
                'constraints' => array
                (
                    array
                    (
                        'field' => 'up',
                        'op'    => '=',
                        'value' => 0,
                    ),
                ),
                'searchfields'  => array
                (
                    'name',
                    'extra'
                ),
                'result_headers' => array
                (
                    array
                    (
                        'title' => 'name',
                        'name' => 'name',
                    ),
                    array
                    (
                        'title' => 'title',
                        'name' => 'extra',
                    ),
                ),
                'orders'        => array
                (
                    array('up'    => 'ASC'),
                    array('name'  => 'ASC'),
                    array('extra' => 'ASC')
                ),
            ),
            'start_fieldgroup' => array
            (
                'title' => 'core configuration',
            ),
        ),
        'staging2live_staging' => array
        (
            'type' => 'select',
            'widget' => 'select',
            'title' => 'staging2live_staging',
            'type_config' => array
            (
                'options' => array
                (
                    ''    => midcom_admin_settings_editor::get_default_bool('staging2live_staging'),
                    '1' => 'yes',
                    '0' => 'no',
                ),
            ),
        ),
        'i18n_fallback_language' => array
        (
            'title' => 'i18n_fallback_language',
            'type' => 'select',
            'type_config' => Array
            (
                'options' => array_merge(array
                (
                    '' => sprintf($_MIDCOM->i18n->get_string('default (%s)', 'midcom.admin.settings'),
                                    $GLOBALS['midcom_config_default']['i18n_fallback_language'])
                ),
                $_MIDCOM->i18n->list_languages()),
            ),
            'widget' => 'select',

        ),
        'midcom_prefix' => array
        (
            'type' => 'text',
            'widget' => 'text',
            'title' => 'midcom_prefix',

        ),
        'midcom_sgconfig_basedir' => array
        (
            'type' => 'text',
            'widget' => 'text',
            'title' => 'midcom_sgconfig_basedir',
        ),

        'midcom_site_url' => array
        (
            'type' => 'text',
            'widget' => 'text',
            'title' => 'midcom_site_url',
        ),

        'midcom_tempdir' => array
        (
            'type' => 'text',
            'widget' => 'text',
            'title' => 'midcom_tempdir',
        ),

        'midcom_temporary_resource_timeout' => array
        (
            'type' => 'text',
            'widget' => 'text',
            'title' => 'midcom_temporary_resource_timeout',
        ),
        // Positioning system
        'positioning_enable' => array
        (
            'type' => 'select',
            'widget' => 'select',
            'title' => 'positioning_enable',
            'type_config' => array
            (
                'options' => array
                (
                    ''    => midcom_admin_settings_editor::get_default_bool('positioning_enable'),
                    '1' => 'yes',
                    '0' => 'no',
                ),
            ),
            'hidden' => ($_MIDCOM->componentloader->is_installed('org.routamc.positioning')) ? false : true,
        ),
        'component_listing_allowed' => array
        (
            'type' => 'select',
            'widget' => 'select',
            'title' => $_MIDCOM->i18n->get_string('allowed components (leave empty to allow all)', 'midcom.admin.settings'),
            'type_config' => array
            (
                'allow_multiple' => true,
                'options' => midcom_admin_folder_folder_management::list_components('', true),
            ),
        ),
        'component_listing_excluded' => array
        (
            'type' => 'select',
            'widget' => 'select',
            'title' => $_MIDCOM->i18n->get_string('excluded components (leave empty to allow all)', 'midcom.admin.settings'),
            'type_config' => array
            (
                'allow_multiple' => true,
                'options' => midcom_admin_folder_folder_management::list_components('', true),
            ),
            'end_fieldgroup' => '',
        ),
        // Logging Configuration
        'log_filename' => array
        (
            'type' => 'text',
            'widget' => 'text',
            'title' => 'log_filename',
            'start_fieldgroup' => array
            (
                'title' => 'logging configuration',
            ),
        ),

        'log_level' => array
        (
            'title' => 'log_level',
            'type' => 'select',
            'type_config' => Array
            (
                'options' => array
                (
                    ''       => midcom_admin_settings_editor::get_default_val('log_level'),
                    MIDCOM_LOG_DEBUG => 'midcom_log_debug',
                    MIDCOM_LOG_INFO  => 'midcom_log_info',
                    MIDCOM_LOG_WARN  => 'midcom_log_warn',
                    MIDCOM_LOG_ERROR => 'midcom_log_error',
                    MIDCOM_LOG_CRIT  => 'midcom_log_crit',
                ),
            ),
            'widget' => 'select',
        ),

        'log_tailurl_enable' => array
        (
            'type' => 'select',
            'widget' => 'select',
            'title' => 'log_tailurl_enable',
            'type_config' => array
            (
                'options' => array
                (
                    ''    => midcom_admin_settings_editor::get_default_bool('log_tailurl_enable'),
                    '1' => 'yes',
                    '0' => 'no',
                ),
            ),
            'end_fieldgroup' => '',
        ),
        // Cache configuration
        'cache_base_directory' => array
        (
            'type' => 'text',
            'widget' => 'text',
            'title' => 'cache_base_directory',
            'start_fieldgroup' => array
            (
                'title' => 'cache configuration',
            ),
        ),

        'cache_module_content_uncached' => array
        (
            'type' => 'select',
            'widget' => 'select',
            'title' => 'cache_module_content_uncached',
            'type_config' => array
            (
                'options' => array
                (
                    ''    => midcom_admin_settings_editor::get_default_bool('cache_module_content_uncached'),
                    '1' => 'yes',
                    '0' => 'no',
                ),
            ),
        ),

        'cache_module_content_headers_strategy' => array
        (
            'type' => 'select',
            'widget' => 'select',
            'title' => 'cache_module_content_headers_strategy',
            'type_config' => array
            (
                'options' => array
                (
                    ''  => midcom_admin_settings_editor::get_default_val('cache_module_content_headers_strategy'),
                    'no-cache' =>'cs-no-cache',
                'revalidate' =>'cs-revalidate',
                'public' =>'cs-public',
                'private'=>'cs-private',
                ),
            ),
        ),

        'cache_module_content_default_lifetime' => array
        (
            'type' => 'text',
            'widget' => 'text',
            'title' => 'cache_module_content_default_lifetime',
            'end_fieldgroup' => '',
        ),
        // Visibility settings (NAP)
        'show_hidden_objects' => array
        (
            'type' => 'select',
            'widget' => 'select',
            'title' => 'show_hidden_objects',
            'type_config' => array
            (
                'options' => array
                (
                    ''    => midcom_admin_settings_editor::get_default_bool('show_hidden_objects'),
                    '1' => 'yes',
                    '0' => 'no',
                ),
            ),
            'start_fieldgroup' => array
            (
                'title' => 'nap settings',
            ),
        ),

        'show_unapproved_objects' => array
        (
            'type' => 'select',
            'widget' => 'select',
            'title' => 'show_unapproved_objects',
            'type_config' => array
            (
                'options' => array
                (
                    ''    => midcom_admin_settings_editor::get_default_bool('show_unapproved_objects'),
                    '1' => 'yes',
                    '0' => 'no',
                ),
            ),
            'end_fieldgroup' => '',
        ),

        // Metadata system
        'metadata_approval' => array
        (
            'type' => 'select',
            'widget' => 'select',
            'title' => 'metadata_approval',
            'type_config' => array
            (
                'options' => array
                (
                    ''    => midcom_admin_settings_editor::get_default_bool('metadata_approval'),
                    '1' => 'yes',
                    '0' => 'no',
                ),
            ),
            'start_fieldgroup' => array
            (
                'title' => 'metadata configuration',
            ),
        ),
        'metadata_scheduling' => array
        (
            'type' => 'select',
            'widget' => 'select',
            'title' => 'metadata_scheduling',
            'type_config' => array
            (
                'options' => array
                (
                    ''    => midcom_admin_settings_editor::get_default_bool('metadata_scheduling'),
                    '1' => 'yes',
                    '0' => 'no',
                ),
            ),
        ),
        'metadata_opengraph' => array
        (
            'type' => 'select',
            'widget' => 'select',
            'title' => 'metadata_opengraph',
            'type_config' => array
            (
                'options' => array
                (
                    ''    => midcom_admin_settings_editor::get_default_bool('metadata_opengraph'),
                    '1' => 'yes',
                    '0' => 'no',
                ),
            ),
        ),
        'metadata_schema' => array
        (
            'type' => 'text',
            'widget' => 'text',
            'title' => 'metadata_schema',
            'end_fieldgroup' => '',
        ),



        // Authentication configuration
        'auth_backend' => array
        (
            'title' => 'auth_backend',
            'type' => 'select',
            'type_config' => Array
            (
                'options' => array
                (
                    ''       => midcom_admin_settings_editor::get_default_val('auth_backend',true),
                    'simple' => 'auth_backend_simple',
                ),
            ),
            'widget' => 'select',
            'start_fieldgroup' => array
            (
                'title' => 'auth configuration',
            ),
        ),

        'auth_login_session_timeout' => array
        (
            'type' => 'text',
            'widget' => 'text',
            'title' => 'auth_login_session_timeout',
        ),

        'auth_frontend' => array
        (
            'title' => 'auth_frontend',
            'type' => 'select',
            'type_config' => Array
            (
                'options' => array
                (
                    ''     => midcom_admin_settings_editor::get_default_val('auth_frontend',true),
                    'form' => 'auth_frontend_form',
                ),
            ),
            'widget' => 'select',
        ),
        'auth_sitegroup_mode' => array
        (
            'title' => 'auth_sitegroup_mode',
            'type' => 'select',
            'type_config' => Array
            (
                'options' => array
                (
                    ''                => midcom_admin_settings_editor::get_default_val('auth_sitegroup_mode',true),
                    'auto'            => 'auth_sitegroup_mode_auto',
                    'sitegrouped'     => 'auth_sitegroup_mode_sitegrouped',
                    'not-sitegrouped' => 'auth_sitegroup_mode_not-sitegrouped',
                ),
            ),
            'widget' => 'select',
        ),

        'auth_login_form_httpcode' => array
        (
            'title' => 'auth_login_form_httpcode',
            'type' => 'select',
            'type_config' => Array
            (
                'options' => array
                (
                    ''    => midcom_admin_settings_editor::get_default_val('auth_login_form_httpcode',true),
                    '403' => 'auth_login_form_httpcode_403',
                    '200' => 'auth_login_form_httpcode_200',
                ),
            ),
            'widget' => 'select',
        ),

        'auth_check_client_ip' => array
        (
            'type' => 'select',
            'widget' => 'select',
            'title' => 'auth_check_client_ip',
            'type_config' => array
            (
                'options' => array
                (
                    ''    => midcom_admin_settings_editor::get_default_bool('auth_check_client_ip'),
                    '1'    => 'yes',
                    '0'    => 'no',
                ),
            ),
        ),

        'auth_openid_enable' => array
        (
            'type' => 'select',
            'widget' => 'select',
            'title' => 'auth_openid_enable',
            'type_config' => array
            (
                'options' => array
                (
                    ''    => midcom_admin_settings_editor::get_default_bool('auth_openid_enable'),
                    '1' => 'yes',
                    '0' => 'no',
                ),
            ),
        ),

        'auth_drupal_enable' => array
        (
            'type' => 'select',
            'widget' => 'select',
            'title' => 'auth_drupal_enable',
            'type_config' => array
            (
                'options' => array
                (
                    ''    => midcom_admin_settings_editor::get_default_bool('auth_drupal_enable'),
                    '1' => 'yes',
                    '0' => 'no',
                ),
            ),
        ),

        'auth_drupal_settings_php' => array
        (
            'type' => 'text',
            'widget' => 'text',
            'title' => 'auth_drupal_settings_php',

        ),

        'auth_drupal_domain' => array
        (
            'type' => 'text',
            'widget' => 'text',
            'title' => 'auth_drupal_domain',

        ),

        'auth_allow_sudo' => array
        (
            'type' => 'select',
            'widget' => 'select',
            'title' => 'auth_allow_sudo',
            'type_config' => array
            (
                'options' => array
                (
                    ''    => midcom_admin_settings_editor::get_default_bool('auth_allow_sudo'),
                    '1' => 'yes',
                    '0' => 'no',
                ),
            ),
        ),


        'auth_backend_simple_cookie_id' => array
        (
            'type' => 'text',
            'widget' => 'text',
            'title' => 'auth_backend_simple_cookie_id',

        ),

        'auth_backend_simple_cookie_path' => array
        (
            'type' => 'text',
            'widget' => 'text',
            'title' => 'auth_backend_simple_cookie_path',
        ),

        'auth_backend_simple_cookie_domain' => array
        (
            'type' => 'text',
            'widget' => 'text',
            'title' => 'auth_backend_simple_cookie_domain' ,
            'end_fieldgroup' => '',
        ),





        // Indexer Configuration
        'indexer_backend' => array
        (
            'title' => 'indexer_backend',
            'type' => 'select',
            'type_config' => Array
            (
                'options' => array
                (
                    false    => 'indexer_backend_none',
                    'solr' => 'Solr indexer',
                ),
            ),
            'widget' => 'select',
            'start_fieldgroup' => array
            (
                'title' => 'indexer configuration',
            ),
        ),

        'indexer_index_name' => array
        (
            'type' => 'text',
            'widget' => 'text',
            'title' => 'indexer_index_name',
        ),

        // SOLR indexer backend setup
        'indexer_xmltcp_host' => array
        (
            'type' => 'text',
            'widget' => 'text',
            'title' => 'indexer_xmltcp_host',
        ),

        'indexer_xmltcp_port' => array
        (
            'type' => 'text',
            'widget' => 'text',
            'title' => 'indexer_xmltcp_port',
            'end_fieldgroup' => '',
        ),


        // Utilities
        'utility_imagemagick_base' => array
        (
            'type' => 'text',
            'widget' => 'text',
            'title' => 'utility_imagemagick_base',
            'start_fieldgroup' => array
            (
                'title' => 'utilities',
            ),
        ),

        'utility_jpegtran' => array
        (
            'type' => 'text',
            'widget' => 'text',
            'title' => 'utility_jpegtran',
        ),

        'utility_unzip' => array
        (
            'type' => 'text',
            'widget' => 'text',
            'title' => 'utility_unzip',
        ),

        'utility_gzip' => array
        (
            'type' => 'text',
            'widget' => 'text',
            'title' => 'utility_gzip',
        ),

        'utility_tar' => array
        (
            'type' => 'text',
            'widget' => 'text',
            'title' => 'utility_tar',
        ),

        'utility_find' => array
        (
            'type' => 'text',
            'widget' => 'text',
            'title' => 'utility_find',
        ),

        'utility_file' => array
        (
            'type' => 'text',
            'widget' => 'text',
            'title' => 'utility_file',
        ),

        'utility_catdoc' => array
        (
            'type' => 'text',
            'widget' => 'text',
            'title' => 'utility_catdoc',
        ),

        'utility_pdftotext' => array
        (
            'type' => 'text',
            'widget' => 'text',
            'title' => 'utility_pdftotext',
        ),

        'utility_unrtf' => array
        (
            'type' => 'text',
            'widget' => 'text',
            'title' => 'utility_unrtf',
        ),

        // Revision control service
        'utility_diff' => array
        (
            'type' => 'text',
            'widget' => 'text',
            'title' => 'utility_diff',
        ),

        'utility_rcs' => array
        (
            'type' => 'text',
            'widget' => 'text',
            'title' => 'utility_rcs',
        ),

        'midcom_services_rcs_bin_dir' => array
        (
            'type' => 'text',
            'widget' => 'text',
            'title' => 'midcom_services_rcs_bin_dir',
        ),

        'midcom_services_rcs_enable' => array
        (
            'type' => 'select',
            'widget' => 'select',
            'title' => 'midcom_services_rcs_enable',
            'type_config' => array
            (
                'options' => array
                (
                    ''    => midcom_admin_settings_editor::get_default_bool('midcom_services_rcs_enable'),
                    '1' => 'yes',
                    '0' => 'no',
                ),
            ),
            'end_fieldgroup' => '',
        ),



    ),
),
