<?php
/**
 * @package midcom
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Core configuration defaults.
 *
 * As always, you must not change this file. Instead, you have two levels of customization,
 * merged in the order listed here:
 *
 * <b>Site-specific configuration:</b>
 *
 * MidCOM will include the file <i>midcom::get()->config->get('midcom_config_basedir') . /midcom.conf</i> which must be a regular
 * PHP file. You may populate the global array $midcom_config_site in this file. It should
 * list all options that apply to all installations (like the Cache backend selection
 * or the indexer host).
 *
 * Example:
 *
 * <code>
 * $GLOBALS['midcom_config_site']['cache_module_content_backend'] =
 *     Array ('directory' => 'content/', 'driver' => 'sqlite');
 * </code>
 *
 * <b>Instance-specific configuration:</b>
 *
 * After including the site itself, MidCOM also merges the contents of the global array
 * $midcom_config_local, which may hold configuration data for the website itself.
 *
 * These settings must be set for all sites:
 * - midcom_root_topic_guid
 *
 * You will usually include these lines somewhere before actually including MidCOM.
 *
 * <code>
 * $GLOBALS['midcom_config_local']['midcom_root_topic_guid'] = '123456789...';
 * </code>
 *
 * <b>Configuration setting overview:</b>
 *
 * The following configuration options are available for the MidCOM core along
 * with their defaults, shown in alphabetical order:
 *
 * <b>Authentication configuration</b>
 *
 * - <b>boolean allow_sudo:</b> Set this to true (the default) to allow components to
 *   request super user privileges for certain operations. This is mainly used to allow
 *   anonymous access to the system without having to store a user account everywhere.
 * - <b>string auth_backend:</b> The authentication backend to use, the "simple"
 *   backend is used as a default.
 * - <b>boolean auth_check_client_ip:</b> Control whether to check the client IP address
 *   on each subsequent request when authentication a user. This is enabled by default
 *   as it will make session hijacking much harder. You should not turn it off unless
 *   you have very good reasons to do.
 * - <b>int auth_login_session_timeout:</b> The login session timeout to use, this
 *   defaults to 3600 seconds (1 hour). Use 0 to have the session stay active until manual logout
 * - <b>string auth_frontend:</b> The authentication frontend to use, the "form" frontend
 *   is used by default.
 * - <b>int auth_login_form_httpcode</b>: HTTP return code used in MidCOM login screens,
 *   either 403 (403 Forbidden) or 200 (200 OK), defaulting to 403.
 * - <b>boolean auth_openid_enable:</b> Whether to enable OpenID authentication handled with
 *   the net.nemein.openid library
 *
 * <b>Authentication Backend configuration: "simple"</b>
 *
 * - <b>auth_backend_simple_cookie_secure:</b> Set the "secure" flag on cookie, defaults to true, applies only when actually using SSL/TLS
 * - <b>auth_backend_simple_cookie_id:</b> The ID appended to the cookie prefix, separating
 *   auth cookies for different sites. Defaults to 1.
 * - <b>auth_backend_simple_cookie_path:</b> Controls the valid path of the cookie,
 *   defaults to midcom_connection::get_url('self').
 * - <b>auth_backend_simple_cookie_domain:</b> Controls the valid domain of the cookie.
 *   If it is set to null (the default), no domain is specified in the cookie, making
 *   it a traditional site-specific session cookie. If it is set, the domain parameter
 *   of the cookie will be set accordingly.
 *
 * <b>Cache configuration</b>
 *
 * - <b>array cache_autoload_queue:</b> The cache module loading queue during startup, you
 *   should normally have no need to change this (unless you want to add your own caching modules,
 *   in which case you have to ensure that the loading queue of MidCOM itself (as seen in this
 *   file) is not changed.
 * - <b>string cache_base_directory:</b> The directory where to place cache files for MidCOM.
 *      This defaults to /tmp/ (note the trailing slash) as this is writable everywhere.
 *
 * - <b>Array cache_module_content_backend:</b> The configuration of the content cache backend.
 *   Check the documentation of midcom_services_cache_backend of what options are available here.
 *   In general, you should use this only to change the backend driver.
 *   In all other cases you should leave this option untouched. The defaults are to store all
 *   cache databases into the 'content/' subdirectory of the cache base directory.
 * - <b>string cache_module_content_name:</b> The identifier, the content cache should use for
 *   naming the files/directories it creates. This defaults to a string constructed out of the
 *   host's name, port and prefix. You should only change this if you run multiple MidCOM
 *   sites on the same host.
 * - <b>boolean cache_module_content_uncached:</b> Set this to true if you want the site to run in an uncached
 *      mode. This is different from cache_disable in that the regular header preprocessing is
 *   done anyway, allowing for browser side caching. Essentially, the computing order is the
 *   same (no_cache for example is considered like usual), but the cache file is not stored.
 *   This defaults to false.
 * - <b>string cache_module_content_headers_strategy:</b> Valid values are<br/>
 *   'no-cache' activates no-cache mode that actively tries to circumvent all caching<br/>
 *   'revalidate' is the default which sets 'must-revalidate' and presses the issue by setting Expires to current time<br/>
 *   'public' and 'private' enable caching with the cache-control header of the same name, default expiry timestamps are generated using the cache_module_content_default_lifetime
 * - <b>int cache_module_content_default_lifetime:</b> How many seconds from now to set the default Expires header to, defaults to 15 minutes. Also used as default expiry time for content-cache entries that have no expiry set.
 * - <b>string cache_module_content_headers_strategy_authenticated:</b> Defaults to 'private', this is equivalent to cache_module_content_headers_strategy but applies when we have authenticated user.
 * - <b>int cache_module_content_default_lifetime_authenticated:</b> defaults to 0, equivalent to cache_module_content_default_lifetime but applies to authenticated users (except this does not set content-cache expiry). These two options are added to combat braindead proxies.
 * - <b>string cache_module_content_caching_strategy:</b> Valid values are<br/>
 *   'user' the "classic" mode, per user content-cache, default<br/>
 *   'memberships' cache per group memberships (users that have same memberships share same cache), for many cases this should offer more performance and smaller cache but if you use per-user privileges or other user specific processing this will cause hard-to-debug issues<br/>
 *   'public' everything goes to single public cache, disabling logins altogether will likely be safer.
 * - <b>Array cache_module_nap_backend:</b> The configuration of the nap/metadata cache backend.
 *   Check the documentation of midcom_services_cache_backend of what options are available here.
 *   In general, you should use this only to change the backend driver.
 *   In all other cases you should leave this option untouched. The defaults are to store all
 *   cache databases into the 'nap/' subdirectory of the cache base directory.
 *   The databases are named after the root topic guid, which should be sufficient
 *   in all cases. If you really want to separate things here, use different directories for
 *   the backends.
 * - <b>string cache_module_memcache_backend:</b> The cache backend to use for the memcache caching
 *   module. The default is null, which disables the module entirely. This is the default. If you
 *   have both memcached and the memcached PHP extension installed, set this to 'memcached', to enable
 *   the cache.
 * - <b>Array cache_module_memcache_backend_config:</b> The backend configuration to use if a backend
 *   was specified. See the individual backend documentations for more information about the allowed
 *   option set. This defaults to an empty array.
 * - <b>Array cache_module_memcache_data_groups:</b> The data groups available for the memcache module.
 *   You should normally not have to touch this, see the memcache module documentation for details.
 *   This defaults to Array('ACL', 'PARENT').
 *
 * See also midcom_services_cache, the midcom_services_cache_backend class hierarchy and
 * the midcom_services_cache_module class hierarchy.
 *
 * <b>Indexer configuration</b>
 *
 * - <b>string indexer_index_name:</b> The default Index to use when indexing the website. This defaults to
 *   a string constructed out of the host's name, port and prefix. You should only change this if you run
 *   the same MidCOM site across multiple hosts.
 * - <b>string indexer_backend:</b> The default indexer backend to use. This defaults to the false,
 *   indicating that <i>no</i> indexing should be done. Right now, the SOLR backend is recommended.
 * - <b>indexer_reindex_allowed_ips:</b> Array of IPs that don't need to basic authenticate themselves
 *   to run MidCOM reindexing or cron.
 *
 * <b>Indexer backend configuration: SOLR module</b>
 *
 * - <b>string indexer_xmltcp_host:</b> The host name or IP address where the indexer daemon is running.
 *   This defaults to "tcp://127.0.0.1", which is the default bind address of the daemon.
 * - <b>int indexer_xmltcp_port:</b> The port to which to connect. This defaults to 8983, which is the
 *      default port of the daemon.
 *
 * <b>Logging configuration</b>
 *
 * - <b>string log_filename:</b> The filename to dump logging messages to, this
 *      defaults to /tmp/debug.log.
 * - <b>int log_level:</b> The logging level to use when starting up the logger, set to
 *   MIDCOM_LOG_ERROR by default. You cannot use the MIDCOM* constants when setting
 *   micdom_config_local, as they are not defined at that point. Use 0 for CRITICAL,
 *   1 for ERROR, 2 for WARING, 3 for INFO and 4 for DEBUG level logging.
 * - <b>array error_actions:</b> Actions to run when a specific error code is produced. This can
 *   be used for saving logs about 404 errors from broken links, or sending an error 500 to
 *   webmaster for analysis.
 *
 *   Configuration example:
 *
 * <code>
 * $GLOBALS['midcom_config_local']['error_actions'] = array
 * (
 *     500 => array
 *     (
 *         'action' => 'email',
 *         'email' => 'webmaster@example.net',
 *     ),
 *     404 => array
 *     (
 *         'action' => 'log',
 *         'filename' => '/var/log/broken_links.log',
 *     ),
 * );
 * </code>
 *
 * <b>MidCOM Core configuration</b>
 *
 * - <b>GUID midcom_root_topic_guid:</b> This is the GUID of the topic we should handle.
 *   This must be set on a per-site basis, otherwise MidCOM won't start up.
 * - <b>string midcom_sgconfig_basedir:</b> The base snippetdir where the current
 *   sites' configuration is stored. This defaults to "/sitegroup-config" which will
 *   result in the original default shared sitegroup-wide configuration.
 * - <b>string midcom_site_url:</b> The fully qualified URL to the Website. A trailing
 *   slash is required. It defaults to '/'.
 *   If an absolute local URL is given to this value, the full URL of the current host
 *   is prefixed to its value, so that this configuration key can be used for Location
 *   headers. You must not use a relative URL. This key will be completed by the MidCOM
 *   Application constructor, before that, it might contain a URL which is not suitable
 *   for relocations.
 * - <b>string midcom_tempdir:</b> A temporary directory that can be used when components
 *   need to write out files. Defaults to '/tmp'.
 * - <b>mixed midcom_max_memory:</b> The maximum memory limit to use doing resource-intensive tasks
 *   like when reindexing the entire site, which can require quite some amount of memory, as the complete NAP
 *   cache has to be loaded and binary indexing can take some memory, too. Defaults to -1.
 *  - <b>mixed midcom_max_execution_time:</b> The maximum execution time for resource-intensive tasks
 *  - <b>array midcom_components:</b> Additional out-of-tree components as name => path pairs
 *
 * <b>RCS system</b>
 *
 *  <b>string midcom_services_rcs_bin_dir</b>: the prefix for the rcs utilities (default: /usr/bin)
 *  <b>string midcom_services_rcs_root </b>: the directory where the rcs files get placed. (default: must be set!)
 *  <b>boolean midcom_services_rcs_enable</b>:  if set, midcom will fail hard if the rcs service is not operational. (default: false)
 *
 * <b>Style Engine</b>
 *
 * - <b>Array styleengine_default_styles:</b> Use this array to set site-wide default
 *   styles to be used for the components. This is an array indexing component name to
 *   style path. Components not present in this array use the default style delivered
 *   with the component. Any style set directly on a topic or inherited to it will
 *   override these settings. This defaults to an empty Array.
 *
 * <b>Toolbars System</b>
 *
 * The CSS classes and IDs used by the toolbars service can be configured using these
 * options:
 *
 * - <b>string toolbars_host_style_class:</b> defaults to "midcom_toolbar host_toolbar"
 * - <b>string toolbars_host_style_id:</b> defaults to ""
 * - <b>string toolbars_node_style_class:</b> defaults to "midcom_toolbar node_toolbar"
 * - <b>string toolbars_node_style_id:</b> defaults to ""
 * - <b>string toolbars_view_style_class:</b> defaults to midcom_toolbar view_toolbar
 * - <b>string toolbars_view_style_id:</b> defaults to ""
 * - <b>string toolbars_object_style_class:</b> defaults to midcom_toolbar object_toolbar
 * - <b>string toolbars_simple_css_path:</b> this defaults to MIDCOM_STATIC_URL/midcom.services.toolbars/simple.css
 *   and is used to set the css for the toolbars used with onsite editing.
 * - <b>boolean toolbars_enable_centralized:</b> defaults to true, whether to enable the centralized,
 *   javascript-floating MidCOM toolbar that users can display with midcom::get()->toolbars->show();
 *
 * <b>Utility Programs</b>
 *
 * The various paths set here lead to the utility programs required by MidCOM, both
 * mandatory and optional applications are listed here. To indicate that a certain
 * application is unavailable, set it to null. The defaults assume that the files are within the
 * $PATH of the Apache user and should be sufficient in most cases. Package maintainers
 * are encouraged to make the paths explicit.
 *
 * - <b>string utility_imagemagick_base:</b> The base path of the ImageMagick executables,
 *   the tools <i>mogrify</i>, <i>identify</i> and <i>convert</i> are needed for almost
 *   all kinds of image operations in MidCOM and have to be present therefore. The path
 *   entered here requires a trailing slash.
 * - <b>string utility_jpegtran:</b> JPEGTran is used to do lossless rotation of JPEG
 *   images for automatic EXIF rotation in n.s.photos for example. If unavailable,
 *   there is an automatic fallback to imagemagick.
 * - <b>string utility_find:</b> The Find utility is used for bulk upload preprocessing
 *   and the like.
 * - <b>string utility_catdoc:</b> Transforms Word Documents into text for indexing.
 * - <b>string utility_pdftotext:</b> Transforms PDF Documents into text for indexing.
 * - <b>string utility_unrtf:</b> Transforms RTF Documents into text files for indexing.
 *
 * <b>Visibility settings (NAP and DBA)</b>
 *
 * - <b>boolean show_hidden_objects:</b> This flag indicates whether objects that are
 *   invisible either by explicit hiding or by their scheduling should be shown anyway.
 *   This defaults to true at this time
 * - <b>boolean show_unapproved_objects:</b> This flag indicates whether objects should be
 *   shown even if they are not approved. This defaults to true.
 *
 * @package midcom
 */
