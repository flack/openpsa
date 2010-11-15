<?php
/**
 * @package midcom
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: application.php 26625 2010-08-26 11:51:41Z jval $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Main controlling instance of the MidCOM Framework
 *
 * This class is the heart of the MidCOM Framework. It has the entry points which
 * are used to fire up the framework and get everything running. This class
 * represents a Facade pattern.
 *
 * - Provide the entry points that are located in code-global, code-init and
 *   content. They will activate the framework.
 * - Provide some very basic Helper Functions for snippet loading and error-page
 *   generation
 * - Evaluate the URL and activate the required components.
 * - Provide a mechanism to dynamically load a second component during a page
 *   run.
 * - Provide a specialized mechanism to dynamically invoke a component's
 *   Administration Interface.
 * - Provide a basic context mechanism that allows each independent component
 *   invocation to access its own context information.
 *
 * <b>URL METHODS TO THE MIDCOM ROOT PAGE</b>
 *
 * The following URL parameters are recognized by the _process function and are
 * executed before any component processing is done. They all belong to the
 * domain "midcom", e.g. they are executed like this: midcom-$name-$value.
 *
 * <b>string substyle</b>
 *
 * This will set a substyle to the current component, which is appended to the
 * style selected by the component at the moment the component style is loaded.
 * The methods substyle_(append|prepend)'s work on the basis of this value then.
 *
 * Note, that this first assignment is done between can_handle and handle, so
 * it will serve as a basis for all component-side style switching operations.
 *
 * The substyle URL switch is most useful in conjunction with
 * midcom_application::dynamic_load().
 *
 * <b>int serveattachment; GUID serveattachmentguid</b>
 *
 * This pair of methods will serve the attachment denoted by the given ID/GUID.
 * It uses the default expiration time of serve_attachment (see there).
 *
 * <b>int servesnippet; GUID servesnippetguid</b>
 *
 * This pair will serve the code field of a snippet denoted by the given ID/GUID,
 * see serve_snippet for further options. For security purposes, the snippets that
 * may be served using this function MUST have the parameter midcom/allow_serve
 * set to "true". If this is not the case, snippet serving will be aborted with an
 * access denied error. See the serve_snippet method documentation for further details.
 *
 * <b>GUID permalink</b>
 *
 * This will resolve the given GUID into the MidCOM NAP tree, relocating to the
 * URL corresponding to the node/leaf. The Permalink can be created by using the
 * key MIDCOM_NAV_PERMALINK of any NAP data array. Upon resolving it, MidCOM will
 * relocate to the automatically computed MIDCOM_NAV_FULLURL.
 *
 * <b>string exec</b>
 *
 * Allows you to execute certain php files directly, in full MidCOM context.
 * The argument is the name of the component, which holds the script to be
 * executed. Script files are searched in the subdirectory "exec" of the component.
 * If you use "midcom" as component name, MidCOM core scripts, located in
 * lib/midcom/exec will be accessible. The next argument on the command line must
 * be the name of the script file. Accessing subdirectories is not possible, only
 * a single argument will be taken.
 *
 * The scripts executed need to do their own permission checks, they will work with
 * the credentials of the current MidCOM instance unconditionally.
 *
 * Example: http://$host/midcom-exec-midcom/upgrade_metadata.php
 *
 * The remaining URL arguments are put into the globals $argc/$argv.
 *
 * <b>string cache</b>
 *
 * May take one of the following values: "invalidate" will clear the cache of the
 * current site, "nocache" will bypass the cache for the current request by
 * calling $this->cache->content->no_cache();
 *
 * <b>mixed log</b>
 *
 * Shows the contents of the current debuglog. You have to enable this interface
 * by setting the config option <i>log_tailurl_enable</i> to true.
 * Note, that this method is using the debug log path
 * of the current MidCOM logger automatically, it is not possible to switch to
 * another logfile dynamically due to security reasons. The parameter can be
 * either "all" which will yield the complete log (beware of huge logfiles), or
 * an integer, which is the number of lines counting from the file backwards you
 * want to display (this uses the systems tail command via exec).
 *
 * NOTE: This function is limited by PHP's memory limit, as the (f)passthru
 * functions are really intelligent and try to load the complete file into memory
 * instead streaming it to the client.
 *
 * @package midcom
 * @property midcom_helper_serviceloader $serviceloader
 * @property midcom_services_i18n $i18n
 * @property midcom_helper__componentloader $componentloader
 * @property midcom_services_dbclassloader $dbclassloader
 * @property midcom_helper__dbfactory $dbfactory
 * @property midcom_helper__styleloader $style
 * @property midcom_services_permalinks $permalinks
 * @property midcom_services_tmp $tmp
 * @property midcom_services_toolbars $toolbars
 * @property midcom_services_uimessages $uimessages
 * @property midcom_services_metadata $metadata
 * @property midcom_services_rcs $rcs
 * @property midcom_services__sessioning $session
 * @property midcom_services_indexer $indexer
 */
class midcom_application
{
    /**
     * Holds the component context information.
     *
     * This is an array of arrays, the outer one indexed by context IDs, the
     * inner one indexed by context keys. Only valid of the system has left
     * the code-init phase.
     *
     * @var Array
     * @access private
     */
    public $_context = array();

    /**
     * Contains the ID of the currently active context or FALSE is none is active.
     *
     * @var int
     * @access private
     */
    private $_currentcontext = 0;

    /**
     * The active component.
     *
     * @var string
     * @access private
     */
    private $_currentcomponent = '';

    /**
     * The client status array.
     *
     * @var Array
     * @access private
     */
    private $_client = array();

    /**
     * The prefix which is appended to midcom_connection::get_url('self') (i.e. the
     * Midgard Page URL).
     *
     * This may be needed when MidCOM is run by wrapper.
     * see constructor.
     *
     * @var string
     * @access private
     */
    private $_prefix = '';

    /**
     * Integer constant resembling the current MidCOM state.
     *
     * See the MIDCOM_STATUS_... constants
     *
     * @var int
     * @access private
     */
    private $_status = null;

    /**
     * This is the interface to MidCOMs Object Services.
     *
     * Each service is indexed by its string-name (for example "i18n"
     * for all i18n stuff).
     *
     * @var Array
     * @access private
     */
    private $_services = array();

    /**
     * Mapping of service names to classes implementing the service
     */
    private $_service_classes = array
    (
        'serviceloader' => 'midcom_helper_serviceloader',
        'i18n' => 'midcom_services_i18n',
        'componentloader' => 'midcom_helper__componentloader',
        'dbclassloader' => 'midcom_services_dbclassloader',
        'dbfactory' => 'midcom_helper__dbfactory',
        'style' => 'midcom_helper__styleloader',
        'permalinks' => 'midcom_services_permalinks',
        'tmp' => 'midcom_services_tmp',
        'toolbars' => 'midcom_services_toolbars',
        'uimessages' => 'midcom_services_uimessages',
        'metadata' => 'midcom_services_metadata',
        'rcs' => 'midcom_services_rcs',
        'session' => 'midcom_services__sessioning',
        'indexer' => 'midcom_services_indexer',
    );

    /**
     * Array of URL parsers by context.
     *
     * @var midcom_core_service_urlparser
     * @access private
     */
    private $_parsers = array();

    /**
     * JS/CSS merger service
     */
    var $jscss = false;

    /**
     * Array with all JavaScript declarations for the page's head.
     *
     * @var Array
     * @access private
     */
    private $_jshead = array();

    /**
     * Array with all JavaScript file inclusions.
     *
     * @var Array
     * @access private
     */
    private $_jsfiles = array();

    /**
     * String with all prepend JavaScript declarations for the page's head.
     *
     * @var string
     * @access private
     */
    private $_prepend_jshead = '';

    /**
     * Boolean showing if jQuery is enabled
     *
     * @var boolean
     * @access private
     */
    private $_jquery_enabled = false;

    private $_jquery_init_scripts = '';

    /**
     * Array with all JQuery state scripts for the page's head.
     *
     * @var array
     * @access private
     */
    private $_jquery_states = array();

    /**
     * Array with all linked URLs for HEAD.
     *
     * @var Array
     * @access private
     */
    private $_linkhrefs = array();

    /**
     * Array with all methods for the BODY's onload event.
     *
     * @var Array
     * @access private
     */
    private $_jsonload = array();

    /**
     * string with all metatags to go into the page head.
     * @var string
     * @access private
     */
    private $_meta_head = '';

    /**
     * string with all object tags to go into a page's head.
     * @var string
     * @access private
     */
    private $_object_head = '';

    /**
     * String with all css styles to go into a page's head.
     *
     * @var string
     * @access private
     */
    private $_style_head = '';

    /**
     * String with all link elements to be included in a page's head.
     *
     * @var string
     * @access private
     */
    private $_link_head = '';

    /**
     * Host prefix cache to avoid computing it each time.
     *
     * @var string
     * @access private
     * @see get_host_prefix()
     */
    private $_cached_host_prefix = '';

    /**
     * Page prefix cache to avoid computing it each time.
     *
     * @var string
     * @access private
     * @see get_page_prefix()
     */
    private $_cached_page_prefix = '';

    /**
     * Host name cache to avoid computing it each time.
     *
     * @var string
     * @access private
     * @see get_host_name()
     */
    private $_cached_host_name = '';

    /**
     * Set this variable to true during the handle phase of your component to
     * not show the site's style around the component output. This is mainly
     * targeted at XML output like RSS feeds and similar things. The output
     * handler of the site, excluding the style-init/-finish tags will be executed
     * immediately after the handle phase, and midcom->finish() is called
     * automatically afterwards, thus ending the request.
     *
     * Changing this flag after the handle phase or for dynamically loaded
     * components won't change anything.
     *
     * @var boolean
     * @access public
     */
    var $skip_page_style = false;

    /**
     * Main MidCOM initialization.
     *
     * Note, that there is no constructor so that initialize can already populate global references.
     *
     * Initialize the Application class. Sets all private variables to a predefined
     * state. $node should be set to the midcom root-node GUID.
     * $prefix can be a prefix, which is appended to midcom_connection::get_url('self') (i.e. the
     * Midgard Page URL). This may be needed when MidCOM is run by wrapper.
     */
    public function initialize()
    {
        // set prefix for "new" midgard->self
        $this->_prefix = $GLOBALS['midcom_config']['midcom_prefix'];

        $this->_status = MIDCOM_STATUS_PREPARE;

        // Start-up some of the services
        $this->dbclassloader->load_classes('midcom', 'legacy_classes.inc', null, true);
        $this->dbclassloader->load_classes('midcom', 'core_classes.inc', null, true);

        $this->componentloader->load_all_manifests();

        // Initialize Root Topic
        $root_node = midcom_db_topic::get_cached($GLOBALS['midcom_config']['midcom_root_topic_guid']);

        if (!$root_node->guid)
        {
            if (midcom_connection::get_error() == MGD_ERR_ACCESS_DENIED)
            {
                $this->generate_error(MIDCOM_ERRFORBIDDEN,
                    $this->i18n->get_string('access denied', 'midcom'));
            }
            else
            {
                // Fall back to another MidCOM topic so that admin has a chance to fix this
                $_MIDCOM->auth->require_admin_user("Root folder is misconfigured. Please log in as administrator and fix this in settings.");
                $qb = midcom_db_topic::new_query_builder();
                $qb->add_constraint('up', '=', 0);
                $qb->add_constraint('component', '<>', '');
                $topics = $qb->execute();
                if (count($topics) == 0)
                {
                    $this->generate_error(MIDCOM_ERRCRIT,
                        "Fatal error: Unable to load website root folder with GUID '{$GLOBALS['midcom_config']['midcom_root_topic_guid']}'.<br />" .
                        'Last Midgard Error was: ' . midcom_connection::get_error_string());
                    // This will exit.
                }
                $root_node = $topics[0];
            }
        }
        // Initialize Context Storage
        $this->_currentcontext = $this->_create_context(0, $root_node);

        // Populate browser information
        $this->_populate_client();

        // Check the midcom_config site prefix for absolute local urls
        if ($GLOBALS['midcom_config']['midcom_site_url'][0] == '/')
        {
            $GLOBALS['midcom_config']['midcom_site_url'] =
                $this->get_page_prefix()
                . substr($GLOBALS['midcom_config']['midcom_site_url'], 1);
        }
    }