class midcom_config implements arrayaccess
{
    private $_default_config = [
        // Authentication configuration
        'auth_type' => 'Legacy',
        'auth_backend' => 'simple',
        'auth_backend_simple_cookie_id' => 1,
        'auth_login_session_timeout' => 3600,
        'auth_login_session_update_interval' => 300,
        'auth_frontend' => 'form',
        'auth_check_client_ip' => true,
        'auth_allow_sudo' => true,
        'auth_login_form_httpcode' => 403,
        'auth_openid_enable' => false,
        'auth_save_prev_login' => false,
        'auth_allow_trusted' => false,
        'person_class' => 'openpsa_person',

        'auth_backend_simple_cookie_path' => 'auto',
        'auth_backend_simple_cookie_domain' => null,
        // set secure flag on cookie (applies only when using SSL)
        'auth_backend_simple_cookie_secure' => true,

        // Cache configuration
        'cache_base_directory' => '/tmp/',
        'cache_autoload_queue' => ['content', 'nap', 'memcache'],

        // Content Cache
        'cache_module_content_name' => 'auto',

        //Memory Caching Daemon
        'cache_module_memcache_backend' => 'flatfile',
        'cache_module_memcache_backend_config' => [],
        'cache_module_memcache_data_groups' => ['ACL', 'PARENT', 'L10N', 'MISC'],

        // Defaults:
        'cache_module_content_backend' => ['driver' => 'flatfile'],
        'cache_module_content_uncached' => true,
        'cache_module_content_headers_strategy' => 'revalidate',
        'cache_module_content_headers_strategy_authenticated' => 'private',
        // Seconds, added to gmdate() for expiry timestamp (in case no other expiry is set),
        // also used as default expiry for content-cache entries that have no expiry set
        'cache_module_content_default_lifetime' => 900,
        // as above but concerns only authenticated state
        'cache_module_content_default_lifetime_authenticated' => 0,
        // Valid options are 'user' (default), 'memberships' and 'public'
        'cache_module_content_caching_strategy' => 'user',

        // NAP Cache
        'cache_module_nap_backend' => [] /* Auto-Detect */,

        // CRON Service configuration
        'cron_day_hours' => 0,
        'cron_day_minutes' => 0,
        'cron_hour_minutes' => 30,

        // I18n Subsystem configuration
        'i18n_available_languages' => null,
        'i18n_fallback_language' => 'en',

        // Indexer Configuration
        'indexer_backend' => false,
        'indexer_index_name' => 'auto',
        'indexer_reindex_allowed_ips' => ['127.0.0.1'],

        'indexer_config_options' => ['fl' => '*,score', 'rows' => 1000, 'defType' => 'dismax', 'qf' => 'content'],

        // XMLTCP indexer backend
        'indexer_xmltcp_host' => "127.0.0.1",
        'indexer_xmltcp_port' => 8983,
        'indexer_xmltcp_core' => null,

        // Logging Configuration
        'log_filename' => '/tmp/midcom.log',
        'log_level' => MIDCOM_LOG_ERROR,
        'error_actions' => [],

        // Core configuration
        'midcom_root_topic_guid' => '',
        'midcom_config_basedir' => '/etc/midgard/',
        'midcom_sgconfig_basedir' => '/sitegroup-config',
        'midcom_site_url' => '/',
        'midcom_site_title' => '',
        'midcom_tempdir' => '/tmp',
        'midcom_max_memory' => -1,
        'midcom_max_execution_time' => 0,
        'midcom_components' => [],

        // Visibility settings (NAP)
        'show_hidden_objects' => true,
        'show_unapproved_objects' => true,
        // Style Engine defaults
        'styleengine_default_styles' => [],

        // Toolbars service
        'toolbars_host_style_class' => 'midcom_toolbar host_toolbar',
        'toolbars_host_style_id' => null,
        'toolbars_node_style_class' => 'midcom_toolbar node_toolbar',
        'toolbars_node_style_id' => null,
        'toolbars_view_style_class' => 'midcom_toolbar view_toolbar',
        'toolbars_view_style_id' => null,
        'toolbars_help_style_class' => 'midcom_toolbar help_toolbar',
        'toolbars_help_style_id' => null,
        'toolbars_simple_css_path' => null,
        'toolbars_enable_centralized' => true,

        // Service implementation defaults
        'service_midcom_core_service_urlparser' => midcom_core_service_implementation_urlparsertopic::class,
        'service_midcom_core_service_urlgenerator' => midcom_core_service_implementation_urlgeneratori18n::class,

        // Public attachment caching directives
        'attachment_cache_enabled' => false,
        'attachment_cache_root' => '/var/lib/midgard/vhosts/example.net/80/midcom-static/blobs',
        'attachment_cache_url' => '/midcom-static/blobs',

        //X-sendfile support
        'attachment_xsendfile_enable' => false,

        // Utilities
        'utility_imagemagick_base' => '',
        'utility_jpegtran' => 'jpegtran',
        'utility_find' => 'find',
        'utility_catdoc' => 'catdoc',
        'utility_pdftotext' => 'pdftotext',
        'utility_unrtf' => 'unrtf',

        'midcom_services_rcs_bin_dir' => '/usr/bin',
        'midcom_services_rcs_root' => '',
        'midcom_services_rcs_enable' => true,

        // Metadata system

        // Enables approval/scheduling controls (does not influence visibility checks using
        // show_unapproved_objects). Disabled by default. Unsafe to Link Prefetching!
        'metadata_approval' => false,
        'metadata_scheduling' => false,
        'metadata_lock_timeout' => 60,    // Time in minutes
        'staging2live_staging' => false,

        // Set the DM2 schema used by the Metadata Service
        'metadata_schema' => 'file:/midcom/config/metadata_default.inc',

        // Map MidCOM metadata properties to HTML meta tags
        'metadata_head_elements' => [
            'published'   => 'DC.date',
            'description'   => 'description',
        ],

        // Whether to gather and display Open Graph Protocol metadata for Midgard pages
        'metadata_opengraph' => false,

        // Component system
        // Show only these components when creating or editing
        'component_listing_allowed' => null,
        'component_listing_excluded' => null,

        // Page class (body class)
        // If this argument is set to true, sanitized name of the component is added to the page class string.
        'page_class_include_component' => true,

        // If this argument is set to true, All midcom_show_style calls wrap the style with HTML comments defining the style path
        'wrap_style_show_with_name' => false,

        // Related to JavaScript libraries
        'jquery_version' => '2.2.4.min',
        'jquery_version_oldie' => '1.12.4.min',
        'jquery_ui_version' => '1.12.1',
        'jquery_ui_theme' => null,
        'jquery_load_from_google' => false,
        'enable_ajax_editing' => false,

        /**
         * Sessioning service, disabling the service will help with external caches.
         * The second option is to allow logged in users to benefit from the service
         */
        'sessioning_service_enable' => true,
        'sessioning_service_always_enable_for_users' => true,

        /**
         * Trash cleanup, purge deleted objects after X days
         */
        'cron_purge_deleted_after' => 25,

        /**
         * Theme support
         */
        'theme' => '',
    ];

    private $_merged_config = [];

    public function __construct()
    {
        $this->_complete_defaults();

        /* ----- MERGE THE CONFIGURATION ----- */
        if (!array_key_exists('midcom_config_site', $GLOBALS)) {
            $GLOBALS['midcom_config_site'] = [];
        }
        if (!array_key_exists('midcom_config_local', $GLOBALS)) {
            $GLOBALS['midcom_config_local'] = [];
        }
        $this->_merged_config = array_merge(
            $this->_default_config,
            $GLOBALS['midcom_config_site'],
            $GLOBALS['midcom_config_local']
        );
    }

    private function _complete_defaults()
    {
        if (class_exists('Memcached')) {
            $this->_default_config['cache_module_content_backend'] = ['driver' => 'memcached'];
            $this->_default_config['cache_module_memcache_backend'] = 'memcached';
        }
        if (isset($_SERVER['SERVER_ADDR'])) {
            $this->_default_config['indexer_reindex_allowed_ips'][] = $_SERVER['SERVER_ADDR'];
        }
        if (!empty($_SERVER['SERVER_NAME'])) {
            $this->_default_config['midcom_site_title'] = $_SERVER['SERVER_NAME'];
        }
    }

    public function get($key, $default = null)
    {
        if (!$this->offsetExists($key)) {
            return $default;
        }

        if (   $key === 'auth_type'
            && !in_array($this->_merged_config[$key], ['Plaintext', 'Legacy', 'SHA256'])) {
            throw new midcom_error('Unsupported authentication type');
        }
        // Check the midcom_config site prefix for absolute local urls
        if (   $key === 'midcom_site_url'
            && substr($this->_merged_config[$key], 0, 1) === '/') {
            $this->_merged_config[$key] = midcom::get()->get_page_prefix() . substr($this->_merged_config[$key], 1);
        }

        return $this->_merged_config[$key];
    }

    public function set($key, $value)
    {
        $this->_merged_config[$key] = $value;
    }

    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    public function offsetExists($offset)
    {
        return isset($this->_merged_config[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->_merged_config[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->get($offset);
    }
}