    /**
     * Magic getter for service loading
     */
    public function __get($key)
    {
        if (!isset($this->_service_classes[$key]))
        {
            return;
        }

        $service_class = $this->_service_classes[$key];
        $this->$key = new $service_class;
        $this->_services[$key] = $this->$key;
        return $this->$key;
    }

    /* *************************************************************************
     * Main Application control framework:
     * start_services - Starts all available services
     * code-init      - Handle the current request
     * content        - Show the current pages output
     * dynamic_load   - Dynamically load and execute a URL
     * finish         - Cleanup Work
     */

    /**
     * Initialize the URL parser and process the request.
     *
     * This function must be called before any output starts.
     *
     * @see _process()
     */
    public function codeinit()
    {
        if ($this->get_current_context() == 0)
        {
            // Initialize the UI message stack from session
            $this->uimessages->initialize();
        }

        // Parse the URL
        $this->_parsers[$this->_currentcontext] = $this->serviceloader->load('midcom_core_service_urlparser');
        $this->_parsers[$this->_currentcontext]->parse(midcom_connection::get_url('argv'));

        if (!$this->_parsers[$this->_currentcontext])
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add('URL Parser is not instantiated, bailing out.', MIDCOM_LOG_ERROR);
            debug_pop();
            $this->generate_error(MIDCOM_ERRCRIT, $GLOBALS['midcom_errstr']);
        }

        $this->_process();

        if ($this->get_current_context() == 0)
        {
            // Let metadata service add its meta tags
            $this->metadata->populate_meta_head();
        }
    }

    /**
     * Display the output of the component
     *
     * This function must be called in the content area of the
     * Style template, usually <(content)>.
     */
    public function content()
    {
        // Enter Context
        $oldcontext = $this->_currentcontext;
        if ($oldcontext != 0)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Entering Context 0 (old Context: {$this->_currentcontext})", MIDCOM_LOG_DEBUG);
            debug_pop();
        }
        $this->_currentcontext = 0;
        $this->style->enter_context(0);

        $this->_output();

        // Leave Context
        if ($oldcontext != 0)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Leaving Context 0 (new Context: {$oldcontext})", MIDCOM_LOG_DEBUG);
            debug_pop();
        }

        $this->style->leave_context();
        $this->_currentcontext = $oldcontext;
    }

    /**
     * Dynamically execute a subrequest and insert its output in place of the
     * function call.
     *
     * <b>Important Note</b> As with the Midgard Parser, dynamic_load strips a
     * trailing .html from the argument list before actually parsing it.
     *
     * Under MIDCOM_REQUEST_CONTENT it tries to load the component referenced with
     * the URL $url and executes it as if it was used as primary component.
     * Additional configuration parameters can be appended through the parameter
     * $config.
     *
     * This is only possible if the system is in the Page-Style output phase. It
     * cannot be used within code-init or during the output phase of another
     * component.
     *
     * Setting MIDCOM_REQUEST_CONTENTADM loads the content administration interface
     * of the component. The semantics is the same as for any other MidCOM run with
     * the following exceptions:
     *
     * - This function can (and usually will be) called during the content output phase
     *   of the system.
     * - A call to generate_error will result in a regular error page output if
     *   we still are in the code-init phase.
     *
     * Example code, executed on a sites Homepage, it will load the news listing from
     * the given URL and display it using a substyle of the node style that is assigned
     * to the loaded one:
     *
     * <code>
     * $blog = '/blog/latest/3/';
     * $substyle = 'homepage';
     * $_MIDCOM->dynamic_load("/midcom-substyle-{$substyle}/{$blog}");
     * </code>
     *
     * <B>Danger, Will Robinson:</b>
     *
     * Be aware, that the call to another component will most certainly overwrite global
     * variables that you are currently using. A common mistake is this:
     *
     * <code>
     * global $view;
     * $_MIDCOM->dynamic_load($view['url1']);
     * // You will most probably fail, could even loop infinitely!
     * $_MIDCOM->dynamic_load($view['url2']);
     * </code>
     *
     * The reason why this usually fails is, that the $view you have been using during
     * the first call was overwritten by the other component during it, $view['url2']
     * is now empty. If you are now on the homepage, the homepage would start loading
     * itself again and again.
     *
     * Therefore, be sure to save the variables locally (remember, the style invocation
     * is in function context):
     *
     * <code>
     * $view = $GLOBALS['view'];
     * $_MIDCOM->dynamic_load($view['url1']);
     * $_MIDCOM->dynamic_load($view['url2']);
     * </code>
     *
     * Results of dynamic_loads are cached, by default with the system cache strategy
     * but you can specify separate cache strategy for the DL in the config array like so
     * <code>
     * $_MIDCOM->dynamic_load("/midcom-substyle-{$substyle}/{$newsticker}", array('cache_module_content_caching_strategy' => 'public'))
     * </code>
     *
     * You can use only less specific strategy than the global strategy, ie basically you're limited to 'memberships' and 'public' as
     * values if the global strategy is 'user' and to 'public' the global strategy is 'memberships', failure to adhere to this
     * rule will result to weird cache behavior.
     *
     * @param string $url                The URL, relative to the Midgard Page, that is to be requested.
     * @param Array $config              A key=>value array with any configuration overrides.
     * @param int $type                  Request type (by default MIDCOM_REQUEST_CONTENT)
     * @return int                       The ID of the newly created context.
     */
    public function dynamic_load($url, $config = array(), $type = MIDCOM_REQUEST_CONTENT, $pass_get = false)
    {
        debug_push_class(__CLASS__, __FUNCTION__);

        debug_add("Dynamic load of URL {$url}", MIDCOM_LOG_DEBUG);

        if (substr($url, -5) == '.html')
        {
            $url = substr($url, 0, -5);
        }

        if (   $type == MIDCOM_REQUEST_CONTENT
            && $this->_status < MIDCOM_STATUS_CONTENT)
        {
            debug_add("dynamic_load content request called before content output phase. Aborting.", MIDCOM_LOG_ERROR);
            debug_pop();
            $this->generate_error(MIDCOM_ERRCRIT, "dynamic_load content request called before content output phase.");
            // This will exit
        }

        // Determine new Context ID and set $this->_currentcontext,
        // enter that context and prepare its data structure.
        $context = $this->_create_context(null, $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ROOTTOPIC));

        if ($pass_get)
        {
            // Include GET parameters into cache URL
            $this->_set_context_data(midcom_connection::get_url('self') . $url . '?GET=' . serialize($_GET), $context, MIDCOM_CONTEXT_URI);
        }
        else
        {
            $this->_set_context_data(midcom_connection::get_url('self') . $url, $context, MIDCOM_CONTEXT_URI);
        }

        $oldcontext = $this->_currentcontext;
        $this->_currentcontext = $context;

        /* "content-cache" for DLs, check_hit */
        if ($this->cache->content->check_dl_hit($context, $config))
        {
            // The check_hit method serves cached content on hit
            $this->_currentcontext = $oldcontext;
            debug_pop();
            return $context;
        }

        // Parser Init: Generate arguments and instantiate it.
        $this->_parsers[$this->_currentcontext] = $this->serviceloader->load('midcom_core_service_urlparser');
        $argv = $this->_parsers[$this->_currentcontext]->tokenize($url);
        $this->_parsers[$this->_currentcontext]->parse($argv);

        if (!$this->_parsers[$this->_currentcontext])
        {
            debug_add("URL Parser could not be instantiated: $midcom_errstr", MIDCOM_LOG_ERROR);
            debug_pop();
            $this->generate_error(MIDCOM_ERRCRIT, "URL Parser could not be instantiated: {$GLOBALS['midcom_errstr']}");
        }

        // Processing, upon error the generate_error function will die here...
        $this->_process();

        if ($this->_status == MIDCOM_STATUS_ABORT)
        {
            debug_add("Dynamic load _process() phase ended up with 404 Error. Aborting...", MIDCOM_LOG_ERROR);
            debug_pop();

            // Leave Context
            $this->_currentcontext = $oldcontext;

            return;
        }

        // Start another buffer for caching DL results
        ob_start();

        // If MIDCOM_REQUEST_CONTENT: Tell Style to enter Context
        if ($type == MIDCOM_REQUEST_CONTENT)
        {
            $this->style->enter_context($context);
            debug_add("Entering Context $context (old Context: $oldcontext)", MIDCOM_LOG_DEBUG);
        }

        $this->_output();

        // If MIDCOM_REQUEST_CONTENT: Tell Style to leave Context
        if ($type == MIDCOM_REQUEST_CONTENT)
        {
            $this->style->leave_context();
            debug_add("Leaving Context $context (new Context: $oldcontext)", MIDCOM_LOG_DEBUG);
        }

        $dl_cache_data = ob_get_contents();
        ob_end_flush();
        /* Cache DL the content */
        $this->cache->content->store_dl_content($context, $config, $dl_cache_data);
        unset($dl_cache_data);

        // Leave Context
        $this->_currentcontext = $oldcontext;

        debug_pop();
        return $context;
    }

    /**
     * Exit from the framework, execute after all output has been made.
     *
     * Does all necessary clean-up work. Must be called after output is completed as
     * the last call of any MidCOM Page. Best Practice: call it at the end of the ROOT
     * style element.
     *
     * <b>WARNING:</b> Anything done after calling this method will be lost.
     */
    public function finish()
    {
        $this->_status = MIDCOM_STATUS_CLEANUP;

        // Shutdown content-cache (ie flush content to user :) before possibly slow DBA watches
        // done this way since it's slightly less hacky than calling shutdown and then mucking about with the cache->_modules etc
        $this->cache->content->_finish_caching();

        $this->componentloader->process_pending_notifies();

        // Store any unshown messages
        $this->uimessages->store();

        if ($GLOBALS['midcom_config']['enable_included_list'])
        {
            $included = get_included_files();
            echo "<p>" . count($included) . " included files:</p>\n";
            echo "<ul>\n";
            foreach ($included as $filename)
            {
                echo "<li>{$filename}</li>\n";
            }
            echo "</ul>\n";
        }

        // Shutdown rest of the caches
        $this->cache->shutdown();

        // This is here to avoid trouble with end-of-processing segfaults. Will block AFAIK
        flush();
        debug_add("End of MidCOM run: {$_SERVER['REQUEST_URI']}", MIDCOM_LOG_DEBUG);
    }


    /* *************************************************************************
     * Component Invocation Helper Functions:
     *
     * _process               - CANHANDLE->HANDLE || ATTACHMENT_OUTPUT
     * _can_handle           - CANHANDLE
     * _loadconfig            - CANHANDLE
     * _handle                - HANDLE
     * _output                - OUTPUT
     */


    /**
     * Process the request
     *
     * Basically this method will parse the URL and search for a component that can
     * handle the request. If one is found, it will process the request, if not, it
     * will report an error, depending on the situation.
     *
     * Details: The logic will traverse the node tree and for each node it will load
     * the component that is responsible for it. This component gets the chance to
     * accept the request (this is encapsulated in the _can_handle call), which is
     * basically a call to can_handle. If the component declares to be able to handle
     * the call, its handle function is executed. Depending if the handle was successful
     * or not, it will either display an HTTP error page or prepares the content handler
     * to display the content later on.
     *
     * If the parsing process doesn't find any component that declares to be able to
     * handle the request, an HTTP 404 - Not Found error is triggered.
     *
     * @access private
     */
    private function _process()
    {
        $success = false;
        $substyle = "";

        while (($tmp = $this->_parsers[$this->_currentcontext]->get_variable('midcom')) !== false)
        {
            foreach ($tmp as $key => $value)
            {
                switch ($key)
                {
                    case 'substyle':
                        $substyle = $value;
                        debug_push_class(__CLASS__, __FUNCTION__);
                        debug_add("Substyle '$substyle' selected", MIDCOM_LOG_DEBUG);
                        debug_pop();
                        break;

                    case 'serveattachmentguid':
                    case 'serveattachment':
                        if ($this->_parsers[$this->_currentcontext]->argc > 1)
                        {
                            debug_push_class(__CLASS__, __FUNCTION__);
                            debug_add('Too many arguments remaining for serve_attachment.', MIDCOM_LOG_ERROR);
                            debug_pop();
                        }

                        $attachment = new midcom_db_attachment($value);
                        if (   !$attachment
                            && !$attachment->guid)
                        {
                            $this->generate_error(MIDCOM_ERRNOTFOUND, 'Failed to access attachment: ' . midcom_connection::get_error_string());
                        }

                        if (!$attachment->can_do('midgard:autoserve_attachment'))
                        {
                            $this->generate_error(MIDCOM_ERRNOTFOUND, 'Failed to access attachment: Autoserving denied.');
                        }

                        $this->serve_attachment($attachment);
                        $this->finish();
                        _midcom_stop_request();

                    case 'permalink':
                        $guid = $value;
                        $destination = $this->permalinks->resolve_permalink($guid);
                        if ($destination === null)
                        {
                            $this->generate_error(MIDCOM_ERRNOTFOUND, "This Permalink is unknown.");
                            // This will exit
                        }

                        // We use "302 Found" here so that search engines and others will keep using the PermaLink instead of the temporary
                        $this->header("Location: {$destination}", 302);
                        $this->finish();
                        _midcom_stop_request();

                    case 'cache':
                        if ($value == 'invalidate')
                        {
                            if (   empty($GLOBALS['midcom_config']['indexer_reindex_allowed_ips'])
                                || !in_array($_SERVER['REMOTE_ADDR'], $GLOBALS['midcom_config']['indexer_reindex_allowed_ips']))
                            {
                                $_MIDCOM->auth->require_valid_user('basic');
                                $_MIDCOM->auth->require_admin_user();
                            }
                            $this->cache->content->enable_live_mode();
                            $this->cache->invalidate_all();
                            $this->uimessages->add($_MIDCOM->i18n->get_string('MidCOM', 'midcom'), "Cache invalidation successful.", 'info');
                            $_MIDCOM->relocate('');
                        }
                        else if ($value == 'nocache')
                        {
                            $this->cache->content->no_cache();
                        }
                        else
                        {
                            $this->generate_error(MIDCOM_ERRNOTFOUND, "Invalid cache request URL.");
                            // This will exit
                        }
                        break;

                    case "logout":
                        // rest of URL used as redirect
                        $remaining_url = false;
                        if (   !empty($tmp['logout'])
                            || !empty($this->_parsers[$this->_currentcontext]->argv))
                        {
                            $remaining_url = $tmp['logout'] . '/' ;
                            $remaining_url .= implode($this->_parsers[$this->_currentcontext]->argv, '/');
                            $remaining_url = preg_replace('%^(.*?):/([^/])%', '\\1://\\2', $remaining_url);
                        }
                        if (is_string($remaining_url))
                        {
                            $redirect_to = $remaining_url;
                        }
                        else
                        {
                            $redirect_to = '';
                        }
                        if (   isset($_SERVER['QUERY_STRING'])
                            && !empty($_SERVER['QUERY_STRING']))
                        {
                            $redirect_to .= "?{$_SERVER['QUERY_STRING']}";
                        }
                        $this->cache->content->no_cache();
                        $this->auth->logout($redirect_to);
                        // This will exit

                    case "login":
                        // rest of URL used as redirect
                        $remaining_url = false;
                        if (   !empty($tmp['login'])
                            || !empty($this->_parsers[$this->_currentcontext]->argv))
                        {
                            $remaining_url = "{$tmp['login']}/" . implode($this->_parsers[$this->_currentcontext]->argv, '/');
                            $remaining_url = preg_replace('%^(.*?):/([^/])%', '\\1://\\2', $remaining_url);
                        }
                        if (is_string($remaining_url))
                        {
                            $redirect_to = $remaining_url;
                        }
                        else
                        {
                            $redirect_to = '';
                        }
                        if (   isset($_SERVER['QUERY_STRING'])
                            && !empty($_SERVER['QUERY_STRING']))
                        {
                            $redirect_to .= "?{$_SERVER['QUERY_STRING']}";
                        }
                        if ($this->auth->is_valid_user())
                        {
                            $this->relocate($redirect_to);
                            // This will exit
                        }
                        $this->auth->show_login_page();
                        // This will exit too;

                    case 'exec':
                        $this->_exec_file($value);
                        // This will exit

                    case 'log':
                        if ($this->_parsers[$this->_currentcontext]->argc > 1)
                        {
                            debug_push_class(__CLASS__, __FUNCTION__);
                            debug_add("Too many arguments remaining for debuglog.", MIDCOM_LOG_ERROR);
                            debug_pop();
                            $this->generate_error(MIDCOM_ERRNOTFOUND, "Failed to access debug log: Too many arguments for debuglog");
                            // This will exit
                        }
                        $this->_showdebuglog($value);
                        break;

                    case 'servejscsscache':
                        $name = $this->_parsers[$this->_currentcontext]->argv[0];
                        if (   !$this->jscss
                            || !is_callable(array($this->jscss, 'serve')))
                        {
                            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, 'Cache is not initialized');
                            // this will exit
                        }
                        $this->jscss->serve($name);
                        // this will exit()

                    default:
                        debug_add("Unknown MidCOM URL Property ignored: {$key} => {$value}", MIDCOM_LOG_WARN);
                        $_MIDCOM->generate_error(MIDCOM_ERRNOTFOUND, "This MidCOM URL method is unknown.");
                        // This will exit.
                }
            }
        }

        $this->_status = MIDCOM_STATUS_CANHANDLE;

        do
        {
            $object = $this->_parsers[$this->_currentcontext]->get_current_object();
            if (   !is_object($object)
                || !$object->guid)
            {
                debug_push_class(__CLASS__, __FUNCTION__);
                debug_add('Root node missing.', MIDCOM_LOG_ERROR);
                debug_pop();
                $this->generate_error(MIDCOM_ERRCRIT, 'Root node missing.');
            }

            if (is_a($object, 'midcom_db_attachment'))
            {
                $this->serve_attachment($object);
            }

            $path = $object->component;
            if (!$path)
            {
                $path = 'midcom.core.nullcomponent';
                debug_push_class(__CLASS__, __FUNCTION__);
                debug_add("No component defined for this node, using 'midcom.core.nullcomponent' instead.", MIDCOM_LOG_INFO);
                debug_pop();
            }

            $this->_set_context_data($path,MIDCOM_CONTEXT_COMPONENT);

            // Check whether the component can handle the request.
            // If so, execute it, if not, continue.
            if ($this->_can_handle($object))
            {
                $this->_status = MIDCOM_STATUS_HANDLE;

                $prefix = $this->_parsers[$this->_currentcontext]->get_url();

                // Initialize context
                $this->_context[$this->_currentcontext][MIDCOM_CONTEXT_ANCHORPREFIX] = $prefix;
                $this->_context[$this->_currentcontext][MIDCOM_CONTEXT_SUBSTYLE] = $substyle;

                $this->_handle( $this->get_context_data( MIDCOM_CONTEXT_COMPONENT ) );

                $success = true;
                break;
            }

        } while ($this->_parsers[$this->_currentcontext]->get_object() !== false);

        if (! $success)
        {
            // We couldn't fetch a node due to access restrictions. Fall only for real pages. Ignore dynamic loads.
            if (   midcom_connection::get_error() == MGD_ERR_ACCESS_DENIED
                && $this->get_current_context() == 0)
            {
                $this->generate_error(MIDCOM_ERRFORBIDDEN, $this->i18n->get_string('access denied', 'midcom'));
                // This will exit.
            }

            /**
             * Simple: if current context is not '0' we were called from another context.
             * If so we should not break application now - just gracefully continue.
             */

            if ($this->get_current_context() == 0)
            {
                $this->generate_error(MIDCOM_ERRNOTFOUND, "This page is not available on this server.");
            }

            $this->_status = MIDCOM_STATUS_ABORT;
            debug_pop();
            return false;// This will exit.
        }

        if (   $this->_currentcontext == 0
            && $this->skip_page_style == true)
        {
            $this->_status = MIDCOM_STATUS_CONTENT;

            // Enter Context
            $oldcontext = $this->_currentcontext;
            $this->_currentcontext = 0;
            $this->style->enter_context(0);

            $this->_output();

            // Leave Context
            $this->style->leave_context();
            $this->_currentcontext = $oldcontext;

            $this->finish();
            _midcom_stop_request();
        }
        else
        {
            $this->_status = MIDCOM_STATUS_CONTENT;
        }
    }

    /**
     * Handle the request.
     *
     * _handle is called after _can_handle determined, that
     * a component can handle a request. The URL of the component that is used
     * to handle the request is obtained automatically. The parameter $path is
     * optional and reserved for future usage. It will fetch the required COMPONENT class
     *
     * from the Component Loader and instruct it to handle a request. If the handler
     * hook returns false (i.e. handling failed), it will produce an Errorpage
     * according to the error code and -string of the component in question.
     *
     * @access private
     */
    private function _handle()
    {
        debug_push_class(__CLASS__, __FUNCTION__);

        $path = $this->get_context_data(MIDCOM_CONTEXT_COMPONENT);

        if ($this->get_context_data(MIDCOM_CONTEXT_REQUESTTYPE) != MIDCOM_REQUEST_CONTENT)
        {
            debug_add("Unknown Request Type encountered:" . $this->_context[$this->current_context][MIDCOM_CONTEXT_REQUESTTYPE], MIDCOM_LOG_ERROR);
            $this->generate_error(MIDCOM_ERRCRIT, "Unknown Request Type encountered:"  . $this->_context[$this->current_context][MIDCOM_CONTEXT_REQUESTTYPE]);
        }

        $handler = $this->componentloader->get_interface_class($path);

        $this->_set_context_data($this->_parsers[$this->_currentcontext]->get_current_object(), MIDCOM_CONTEXT_CONTENTTOPIC);
        $this->_set_context_data($this->_parsers[$this->_currentcontext]->get_objects(), MIDCOM_CONTEXT_URLTOPICS);

        if (!$handler->handle($this->_parsers[$this->_currentcontext]->get_current_object(), $this->_parsers[$this->_currentcontext]->argc, $this->_parsers[$this->_currentcontext]->argv, $this->_currentcontext))
        {
            $this->generate_error(MIDCOM_ERRCRIT, "Component $path failed to handle the request");

            // This will exit.
        }

        // Retrieve Metadata
        $nav = new midcom_helper_nav();
        if ($nav->get_current_leaf() === false)
        {
            $meta = $nav->get_node($nav->get_current_node());
        }
        else
        {
            $meta = $nav->get_leaf($nav->get_current_leaf());
        }

        if ($this->_context[$this->_currentcontext][MIDCOM_CONTEXT_PERMALINKGUID] === null)
        {
            $this->_context[$this->_currentcontext][MIDCOM_CONTEXT_PERMALINKGUID] = $meta[MIDCOM_NAV_GUID];
        }

        if ($this->_context[$this->_currentcontext][MIDCOM_CONTEXT_PAGETITLE] == '')
        {
            $this->_context[$this->_currentcontext][MIDCOM_CONTEXT_PAGETITLE] = $meta[MIDCOM_NAV_NAME];
        }

        debug_pop();
    }

    /**
     * Check, whether a given component is able to handle the current request.
     *
     * Used by _process(), it checks if the component associated to $object is able
     * to handle the request. First it will load the component associated to $object.
     * Then it will fetch the COMPONENT class associated to the MidCOM. After the
     * local configuration is retrieved from the object in question the component will
     * be asked, if it can handle the request. TRUE or FALSE will be returned
     * accordingly, both on the configure and on the can_handle run.
     *
     * @param midcom_db_topic $object    The node that is currently being tested.
     * @return boolean                    Indication, whether a component can handle a request.
     * @access private
     */
    private function _can_handle($object)
    {
        $path = $this->get_context_data(MIDCOM_CONTEXT_COMPONENT);

        // Request type safety check
        if ($this->_context[$this->_currentcontext][MIDCOM_CONTEXT_REQUESTTYPE] != MIDCOM_REQUEST_CONTENT)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Unknown Request Type encountered:" . $this->_context[$this->current_context][MIDCOM_CONTEXT_REQUESTTYPE], MIDCOM_LOG_ERROR);
            debug_pop();
            $this->generate_error(MIDCOM_ERRCRIT, "Unknown Request Type encountered:" . $this->_context[$this->current_context][MIDCOM_CONTEXT_REQUESTTYPE]);
        }

        // Get component interface class
        $component_interface = $this->componentloader->get_interface_class($path);
        if ($component_interface === null)
        {
            $path = 'midcom.core.nullcomponent';
            $this->_set_context_data($path, MIDCOM_CONTEXT_COMPONENT);
            $component_interface = $this->componentloader->get_interface_class($path);
        }

        // Load configuration
        $config_obj = $this->_loadconfig($this->_currentcontext, $object);
        $config = ($config_obj == false) ? array() : $config_obj->get_all();
        if (! $component_interface->configure($config, $this->_currentcontext))
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add ("Component Configuration failed: " . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
            debug_pop();
            $this->generate_error(MIDCOM_ERRCRIT, "Component Configuration failed: " . midcom_connection::get_error_string());
        }

        // Make can_handle check
        if (!$component_interface->can_handle($object, $this->_parsers[$this->_currentcontext]->argc, $this->_parsers[$this->_currentcontext]->argv, $this->_currentcontext))
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Component {$path} in {$object->name} declared unable to handle request.", MIDCOM_LOG_INFO);
            debug_pop();
            return false;
        }

        return true;
    }

    /**
     * Load the configuration for a given object.
     *
     * This is a small wrapper function that retrieves all local configuration data
     * attached to $object. The assigned component is used to determine which
     * parameter domain has to be used.
     *
     * @param MidgardObject $object    The node from which to load the configuration.
     * @return midcom_helper_configuration    Reference to the newly constructed configuration object.
     * @access private
     */
    private function _loadconfig($context_id, $object)
    {
        static $configs = array();
        if (!isset($configs[$context_id]))
        {
            $configs[$context_id] = array();
        }

        $path = $this->get_context_data(MIDCOM_CONTEXT_COMPONENT);
        if (!isset($configs[$context_id][$object->guid]))
        {
            $configs[$context_id][$object->guid] = new midcom_helper_configuration($object, $path);
        }

        return $configs[$context_id][$object->guid];
    }

    /**
     * Execute the output callback.
     *
     * Launches the output of the currently selected component. If you set $showcontent
     * to FALSE the output concept will not be activated, only style-init and -finish
     * will be called.
     *
     * It executes the content_handler that has been determined during the handle
     * phase. It fetches the content_handler from the Component Loader class cache.
     *
     * This method always captures the output of the current run (except for context
     * 0) and stores it into the component context as MIDCOM_CONTEXT_OUTPUT. If
     * the current request is a content output request, it will automatically flush
     * the buffer to stdout, in all other cases you have to do this by yourself.
     *
     * @param boolean $showcontent    If set and false, the output will not be automatically flushed.
     * @access private
     */
    private function _output()
    {
        ob_start();

        if (!$this->skip_page_style)
        {
            midcom_show_style('style-init');
        }

        if ($this->get_context_data(MIDCOM_CONTEXT_REQUESTTYPE) != MIDCOM_REQUEST_CONTENT)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Unknown Request Type encountered:" . $this->_context[$this->current_context][MIDCOM_CONTEXT_REQUESTTYPE], MIDCOM_LOG_ERROR);
            debug_pop();
            $this->generate_error(MIDCOM_ERRCRIT, "Unknown Request Type encountered:" . $this->_context[$this->current_context][MIDCOM_CONTEXT_REQUESTTYPE]);
        }

        $component = $this->componentloader->get_interface_class($this->get_context_data(MIDCOM_CONTEXT_COMPONENT));
        $component->show_content($this->_currentcontext);

        if (!$this->skip_page_style)
        {
            midcom_show_style('style-finish');
        }

        if ($this->_currentcontext != 0)
        {
            $output = ob_get_contents();
            $this->_set_context_data($output, MIDCOM_CONTEXT_OUTPUT);
        }

        if ($this->get_context_data(MIDCOM_CONTEXT_REQUESTTYPE) == MIDCOM_REQUEST_CONTENT)
        {
            ob_end_flush();
        }
        else
        {
            ob_end_clean();
        }
    }

    /* *************************************************************************
     * Framework Access Helper functions
     */

    function generate_host_url($host)
    {
        if ($host->port == 443)
        {
            $protocol = 'https';
        }
        else
        {
            $protocol = 'http';
        }

        $port = '';
        if (   $host->port != 80
            && $host->port != 443
            && $host->port != 0)
        {
            $port = ':' . $host->port;
        }

        return "{$protocol}://{$host->name}{$port}{$host->prefix}/";
    }

    /**
     * Retrieves the name of the current host, fully qualified with protocol and
     * port.
     *
     * @return string Full Hostname (http[s]://www.my.domain.com[:1234])
     */
    function get_host_name()
    {
        if (! $this->_cached_host_name)
        {
            if (   array_key_exists("SSL_PROTOCOL", $_SERVER)
                ||
                (
                       array_key_exists('HTTPS', $_SERVER)
                    && $_SERVER['HTTPS'] == 'on'
                )
                || $_SERVER["SERVER_PORT"] == 443)
            {
                $protocol = "https";
            }
            else
            {
                $protocol = "http";
            }

            $port = "";
            if (strpos($_SERVER['SERVER_NAME'], ':') === false)
            {
                if ($protocol == "http" && $_SERVER["SERVER_PORT"] != 80)
                {
                    $port = ":" . $_SERVER["SERVER_PORT"];
                }
                else if ($protocol == "https" && $_SERVER["SERVER_PORT"] != 443)
                {
                    $port = ":" . $_SERVER["SERVER_PORT"];
                }
            }

            $this->_cached_host_name = "{$protocol}://{$_SERVER['SERVER_NAME']}{$port}";
        }

        return $this->_cached_host_name;
    }

    /**
     * Return the prefix required to build relative links on the current site.
     * This includes the http[s] prefix, the hosts port (if necessary) and the
     * base url of the Midgard Page. Be aware, that this does *not* point to the
     * base host of the site.
     *
     * e.g. something like http[s]://www.domain.com[:8080]/host_prefix/page_prefix/
     *
     * @return string The current MidCOM page URL prefix.
     */
    function get_page_prefix()
    {
        if (! $this->_cached_page_prefix)
        {
            $host_name = $this->get_host_name();
            $this->_cached_page_prefix = $host_name . midcom_connection::get_url('self');
        }

        return $this->_cached_page_prefix;
    }

    /**
     * Return the prefix required to build relative links on the current site.
     * This includes the http[s] prefix, the hosts port (if necessary) and the
     * base url of the main host. This is not necessarily the currently active
     * MidCOM Page however, use the get_page_prefix() function for that.
     *
     * e.g. something like http[s]://www.domain.com[:8080]/host_prefix/
     *
     * @return string The host's root page URL prefix.
     */
    function get_host_prefix()
    {
        if (! $this->_cached_host_prefix)
        {
            $host_name = $this->get_host_name();
            $host_prefix = midcom_connection::get_url('prefix');
            if ($host_prefix == '')
            {
                $host_prefix = '/';
            }
            else if ($host_prefix != '/')
            {
                if (substr($host_prefix, 0, 1) != '/')
                {
                    $host_prefix = "/{$host_prefix}";
                }
                if (substr($host_prefix, 0, -1) != '/')
                {
                    $host_prefix .= '/';
                }
            }
            $this->_cached_host_prefix = "{$host_name}{$host_prefix}";
        }

        return $this->_cached_host_prefix;
    }

    /**
     * Return the reference to the component loader.
     *
     * @return midcom_helper__componentloader The reference of the component loader in use.
     */
    public function get_component_loader()
    {
        return $this->componentloader;
    }

    /**
     * If the system is in the output phase (see above), the systemwide low-level
     * NAP interface can be accessed through this function. A reference is returned.
     *
     * This function maintains one NAP Class per context. Usually this is enough,
     * since you mostly will access it in context 0, the default. The problem is, that
     * this is not 100% efficient: If you instantiate two different NAP Classes in
     * different contexts both referring to the same root node, you will get two
     * different instances.
     *
     * If the system has not completed the can_handle phase, this method fails and
     * returns false.
     *
     * <b>Note:</b> Direct use of this function is discouraged, use the class
     * midcom_helper_nav instead.
     *
     * @param int $contextid    The ID of the context for which a NAP class is requested.
     * @return midcom_helper__basicnav&    A reference to the basicnav instance in the application cache.
     * @see midcom_helper_nav
     */
    function & get_basic_nav($contextid)
    {
        if (is_null($this->_context[$contextid][MIDCOM_CONTEXT_NAP]))
        {
            $this->_context[$contextid][MIDCOM_CONTEXT_NAP] = new midcom_helper__basicnav($contextid);
        }

        if ($this->_context[$contextid][MIDCOM_CONTEXT_NAP] === false)
        {
            $this->generate_error(MIDCOM_ERRCRIT,
                                  "Failed to create a NAP instance: " . $GLOBALS["midcom_errstr"]
                                  . "; see the debug log for details");
            /* This will exit */
        }

        return $this->_context[$contextid][MIDCOM_CONTEXT_NAP];
    }

    /**
     * Access the MidCOM component context
     *
     * Returns Component Context Information associated to the component with the
     * context ID $contextid identified by $key. Omitting $contextid will yield
     * the variable from the current context.
     *
     * If the context ID is invalid, false is returned and $midcom_errstr will be set
     * accordingly. Be sure to compare the data type with it, since a "0" will evaluate
     * to false if compared with "==" instead of "===".
     *
     * @param int param1    Either the ID of the context (two parameters) or the key requested (one parameter).
     * @param int param2    Either the key requested (two parameters) or null (one parameter, the default).
     * @return mixed    The content of the key being requested.
     */
    function get_context_data($param1, $param2 = null)
    {
        global $midcom_errstr;

        if (is_null($param2))
        {
            $contextid = $this->_currentcontext;
            $key = $param1;
        }
        else
        {
            $contextid = $param1;
            $key = $param2;
        }

        if (!is_array($this->_context))
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            $midcom_errstr = "Corrupted context data (should be array).";
            debug_add ($midcom_errstr, MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }

        if (!array_key_exists($contextid, $this->_context))
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            $midcom_errstr = "Requested Context ID $contextid invalid.";
            debug_add ($midcom_errstr, MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }

        if (!array_key_exists($key, $this->_context[$contextid]) || $key >= 1000)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            $midcom_errstr = "Requested Key ID $key invalid.";
            debug_add($midcom_errstr, MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }

        return $this->_context[$contextid][$key];
    }

    /**
     * Update the component context
     *
     * This function sets a variable of the current or the given component context.
     *
     * @param mixed $value    The value to be stored
     * @param int $param1    See get_context_data()
     * @param int $param2    See get_context_data()
     * @see get_context_data()
     * @access private
     */
    function _set_context_data($value, $param1, $param2 = null)
    {
        if (is_null($param2))
        {
            $contextid = $this->_currentcontext;
            $key = $param1;
        } else {
            $contextid = $param1;
            $key = $param2;
        }

        $this->_context[$contextid][$key] = $value;
    }

    /**
     * Store arbitrary, component-specific information in the component context
     *
     * This method allows you to get custom data to a given context.
     * The system will automatically associate this data with the component from the
     * currently active context. You cannot access the custom data of any other
     * component this way, it is private to the context. You may attach information
     * to other contexts, which will be associated with the current component, so
     * you have a clean namespace independently from which component or context you
     * are operating of. All calls honor references of passed data, so you can use
     * this for central controlling objects.
     *
     * Note, that if you are working from a library like the datamanager is, you
     * cannot override the component association done by the system. Instead you
     * should add your libraries name (like midcom.helper.datamanager) as a prefix,
     * separated by a dot. I know, that this is not really an elegant solution and
     * that it actually breaks with the encapsulation I want, but I don't have a
     * better solution yet.
     *
     * Be aware, that this function works by-reference instead of by-value.
     *
     * A complete example could look like this:
     *
     * <code>
     * class my_component_class_one {
     *     function init () {
     *         $_MIDCOM->set_custom_context_data('classone', $this);
     *     }
     * }
     *
     * class my_component_class_two {
     *        var one;
     *     function my_component_class_two () {
     *         $this->one =& $_MIDCOM->get_custom_context_data('classone');
     *     }
     * }
     * </code>
     *
     * A very important caveat of PHP references can be seen here: You must never give
     * a reference to $this outside of a class within a constructor. class_one uses an
     * init function therefore. See the PHP documentation for a few more details on
     * all this. Component authors can usually safely set this up at the beginning of the
     * can_handle() and/or handle() calls.
     *
     * Also, be careful with the references you use here, things like this can easily
     * get quite confusing.
     *
     * @param mixed $key        The key associated to the value.
     * @param mixed $value    The value to store. (This is stored by-reference!)
     * @param int $contextid    The context to associated this data with (defaults to the current context)
     * @see get_custom_context_data()
     */
    function set_custom_context_data ($key, &$value, $contextid = null) {
        if (is_null($contextid))
            $contextid = $this->_currentcontext;
        $component = $this->get_context_data(MIDCOM_CONTEXT_COMPONENT);
        $this->_context[$contextid][MIDCOM_CONTEXT_CUSTOMDATA][$component][$key] =& $value;
    }

    /**
     * Retrieve arbitrary, component-specific information in the component context
     *
     * The set call defaults to the current context, the get call's semantics are as
     * with get_context_data.
     *
     * Note, that if you are working from a library like the datamanager is, you
     * cannot override the component association done by the system. Instead you
     * should add your libraries name (like midcom.helper.datamanager) as a prefix,
     * separated by a dot. I know, that this is not really an elegant solution and
     * that it actually breaks with the encapsulation I want, but I don't have a
     * better solution yet.
     *
     * A complete example can be found with set_custom_context_data.
     *
     * @param int $param1    See get_context_data()
     * @param int $param2    See get_context_data()
     * @return mixed        The requested value, which is returned by Reference!
     * @see get_context_data()
     * @see set_custom_context_data()
     */
    function & get_custom_context_data($param1, $param2 = null)
    {
        global $midcom_errstr;

        if (is_null($param2))
        {
            $contextid = $this->_currentcontext;
            $key = $param1;
        }
        else
        {
            $contextid = $param1;
            $key = $param2;
        }

        $component = $this->get_context_data(MIDCOM_CONTEXT_COMPONENT);

        if (!array_key_exists($contextid, $this->_context))
        {
            debug_push("midcom_application::get_custom_context_data");
            $midcom_errstr = "Requested Context ID $contextid invalid.";
            debug_add ($midcom_errstr, MIDCOM_LOG_ERROR);
            debug_pop();
            $result = false;
            return $result;
        }

        if (   !array_key_exists($component, $this->_context[$contextid][MIDCOM_CONTEXT_CUSTOMDATA])
            || !array_key_exists($key, $this->_context[$contextid][MIDCOM_CONTEXT_CUSTOMDATA][$component]))
        {
            $midcom_errstr = "Requested Key ID {$key} for the component {$component} is invalid.";
            $result = false;
            return $result;
        }

        return $this->_context[$contextid][MIDCOM_CONTEXT_CUSTOMDATA][$component][$key];

    }

    /**
     * Returns the ID of the currently active context. This is FALSE if there is no
     * context running.
     *
     * @return int The context ID.
     */
    function get_current_context ()
    {
        return $this->_currentcontext;
    }

    /**
     * Appends a substyle after the currently selected component style.
     *
     * Appends a substyle after the currently selected component style, effectively
     * enabling a depth of more then one style during substyle selection. This is only
     * effective if done during the handle phase of the component and allows the
     * component. The currently selected substyle therefore is now searched one level
     * deeper below "subStyle".
     *
     * The system must have completed the CAN_HANDLE Phase before this function will
     * be available.
     *
     * @param string $newsub The substyle to append.
     * @see substyle_prepend()
     */
    function substyle_append ($newsub)
    {
        // Make sure try to use only the first argument if we get space separated list, fixes #1788
        if (strpos($newsub, ' ') !== false)
        {
            list ($newsub, $ignore) = explode(' ', $newsub, 2);
            unset($ignore);
        }
        if ($this->_status < MIDCOM_STATUS_HANDLE)
        {
            $this->generate_error(MIDCOM_ERRCRIT, "Cannot do a substyle_append before the HANDLE phase.");
        }

        $current_style = $this->_context[$this->_currentcontext][MIDCOM_CONTEXT_SUBSTYLE];

        if (strlen($current_style) > 0)
        {
            $newsub = $current_style . '/' . $newsub;
        }

        $this->_context[$this->_currentcontext][MIDCOM_CONTEXT_SUBSTYLE] = $newsub;
    }

    /**
     * Prepends a substyle before the currently selected component style.
     *
     * Prepends a substyle before the currently selected component style, effectively
     * enabling a depth of more then one style during substyle selection. This is only
     * effective if done during the handle phase of the component and allows the
     * component. The currently selected substyle therefore is now searched one level
     * deeper below "subStyle".
     *
     * The system must have completed the CAN_HANDLE Phase before this function will
     * be available.
     *
     * @param string $newsub The substyle to prepend.
     * @see substyle_append()
     */
    function substyle_prepend($newsub)
    {
        if ($this->_status < MIDCOM_STATUS_HANDLE) {
            $this->generate_error(MIDCOM_ERRCRIT, "Cannot do a substyle_append before the HANDLE phase.");
        }

        debug_push("midcom_application::substyle_prepend");

        $current_style = $this->_context[$this->_currentcontext][MIDCOM_CONTEXT_SUBSTYLE];

        if (strlen($current_style) > 0)
            $newsub .= "/" . $current_style;

        debug_add("Updating Component Context Substyle from $current_style to $newsub", MIDCOM_LOG_DEBUG);

        $this->_context[$this->_currentcontext][MIDCOM_CONTEXT_SUBSTYLE] = $newsub;
        debug_pop();
    }

    /**
     * Load a code library
     *
     * This will load the pure-code library denoted by the MidCOM Path $path. It will
     * return true if the component truly was a pure-code library, false otherwise.
     * If the component loader cannot load the component, generate_error will be
     * called by it.
     *
     * Common example:
     *
     * <code>
     * $_MIDCOM->load_library('midcom.helper.datamanager');
     * </code>
     *
     * @param string $path    The name of the code library to load.
     * @return boolean            Indicates whether the library was successfully loaded.
     */
    function load_library($path)
    {
        if (! array_key_exists($path, $this->componentloader->manifests))
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Cannot load component {$path} as library, it is not installed.", MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }

        if (! $this->componentloader->manifests[$path]->purecode)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Cannot load component {$path} as library, it is a full-fledged component.", MIDCOM_LOG_ERROR);
            debug_print_r('Manifest:', $this->componentloader->manifests[$path]);
            debug_pop();
            return false;
        }

        $this->componentloader->load($path);

        return true;
    }

    /**
     * Populates the client status array that can be queried using the get_client()
     * method.
     *
     * @access private
     */
    function _populate_client ()
    {
        $this->_client = Array();

        if (!array_key_exists("HTTP_USER_AGENT",$_SERVER))
            $_SERVER["HTTP_USER_AGENT"] = "unspecified";

        if (stristr($_SERVER["HTTP_USER_AGENT"],"Gecko") !== false)
            $this->_client[MIDCOM_CLIENT_MOZILLA] = true;
        else
            $this->_client[MIDCOM_CLIENT_MOZILLA] = false;

        if (stristr($_SERVER["HTTP_USER_AGENT"],"MSIE") !== false)
            $this->_client[MIDCOM_CLIENT_IE] = true;
        else
            $this->_client[MIDCOM_CLIENT_IE] = false;

        if (stristr($_SERVER["HTTP_USER_AGENT"],"Mozilla/4") !== false &&
          stristr($_SERVER["HTTP_USER_AGENT"], "MSIE") === false)
            $this->_client[MIDCOM_CLIENT_NETSCAPE] = true;
        else
            $this->_client[MIDCOM_CLIENT_NETSCAPE] = false;

        if (stristr($_SERVER["HTTP_USER_AGENT"],"Opera") !== false) {
            $this->_client[MIDCOM_CLIENT_OPERA] = true;
            $this->_client[MIDCOM_CLIENT_IE] = false;
            $this->_client[MIDCOM_CLIENT_NETSCAPE] = false;
            $this->_client[MIDCOM_CLIENT_MOZILLA] = false;
        } else {
            $this->_client[MIDCOM_CLIENT_OPERA] = false;
        }

        if (stristr($_SERVER["HTTP_USER_AGENT"],"Win") !== false)
            $this->_client[MIDCOM_CLIENT_WIN] = true;
        else
            $this->_client[MIDCOM_CLIENT_WIN] = false;


        if (stristr($_SERVER["HTTP_USER_AGENT"],"X11") !== false ||

          stristr($_SERVER["HTTP_USER_AGENT"],"Linux"))
            $this->_client[MIDCOM_CLIENT_UNIX] = true;
        else
            $this->_client[MIDCOM_CLIENT_UNIX] = false;

        if (stristr($_SERVER["HTTP_USER_AGENT"],"Mac") !== false)
            $this->_client[MIDCOM_CLIENT_MAC] = true;
        else
            $this->_client[MIDCOM_CLIENT_MAC] = false;
    }

    /**
     * Returns the Client Status Array which gives you all available information about
     * the client accessing us.
     *
     * Currently incorporated is a recognition of client OS and client browser.
     *
     * <b>NOTE:</b> Be careful if you rely on this information, the system does not check
     * for invervening Proxies yet.
     *
     * <b>WARNING:</b> If the caching engine is running, you must not rely on this
     * information! You should set no_cache in these cases.
     *
     * @return Array    Key/Value Array with the client information (see MIDCOM_CLIENT_... constants)
     */
    function get_client()
    {
        return $this->_client;
    }

    /**
     * Sends a header out to the client.
     *
     * This function is syntactically identical to
     * the regular PHP header() function, but is integrated into the framework. Every
     * Header you sent must go through this function or it might be lost later on;
     * this is especially important with caching.
     *
     * @param string $header    The header to send.
     * @param integer $response_code HTTP response code to send with the header
     */
    public function header($header, $response_code = null)
    {
        $this->cache->content->register_sent_header($header);

        if (!is_null($response_code))
        {
            // Send the HTTP response code as requested, works on PHP 4.3.0+
            _midcom_header($header, true, $response_code);
        }
        else
        {
            _midcom_header($header);
        }
    }

    /**
     * Create and prepare a new component context.
     *
     * @param int $id Explicitly specify the ID for context creation (used during construction), this parameter is usually omitted.
     * @param MidgardObject $node Root node of the context
     * @return int The ID of the newly created component.
     * @access private
     */
    public function _create_context($id = null, $node = null)
    {
        if (is_null($id))
        {
            $id = count($this->_context);
        }
        $this->_context[$id] = Array();
        $this->_context[$id][MIDCOM_CONTEXT_ANCHORPREFIX] = '';
        $this->_context[$id][MIDCOM_CONTEXT_REQUESTTYPE] = MIDCOM_REQUEST_CONTENT;
        $this->_context[$id][MIDCOM_CONTEXT_URI] = $_SERVER['REQUEST_URI'];
        if (is_object($node))
        {
            $this->_context[$id][MIDCOM_CONTEXT_ROOTTOPIC] = $node;
            $this->_context[$id][MIDCOM_CONTEXT_ROOTTOPICID] = $node->id;
        }
        else
        {
            $this->_context[$id][MIDCOM_CONTEXT_ROOTTOPIC] = null;
            $this->_context[$id][MIDCOM_CONTEXT_ROOTTOPICID] = null;
        }
        $this->_context[$id][MIDCOM_CONTEXT_CONTENTTOPIC] = null;
        $this->_context[$id][MIDCOM_CONTEXT_COMPONENT] = null;
        $this->_context[$id][MIDCOM_CONTEXT_OUTPUT] = null;
        $this->_context[$id][MIDCOM_CONTEXT_NAP] = null;
        $this->_context[$id][MIDCOM_CONTEXT_PAGETITLE] = "";
        $this->_context[$id][MIDCOM_CONTEXT_LASTMODIFIED] = null;
        $this->_context[$id][MIDCOM_CONTEXT_PERMALINKGUID] = null;
        $this->_context[$id][MIDCOM_CONTEXT_CUSTOMDATA] = Array();
        $this->_context[$id][MIDCOM_CONTEXT_URLTOPICS] = Array();
        return $id;
    }

    /**
     * Sets a new context, doing some minor sanity checking.
     *
     * @return boolean    Indicating if the switch was successful.
     * @access private
     */
    function _set_current_context($id)
    {
        debug_push("midcom_application::_set_current_context");

        if ($id < 0 || $id >= count ($this->_context)) {
            debug_add("Could not switch to invalid context $id.", MIDCOM_LOG_WARN);
            debug_pop();
            return false;
        } else {
            debug_add("Setting active context to $id.", MIDCOM_LOG_DEBUG);
            $this->_currentcontext = $id;
            debug_pop();
            return true;
        }
    }

    /**
     * Get the current MidCOM processing state.
     *
     * @return int    One of the MIDCOM_STATUS_... constants indicating current state.
     */
    function get_status()
    {
        return $this->_status;
    }

    /**
     * Return a reference to a given service.
     *
     * Returns the MidCOM Object Service indicated by $name. If the service cannot be
     * found, an HTTP 500 is triggered.
     *
     * See the documentation of the various services for further details.
     *
     * @param string $name        The name of the service being requested.
     * @return mixed    A reference(!) to the service requested.
     */
    function get_service($name)
    {
        if (   $this->$name
            || isset($this->_service_classes[$name]))
        {
            return $this->$name;
        }

        debug_push_class(__CLASS__, __FUNCTION__);
        debug_add("Requested service '$name' is not available.", MIDCOM_LOG_ERROR);
        debug_pop();
        $this->generate_error(MIDCOM_ERRCRIT, "Requested service '$name' is not available.");
    }

    /**
     * Sets the page title for the current context.
     *
     * This can be retrieved by accessing the component context key
     * MIDCOM_CONTEXT_PAGETITLE.
     *
     * @param string $string    The title to set.
     */
    function set_pagetitle($string)
    {
        $this->_set_context_data($string, MIDCOM_CONTEXT_PAGETITLE);
    }


    /* *************************************************************************
     * Generic Helper Functions not directly related with MidCOM:
     *
     * generate_error     - Generate HTTP Error
     * serve_snippet      - Serves snippet including all necessary headers
     * serve_attachment   - Serves attachment including all necessary headers
     * _l10n_edit_wrapper - Invokes the l10n string editor
     * add_jsfile         - Add a JavaScript URL to the load queue
     * add_jscript        - Add JavaScript code to the load queue
     * add_jsonload       - Add a JavaScript method call to the bodies onload tag
     * add_object_head    - Add object links to the page's head.
     * add_style_head     - Add style  tags to the page's head.
     * add_meta_head      - Add metatags to the page's head.
     * print_head_elements     - Print the queued-up JavaScript code (for inclusion in the HEAD section)
     * print jsonload     - Prints the onload command if required (for inclusion as a BODY attribute)
     * check_memberships  - Checks whether the user is in a given group
     * relocate           - executes a HTTP relocation to the given URL
     * _showdebuglog      - internal helper for the debuglog URL method.
     */

    /**
     * Generate an error page.
     *
     * This function is a small helper, that will display a simple HTML Page reporting
     * the error described by $httpcode and $message. The $httpcode is also used to
     * send an appropriate HTTP Response.
     *
     * For a list of the allowed HTTP codes see the MIDCOM_ERR... constants
     *
     * <b>Note:</b> This function will call _midcom_stop_request() after it is finished.
     *
     * @see midcom_exception_handler::show()
     * @param int $httpcode        The error code to send.
     * @param string $message    The message to print.
     */
    public function generate_error($httpcode, $message)
    {
        static $error_shown = false;
        if ($error_shown)
        {
            return;
        }

        if (!class_exists('midcom_exception_handler'))
        {
            // Here we need the error handler, enabled or not
            require(MIDCOM_ROOT. '/errors.php');
        }
        $error_shown = true;
        $error_handler = new midcom_exception_handler();
        $error_handler->show($httpcode, $message);
        // This will exit
    }

    /**
     * Deliver a snippet to the client.
     *
     * This function is a copy of serve_attachment, but instead of serving attachments
     * it can serve the code field of an arbitrary snippet. There is no checking on
     * permissions done here, the callee has to ensure this. See the URL methods
     * servesnippet(guid) for details.
     *
     * Two parameters can be used to influence the behavior of this method:
     * "midcom/content-type" will set the content-type header sent with the code
     * field's content. If this is not set, application/octet-stream is used as a
     * default. "midcom/expire" is a count of seconds used for content expiration,
     * both for the HTTP headers and for the caching engine. If this is no valid
     * integer or less then or equal to zero or not set, the value is set to "1".
     *
     * The last modified header is created by using the revised timestamp of the
     * snippet.
     *
     * Remember to also set the parameter "midcom/allow_serve" to "true" to clear the
     * snippet for serving.
     *
     * @param MidgardSnippet &$snippet    The snippet that should be delivered to the client.
     */
    function serve_snippet (& $snippet)
    {
        if ($snippet->parameter("midcom", "allow_serve") != "true") {
            debug_add("This snippet may not be served.", MIDCOM_LOG_ERROR);
            $this->generate_error(MIDCOM_ERRFORBIDDEN, "This snippet may not be served.");
            // This will exit.
        }
        $content_type = $snippet->parameter("midcom", "content-type");
        if (! $content_type || $content_type == "")
        {
            $content_type = "application/octet-stream";
        }
        $expire = $snippet->parameter("midcom", "expire");
        if (! $expire || ! is_numeric($expire) || $expire < -1)
        {
            $expire = -1;
        }
        else
        {
            $expire = (int) $expire;
        }
        // This is necessary, as the internal date representation is not HTTP
        // standard compliant. :-(
        $lastmod = strtotime($snippet->revised);

        $this->header("Last-Modified: " . gmdate("D, d M Y H:i:s", $lastmod) . ' GMT');
        $this->header("Content-Length: " . strlen($snippet->code));
        $this->header("Accept-Ranges: none");
        $this->header("Content-Type: $content_type");
        $this->cache->content->content_type($content_type);

        // TODO: This should be made aware of the cache headers strategy for content cache module
        if ($expire > 0)
        {
            $this->header("Cache-Control: public max-age=$expires");
            $this->header("Expires: " . gmdate("D, d M Y H:i:s", (time()+$expire)) . " GMT" );
            $this->cache->content->expires(time()+$expire);
        }
        else if ($expire == 0)
        {
            $this->cache->content->no_cache();
        }
        echo $snippet->code;
    }

    /**
     * Deliver a blob to the client.
     *
     * This is a replacement for mgd_serve_attachment that should work around most of
     * its bugs: It is missing all important HTTP Headers concerning file size,
     * modification date and expiration. It will not call _midcom_stop_request() when it is finished,
     * you still have to do that yourself. It will add the following HTTP Headers:
     *
     * - Cache-Control: public max-age=$expires
     * - Expires: GMT Date $now+$expires
     * - Last-Modified: GMT Date of the last modified timestamp of the Attachment
     * - Content-Length: The Length of the Attachment in Bytes
     * - Accept-Ranges: none
     *
     * This should enable caching of browsers for Navigation images and so on. You can
     * influence the expiration of the served attachment with the parameter $expires.
     * It is the time in seconds till the client should refetch the file. The default
     * for this is 24 hours. If you set it to "0" caching will be prohibited by
     * changing the sent headers like this:
     *
     * - Pragma: no-cache
     * - Cache-Control: no-cache
     * - Expires: Current GMT Date
     *
     * If expires is set to -1, which is the default as of 2.0.0 (it was 86400 earlier),
     * no expires header gets sent.
     *
     * @param MidgardAttachment &$attachment    A reference to the attachment to be delivered.
     * @param int $expires HTTP-Expires timeout in seconds, set this to 0 for uncacheable pages, or to -1 for no Expire header.
     */
    function serve_attachment(& $attachment, $expires = -1)
    {
        if ($GLOBALS['midcom_config']['attachment_cache_enabled'])
        {
            $subdir = substr($attachment->guid, 0, 1);
            if (file_exists("{$GLOBALS['midcom_config']['attachment_cache_root']}/{$subdir}/{$attachment->guid}_{$attachment->name}"))
            {
                $this->relocate("{$GLOBALS['midcom_config']['attachment_cache_url']}/{$subdir}/{$attachment->guid}_{$attachment->name}", 301);
            }
        }

        // Sanity check expires
        if (   !is_int($expires)
            || $expires < -1)
        {
            $this->generate_error(MIDCOM_ERRCRIT, "\$expires has to be a positive integer or zero or -1, is now {$expires}.");
            // This will exit()
        }

        // Doublecheck that this is registered
        $this->cache->content->register($attachment->guid);
        $stats = $attachment->stat();
        $last_modified =& $stats[9];

        debug_push_class(__CLASS__, __FUNCTION__);

        $etag = md5("{$last_modified}{$attachment->name}{$attachment->mimetype}{$attachment->guid}");

        // Check etag and return 304 if necessary
        if (   $expires <> 0
            && $this->cache->content->_check_not_modified($last_modified, $etag))
        {
            if (!_midcom_headers_sent())
            {
                $this->cache->content->cache_control_headers();
                // Doublemakesure these are present
                $this->header('HTTP/1.0 304 Not Modified', 304);
                $this->header("ETag: {$etag}");
            }
            while(@ob_end_flush());
            debug_add("End of MidCOM run: {$_SERVER['REQUEST_URI']}", MIDCOM_LOG_DEBUG);
            debug_pop();
            _midcom_stop_request();
        }

        $f = $attachment->open('r');
        if (! $f)
        {
            debug_pop();
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, 'Failed to open attachment for reading: ' . midcom_connection::get_error_string());
            // This will exit()
        }

        $this->header("ETag: {$etag}");
        $this->cache->content->content_type($attachment->mimetype);
        $this->cache->content->register_sent_header("Content-Type: {$attachment->mimetype}");
        $this->header("Last-Modified: " . gmdate("D, d M Y H:i:s", $last_modified) . ' GMT');
        $this->header("Content-Length: " . $stats[7]);
        $this->header("Content-Description: {$attachment->title}");
        $this->cache->content->register_sent_header("Content-Description: {$attachment->title}");

        // PONDER: Support ranges ("continue download") somehow ?
        $this->header("Accept-Ranges: none");

        if ($expires > 0)
        {
            // If custom expiry now+expires is set use that
            $this->cache->content->expires(time()+$expires);
        }
        else if ($expires == 0)
        {
            // expires set to 0 means disable cache, so we shall
            $this->cache->content->no_cache();
        }
        // TODO: Check metadata service for the real expiry timestamp ?

        $this->cache->content->cache_control_headers();

        $send_att_body = true;
        if ($GLOBALS['midcom_config']['attachment_xsendfile_enable'])
        {
            $blob = new midgard_blob($attachment->__object);
            $att_local_path = $blob->get_path();
            debug_add("Checking is_readable({$att_local_path})");
            if (is_readable($att_local_path))
            {
                $this->header("X-Sendfile: {$att_local_path}");
                $send_att_body = false;
            }
        }

        // Store metadata in cache so _check_hit() can help us
        if (   !$this->cache->content->_uncached
            && !$this->cache->content->_no_cache)
        {
            $this->cache->content->write_meta_cache('A-' . $etag, $etag);
        }

        while(@ob_end_flush());

        if (!$send_att_body)
        {
            debug_add('NOT sending file (X-Sendfile will take care of that, _midcom_stop_request()ing so nothing has a chance the mess things up anymore');
            debug_pop();
            _midcom_stop_request();
        }

        fpassthru($f);
        $attachment->close();
        debug_add("End of MidCOM run: {$_SERVER['REQUEST_URI']}", MIDCOM_LOG_DEBUG);
        debug_pop();
        _midcom_stop_request();
    }

    /**
     * This is a helper to execute any given Script in the current MidCOM
     * context. All files have to be in $component_dir/exec directly, otherwise
     * the script will not execute.
     *
     * The script's name is taken from the current argv[0].
     *
     * Any error calls generate_error.
     *
     * The script file is executed in the cache's live mode to allow for long running
     * scripts (just produce any output regularly, or Apache will kill you after ~ 2 mins.).
     *
     * The remaining arguments will be placed into the globals $argc/argv.
     *
     * @param string $component The component to look in ("midcom" uses core scripts)
     * @see midcom_services_cache_module_content::enable_live_mode()
     */
    function _exec_file($component)
    {
        // Sanity checks
        if ($this->_parsers[$this->_currentcontext]->argc < 1)
        {
            $this->generate_error(MIDCOM_ERRNOTFOUND, "Script exec path invalid, need exactly one argument.");
        }

        // Build the path
        if ($component == 'midcom')
        {
            $path = MIDCOM_ROOT . '/midcom/exec/';
        }
        else
        {
            if (! $this->componentloader->validate_url($component))
            {
                $this->generate_error(MIDCOM_ERRNOTFOUND, "The component path {$component} is invalid.");
                // This will exit
            }
            $this->componentloader->load($component);
            $this->_set_context_data($component, MIDCOM_CONTEXT_COMPONENT);
            $path = MIDCOM_ROOT . $this->componentloader->path_to_snippetpath($component) . '/exec/';
        }
        $path .= $this->_parsers[$this->_currentcontext]->argv[0];

        if (   is_dir($path)
            && isset($this->_parsers[$this->_currentcontext]->argv[1]))
        {
            $path .= '/' . $this->_parsers[$this->_currentcontext]->argv[1];
        }

        if (is_dir($path))
        {
            $this->generate_error(MIDCOM_ERRNOTFOUND, "File is a directory.");
        }

        if (! file_exists($path))
        {
            $this->generate_error(MIDCOM_ERRNOTFOUND, "File not found.");
        }

        // collect remaining arguments and put them to global vars.
        $GLOBALS['argc'] = $this->_parsers[$this->_currentcontext]->argc--;
        $GLOBALS['argv'] = $this->_parsers[$this->_currentcontext]->argv;
        array_shift($GLOBALS['argv']);

        $this->cache->content->enable_live_mode();

        $this->_status = MIDCOM_STATUS_CONTENT;

        // We seem to be in a valid place. Exec the file with the current
        // permissions.
        require($path);

        // Exit
        $this->finish();
        _midcom_stop_request('');
    }

    /**
     * Register JavaScript File for referring in the page.
     *
     * This allows MidCOM components to register JavaScript code
     * during page processing. The site style code can then query this queued-up code
     * at anytime it likes. The queue-up SHOULD be done during the code-init phase,
     * while the print_head_elements output SHOULD be included in the HTML HEAD area and
     * the HTTP onload attribute returned by print_jsonload SHOULD be included in the
     * BODY-tag. Note, that these suggestions are not enforced, if you want a JScript
     * clean site, just omit the print calls and you should be fine in almost all
     * cases.
     *
     * The sequence of the add_jsfile and add_jscript commands is kept stable.
     *
     * @param string $url    The URL to the file to-be referenced.
     * @param boolean $prepend Whether to add the JS include to beginning of includes
     * @see add_jscript()
     * @see add_jsonload()
     * @see print_head_elements()
     * @see print_jsonload()
     */
    function add_jsfile($url, $prepend = false)
    {
        // use merger cache if possible
        if (   $this->jscss
            && is_callable(array($this->jscss, 'add_jsfile')))
        {
            if ($this->jscss->add_jsfile($url, $prepend))
            {
                return;
            }
        }
        // Adds a URL for a <script type="text/javascript" src="tinymce.js"></script>
        // like call. $url is inserted into src. Duplicates are omitted.
        if (! in_array($url, $this->_jsfiles))
        {
            $this->_jsfiles[] = $url;
            $js_call = "<script type=\"text/javascript\" src=\"{$url}\"></script>\n";
            if ($prepend)
            {
                // Add the javascript include to the beginning, not the end of array
                array_unshift($this->_jshead, $js_call);
            }
            else
            {
                $this->_jshead[] = $js_call;
            }
        }
    }

    /**
     * Register JavaScript Code for output directly in the page.
     *
     * This allows MidCOM components to register JavaScript code
     * during page processing. The site style code can then query this queued-up code
     * at anytime it likes. The queue-up SHOULD be done during the code-init phase,
     * while the print_head_elements output SHOULD be included in the HTML HEAD area and
     * the HTTP onload attribute returned by print_jsonload SHOULD be included in the
     * BODY-tag. Note, that these suggestions are not enforced, if you want a JScript
     * clean site, just omit the print calls and you should be fine in almost all
     * cases.
     *
     * The sequence of the add_jsfile and add_jscript commands is kept stable.
     *
     * @param string $script    The code to be included directly in the page.
     * @see add_jsfile()
     * @see add_jsonload()
     * @see print_head_elements()
     * @see print_jsonload()
     */
    function add_jscript($script, $defer = '', $prepend = false)
    {
        $js_call = "<script type=\"text/javascript\"{$defer}>\n";
        $js_call .= trim($script) . "\n";
        $js_call .= "</script>\n";
        if ($prepend)
        {
            $this->_prepend_jshead[] = $js_call;
        }
        else
        {
            $this->_jshead[] = $js_call;
        }
    }

    /**
     * Register JavaScript snippets to jQuery states.
     *
     * This allows MidCOM components to register JavaScript code
     * to the jQuery states.
     * Possible ready states: document.ready
     *
     * @param string $script    The code to be included in the state.
     * @param string $state    The state where to include the code to. Defaults to document.ready
     * @see print_jquery_statuses()
     */
    function add_jquery_state_script($script, $state = 'document.ready')
    {
        $js_call = "\n" . trim($script) . "\n";

        if (!isset($this->_jquery_states[$state]))
        {
            $this->_jquery_states[$state] = $js_call;
        }
        else
        {
            $this->_jquery_states[$state] .= $js_call;
        }
    }

    /**
     * Register some object tags to be added to the head element.
     *
     * This allows MidCom components to register object tags to be placed in the
     * head section of the page.
     *
     * @param  string $script    The input between the <object></object> tags.
     * @param  array  $attributes Array of attribute=> value pairs to be placed in the tag.
     * @see print_head_elements()
     *
     */

    function add_object_head ($script, $attributes = null)
    {
        $output = "";
        if (!is_null($attributes) ) foreach ($attributes as $key => $val)
        {
            $output .= " $key=\"$val\" ";
        }
        $this->_object_head .= '<object '. $output . ' >' . $script . "</object>\n";
    }
    /**
     *  Register a metatag  to be added to the head element.
     *  This allows MidCom components to register metatags  to be placed in the
     *  head section of the page.
     *
     *  @param  array  $attributes Array of attribute=> value pairs to be placed in the tag.
     *  @see print_head_elements()
     */
    function add_meta_head($attributes = null)
    {
         $output = "";
         if (!is_null($attributes) ) foreach ($attributes as $key => $val)
         {
            $output .= " $key=\"$val\" ";
         }
         $this->_meta_head .= '<meta '. $output . ' />'."\n";
    }

    /**
     * Register a styleblock / style link  to be added to the head element.
     * This allows MidCom components to register extra css sheets they wants to include.
     * in the head section of the page.
     *
     * @param  string $script    The input between the <style></style> tags.
     * @param  array  $attributes Array of attribute=> value pairs to be placed in the tag.
     * @see print_head_elements()
     */
    function add_style_head($script, $attributes = null)
    {
        $output = "";
        if (!is_null($attributes) ) foreach ($attributes as $key => $val)
        {
            $output .= " $key=\"$val\" ";
        }
        $this->_style_head .= '<style '. $output . ' type="text/css" ><!--' . $script . "--></style>\n";
    }
    /**
     * Register a linkelement to be placed in the pagehead.
     * This allows MidCom components to register extra css-links in the pagehead.
     * Example to use this to include a css link:
     * <code>
     * $attributes = array ('rel' => 'stylesheet',
     *                      'type' => 'text/css',
     *                      'href' => '/style.css'
     *                      );
     * $midcom->add_link_head($attributes);
     * </code>
     *
     *  @param  array  $attributes Array of attribute=> value pairs to be placed in the tag.
     *  @see print_head_elements()
     */
    function add_link_head( $attributes = null )
    {
        if (   is_null($attributes)
            || !is_array($attributes))
        {
            return false;
        }

        if (!array_key_exists('href', $attributes))
        {
            return false;
        }
        // use merger cache if possible
        if (   $this->jscss
            && is_callable(array($this->jscss, 'add_cssfile')))
        {
            if ($this->jscss->add_cssfile($attributes))
            {
                return;
            }
        }

        // Register each URL only once
        if (in_array($attributes['href'], $this->_linkhrefs))
        {
            return false;
        }
        $this->_linkhrefs[] = $attributes['href'];

        $output = '';

        if (array_key_exists('condition', $attributes))
        {
            $this->_link_head .= "<!--[if {$attributes['condition']}]>\n";
        }

        foreach ($attributes as $key => $val)
        {
            if ($key != 'condition')
            {
                $output .= " {$key}=\"{$val}\" ";
            }
        }
        $this->_link_head .= "<link{$output}/>\n";

        if (array_key_exists('condition', $attributes))
        {
            $this->_link_head .= "<![endif]-->\n";
        }
    }

    /**
     * Register a JavaScript method for the body onload event
     *
     * This allows MidCOM components to register JavaScript code
     * during page processing. The site style code can then query this queued-up code
     * at anytime it likes. The queue-up SHOULD be done during the code-init phase,
     * while the print_head_elements output SHOULD be included in the HTML HEAD area and
     * the HTTP onload attribute returned by print_jsonload SHOULD be included in the
     * BODY-tag. Note, that these suggestions are not enforced, if you want a JScript
     * clean site, just omit the print calls and you should be fine in almost all
     * cases.
     *
     * @param string $method    The name of the method to be called on page startup, including parameters but excluding the ';'.
     * @see add_jsfile()
     * @see add_jscript()
     * @see print_head_elements()
     * @see print_jsonload()
     */
    function add_jsonload($method)
    {
        // Adds a method name for <body onload=".."> The string must not end with a ;, it is added automagically
        $this->_jsonload[] = $method;
    }

    /**
     * Echo the registered javascript code.
     *
     * This allows MidCOM components to register JavaScript code
     * during page processing. The site style code can then query this queued-up code
     * at anytime it likes. The queue-up SHOULD be done during the code-init phase,
     * while the print_head_elements output SHOULD be included in the HTML HEAD area and
     * the HTTP onload attribute returned by print_jsonload SHOULD be included in the
     * BODY-tag. Note, that these suggestions are not enforced, if you want a JScript
     * clean site, just omit the print calls and you should be fine in almost all
     * cases.
     *
     * The sequence of the add_jsfile and add_jscript commands is kept stable.
     *
     * This is usually called during the BODY region of your style:
     *
     * <code>
     * <HTML>
     *     <BODY <?php $_MIDCOM->print_jsonload();?>>
     *            <!-- your actual body -->
     *     </BODY>
     * </HTML>
     * </code>
     *
     * @see add_jsfile()
     * @see add_jscript()
     * @see add_jsonload()
     * @see print_head_elements()
     */
    function print_jsonload()
    {
        if (count ($this->_jsonload) > 0) {
            $calls = implode("; ", $this->_jsonload);
            echo " onload=\"$calls\" ";
        }
    }
    /**
     * Echo the _head elements added.
     * This function echos the elements added by the add_(style|meta|link|object)_head
     * methods.
     *
     * Place the method within the <head> section of your page.
     *
     * This allows MidCOM components to register HEAD elements
     * during page processing. The site style code can then query this queued-up code
     * at anytime it likes. The queue-up SHOULD be done during the code-init phase,
     * while the print_head_elements output SHOULD be included in the HTML HEAD area and
     * the HTTP onload attribute returned by print_jsonload SHOULD be included in the
     * BODY-tag. Note, that these suggestions are not enforced, if you want a JScript
     * clean site, just omit the print calls and you should be fine in almost all
     * cases.
     *
     * @see add_link_head
     * @see add_object_head
     * @see add_style_head
     * @see add_meta_head
     * @see add_jsfile()
     * @see add_jscript()
     */
    function print_head_elements()
    {
        if ($this->_jquery_enabled)
        {
            echo $this->_jquery_init_scripts;
        }

        if (!empty($this->_prepend_jshead))
        {
            foreach ($this->_prepend_jshead as $js_call)
            {
                echo $js_call;
            }
        }

        echo $this->_link_head;
        if (   $this->jscss
            && is_callable(array($this->jscss, 'print_cssheaders')))
        {
            $this->jscss->print_cssheaders();
        }
        echo $this->_object_head;
        echo $this->_style_head;
        echo $this->_meta_head;
        // if (   $this->jscss
        //     && is_callable(array($this->jscss, 'print_jsheaders')))
        // {
        //     $this->jscss->print_jsheaders();
        // }
        foreach ($this->_jshead as $js_call)
        {
            echo $js_call;
        }
        $this->print_jquery_statuses();
    }

    /**
     * Init jQuery
     *
     * This method adds jQuery support to the page
     *
     */
    function enable_jquery($version = null)
    {
        if ($this->_jquery_enabled)
        {
            return;
        }

        if (!$version)
        {
            $version = $GLOBALS['midcom_config']['jquery_version'];
        }

        $this->_jquery_init_scripts .= "\n";

        if ($GLOBALS['midcom_config']['jquery_load_from_google'])
        {
            // Use Google's hosted jQuery version
            $this->_jquery_init_scripts .= "<script src=\"http://www.google.com/jsapi\"></script>\n";
            $this->_jquery_init_scripts .= "<script>\n";
            $this->_jquery_init_scripts .= "    google.load('jquery', '{$GLOBALS['midcom_config']['jquery_version']}');\n";
            $this->_jquery_init_scripts .= "</script>\n";
        }
        else
        {
            $url = MIDCOM_STATIC_URL . "/jQuery/jquery-{$version}.js";
            $this->_jquery_init_scripts .= "<script type=\"text/javascript\" src=\"{$url}\"></script>\n";
        }

        if (!defined('MIDCOM_JQUERY_UI_URL'))
        {
            define('MIDCOM_JQUERY_UI_URL', MIDCOM_STATIC_URL . "/jQuery/jquery.ui-{$GLOBALS['midcom_config']['jquery_ui_version']}");
        }

        $script  = "var MIDCOM_STATIC_URL = '" . MIDCOM_STATIC_URL . "';\n";
        $script .= "var MIDCOM_PAGE_PREFIX = '" . $_MIDCOM->get_page_prefix() . "';\n";

        $this->_jquery_init_scripts .= "<script type=\"text/javascript\">\n";
        $this->_jquery_init_scripts .= trim($script) . "\n";
        $this->_jquery_init_scripts .= "</script>\n";

        $this->_jquery_enabled = true;
    }

    /**
     * Echo the jquery statuses
     *
     * This function echos the scripts added by the add_jquery_state_script
     * method.
     *
     * This method is called from print_head_elements method.
     *
     * @see add_jquery_state_script
     * @see print_head_elements
     */
    function print_jquery_statuses()
    {
        if (empty($this->_jquery_states))
        {
            return;
        }

        echo '<script type="text/javascript">' . "\n";

        foreach ($this->_jquery_states as $status => $scripts)
        {
            $status_parts = explode('.',$status);
            $status_target = $status_parts[0];
            $status_method = $status_parts[1];
            echo "\njQuery({$status_target}).{$status_method}(function() {\n";
            echo $scripts;
            echo "\n" . '});' . "\n";
        }

        echo '</script>' . "\n";
    }

    /**
     * Relocate to another URL.
     *
     * Helper function to facilitate HTTP relocation (Location: ...) headers. The helper
     * actually can distinguish between site-local, absolute redirects and external
     * redirects. If you add an absolute URL starting with a "/", it will
     * automatically add an http[s]://$servername:$server_port in front of that URL;
     * note that the server_port is optional and only added if non-standard ports are
     * used. If the url does not start with http[s], it is taken as a URL relative to
     * the current anchor prefix, which gets prepended automatically (no other characters
     * as the anchor prefix get inserted).
     *
     * Fully qualified urls (starting with http[s]) are used as-is.
     *
     * Note, that this function automatically makes the page uncacheable, calls
     * midcom_finish and exit, so it will never return. If the headers have already
     * been sent, this will leave you with a partially completed page, so beware.
     *
     * @param string $url    The URL to redirect to, will be preprocessed as outlined above.
     * @param string $response_code HTTP response code to send with the relocation, from 3xx series
     */
    function relocate($url, $response_code = 302)
    {
        debug_push_class(__CLASS__, __FUNCTION__);
        debug_add("Relocating to {$url}");

        if (! preg_match('|^https?://|', $url))
        {
            if (   $url == ''
                || substr($url, 0, 1) != "/")
            {
                $prefix = $this->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
                if ($prefix == '')
                {
                    $prefix = $this->get_page_prefix();
                }
                $url =  "{$prefix}{$url}";
                debug_add("This is a relative URL from the local MidCOM site, prepending anchor prefix: {$url}");
            }
            else
            {
                $url = $this->get_host_name() . $url;
                debug_add("This is an absolute URL from the local host, prepending host name: {$url}");
            }

            $location = "Location: {$url}";
        }
        else
        {
            // This is an external URL
            $location = "Location: {$url}";
        }

        $this->cache->content->no_cache();
        $this->header($location, $response_code);
        debug_add("Relocating to {$location}");
        $this->finish();
        _midcom_stop_request();
    }

    /**
     * Shows the contents of the current debuglog. You have to enable this interface
     * by setting the config option <i>log_tailurl_enable</i> to true. Note, that ´
     * this method is using the debug log path
     * of the current MidCOM debugger automatically, it is not possible to switch to
     * another logfile dynamically due to security reasons. The parameter can be
     * either "all" which will yield the complete log (beware of huge logfiles), or
     * an integer, which is the number of lines counting from the file backwards you
     * want to display (this uses the systems tail command via exec).
     *
     * MidCOM Error pages (FORBIDDEN/NOTFOUND) are created upon error.
     *
     * @param mixed $count Number of lines to be dumped or 'all' for everything
     * @access private
     */
    function _showdebuglog($count)
    {
        if ($GLOBALS['midcom_config']['log_tailurl_enable'] !== true)
        {
            $this->generate_error(MIDCOM_ERRFORBIDDEN, "Access to the debug log is disabled.");
        }

        $filename = $GLOBALS["midcom_debugger"]->_filename;

        if ($count == "all")
        {
            $this->header("Content-Type: text/plain");
            $this->cache->content->no_cache();
            $handle = fopen($filename, "r");

            fpassthru($handle);
            $this->finish();
            _midcom_stop_request();

        }
        else if (is_numeric($count))
        {
            $this->header("Content-Type: text/plain");

            $this->cache->content->no_cache();
            passthru ("tail -" . abs($count) . " " . escapeshellarg($filename));
            $this->finish();
            _midcom_stop_request();

        }
        else
        {
            $this->generate_error(MIDCOM_ERRNOTFOUND, "Parameter must be 'all' or an integer");
        }
    }

    /**
     * Binds the current page view to a particular object. This will automatically connect such things like
     * metadata and toolbars to the correct object.
     *
     * @param DBAObject &$object The DBA class instance to bind to.
     * @param string $page_class String describing page type, will be used for substyling
     */
    function bind_view_to_object(&$object, $page_class = 'default')
    {
        // Bind the object into the view toolbar
        $view_toolbar = $this->toolbars->get_view_toolbar($this->_currentcontext);
        $view_toolbar->bind_to($object);

        // Bind the object to the metadata service
        $this->metadata->bind_metadata_to_object(MIDCOM_METADATA_VIEW, $object, $this->_currentcontext);

        // Push the object's CSS classes to metadata service
        $page_class = $_MIDCOM->metadata->get_object_classes($object, $page_class);
        $this->metadata->set_page_class($page_class, $this->_currentcontext);

        $this->substyle_append($page_class);
    }

    /**
     * This is a temporary transition function used to set the currently known and required
     * Request Metadata: The last modified timestamp and the permalink GUID.
     *
     * <i>Author's note:</i> This function is a temporary solution which is used until the
     * Request handling code of MidCOM has been rewritten. Hence the _26_ section in its name.
     * I have decided to put them into their own function instead of letting you access the
     * corresponding context keys directly. Thus, there is also corresponding getter-function,
     * which return you the set information here. Just don't worry where it is stored and use
     * the interface functions.
     *
     * You may set either of the arguments to NULL to enforce default usage (based on NAP).
     *
     * @param int $lastmodified The date of last modification of this request.
     * @param string $permalinkguid The GUID used to create a permalink for this request.
     * @see get_26_request_metadata()
     */
    function set_26_request_metadata($lastmodified, $permalinkguid)
    {
        if (   is_object($lastmodified)
            && is_a($lastmodified, 'midgard_datetime'))
        {
            // Midgard2 compatibility
            $lastmodified = $lastmodified->format('U');
        }
        $this->_context[$this->_currentcontext][MIDCOM_CONTEXT_LASTMODIFIED] = $lastmodified;
        $this->_context[$this->_currentcontext][MIDCOM_CONTEXT_PERMALINKGUID] = $permalinkguid;
    }

    /**
     * This is a temporary transition function used to get the currently known and required
     * Request Metadata: The last modified timestamp and the permalink GUID.
     *
     * <i>Author's note:</i> This function is a temporary solution which is used until the
     * Request handling code of MidCOM has been rewritten. Hence the _26_ section in its name.
     * I have decided to put them into their own function instead of letting you access the
     * corresponding context keys directly. Thus, there is also corresponding setter-function,
     * which set the information returned here. Just don't worry where it is stored and use
     * the interface functions.
     *
     * @param int $context The context from which the request metadata should be retrieved. Omit
     *     to use the current context.
     * @return Array An array with the two keys 'lastmodified' and 'permalinkguid' containing the
     *     values set with the setter pendant. For ease of use, there is also a key 'permalink'
     *     which contains a ready-made permalink.
     * @see set_26_request_metadata()
     */
    function get_26_request_metadata($context = null)
    {
        if ($context === null)
        {
            $context = $this->_currentcontext;
        }
        $meta = array
        (
            'lastmodified' => $this->_context[$context][MIDCOM_CONTEXT_LASTMODIFIED],
            'permalinkguid' => $this->_context[$context][MIDCOM_CONTEXT_PERMALINKGUID],
            'permalink' => $this->permalinks->create_permalink($this->_context[$context][MIDCOM_CONTEXT_PERMALINKGUID]),
        );

        if (   is_object($meta['lastmodified'])
            && is_a($meta['lastmodified'], 'midgard_datetime'))
        {
            // Midgard2 compatibility
            $meta['lastmodified'] = $meta['lastmodified']->format('U');
        }

        return $meta;
    }
}
?>
