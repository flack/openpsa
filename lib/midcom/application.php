<?php
/**
 * @package midcom
 * @author The Midgard Project, http://www.midgard-project.org
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
 * <b>GUID serveattachmentguid</b>
 *
 * This method will serve the attachment denoted by the given ID/GUID.
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
 * calling midcom::get('cache')->content->no_cache();
 *
 * @package midcom
 */
class midcom_application
{
    /**
     * Integer constant resembling the current MidCOM state.
     *
     * See the MIDCOM_STATUS_... constants
     *
     * @var int
     */
    private $_status = null;

    /**
     * Host prefix cache to avoid computing it each time.
     *
     * @var string
     * @see get_host_prefix()
     */
    private $_cached_host_prefix = '';

    /**
     * Page prefix cache to avoid computing it each time.
     *
     * @var string
     * @see get_page_prefix()
     */
    private $_cached_page_prefix = '';

    /**
     * Host name cache to avoid computing it each time.
     *
     * @var string
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
     */
    public $skip_page_style = false;

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
        $this->_status = MIDCOM_STATUS_PREPARE;

        // Start-up some of the services
        midcom::get('dbclassloader')->load_classes('midcom', 'legacy_classes.inc', null, true);
        midcom::get('dbclassloader')->load_classes('midcom', 'core_classes.inc', null, true);

        midcom::get('componentloader')->load_all_manifests();

        // Initialize Context Storage
        $context = new midcom_core_context(0);
        $context->set_current();

        // Initialize Root Topic
        try
        {
            $root_node = midcom_db_topic::get_cached($GLOBALS['midcom_config']['midcom_root_topic_guid']);
        }
        catch (midcom_error $e)
        {
            if ($e instanceof midcom_error_forbidden)
            {
                throw new midcom_error_forbidden(midcom::get('i18n')->get_string('access denied', 'midcom'));
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
                    throw new midcom_error
                    (
                        "Fatal error: Unable to load website root folder with GUID '{$GLOBALS['midcom_config']['midcom_root_topic_guid']}'.<br />" .
                        'Last Midgard Error was: ' . midcom_connection::get_error_string()
                    );
                }
                $root_node = $topics[0];
            }
        }

        $context->set_key(MIDCOM_CONTEXT_ROOTTOPIC, $root_node);
        $context->set_key(MIDCOM_CONTEXT_ROOTTOPICID, $root_node->id);

        // Check the midcom_config site prefix for absolute local urls
        if ($GLOBALS['midcom_config']['midcom_site_url'][0] == '/')
        {
            $GLOBALS['midcom_config']['midcom_site_url'] =
                $this->get_page_prefix()
                . substr($GLOBALS['midcom_config']['midcom_site_url'], 1);
        }
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
        $context = midcom_core_context::get();
        if ($context->id == 0)
        {
            // Initialize the UI message stack from session
            midcom::get('uimessages')->initialize();
        }

        // Parse the URL
        $context->parser = midcom::get('serviceloader')->load('midcom_core_service_urlparser');
        $context->parser->parse(midcom_connection::get_url('argv'));

        $this->_process($context);

        if ($context->id == 0)
        {
            // Let metadata service add its meta tags
            midcom::get('metadata')->populate_meta_head();
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
        $oldcontext = midcom_core_context::get();
        if ($oldcontext->id != 0)
        {
            debug_add("Entering Context 0 (old Context: {$oldcontext->id})");
        }
        $this->_currentcontext = 0;
        midcom::get('style')->enter_context(0);

        $template = midcom_helper_misc::preparse('<(ROOT)>');
        $template_parts = explode('<(content)>', $template);
        eval('?>' . $template_parts[0]);

        $this->_output();

        if (isset($template_parts[1]))
        {
            eval('?>' . $template_parts[1]);
        }

        // Leave Context
        if ($oldcontext->id != 0)
        {
            debug_add("Leaving Context 0 (new Context: {$oldcontext->id})");
        }

        midcom::get('style')->leave_context();
        $oldcontext->set_current();
    }

    /**
     * Dynamically execute a subrequest and insert its output in place of the
     * function call.
     *
     * <b>Important Note</b> As with the Midgard Parser, dynamic_load strips a
     * trailing .html from the argument list before actually parsing it.
     *
     * It tries to load the component referenced with the URL $url and executes
     * it as if it was used as primary component. Additional configuration parameters
     * can be appended through the parameter $config.
     *
     * This is only possible if the system is in the Page-Style output phase. It
     * cannot be used within code-init or during the output phase of another
     * component.
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
     * @return int                       The ID of the newly created context.
     */
    public function dynamic_load($url, $config = array(), $pass_get = false)
    {
        debug_add("Dynamic load of URL {$url}");

        if (substr($url, -5) == '.html')
        {
            $url = substr($url, 0, -5);
        }

        if ($this->_status < MIDCOM_STATUS_CONTENT)
        {
            throw new midcom_error("dynamic_load content request called before content output phase.");
        }

        // Determine new Context ID and set currentcontext,
        // enter that context and prepare its data structure.
        $context = new midcom_core_context(null, $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ROOTTOPIC));

        if ($pass_get)
        {
            // Include GET parameters into cache URL
            $context->set_key(MIDCOM_CONTEXT_URI, midcom_connection::get_url('self') . $url . '?GET=' . serialize($_GET));
        }
        else
        {
            $context->set_key(MIDCOM_CONTEXT_URI, midcom_connection::get_url('self') . $url);
        }

        $oldcontext = midcom_core_context::get();
        $context->set_current();

        /* "content-cache" for DLs, check_hit */
        if (midcom::get('cache')->content->check_dl_hit($context->id, $config))
        {
            // The check_hit method serves cached content on hit
            $oldcontext->set_current();
            return $context->id;
        }

        // Parser Init: Generate arguments and instantiate it.
        $context->parser = midcom::get('serviceloader')->load('midcom_core_service_urlparser');
        $argv = $context->parser->tokenize($url);
        $context->parser->parse($argv);

        // Processing, upon error the generate_error function will die here...
        $this->_process($context);

        if ($this->_status == MIDCOM_STATUS_ABORT)
        {
            debug_add("Dynamic load _process() phase ended up with 404 Error. Aborting...", MIDCOM_LOG_ERROR);

            // Leave Context
            $oldcontext->set_current();

            return;
        }

        // Start another buffer for caching DL results
        ob_start();

        midcom::get('style')->enter_context($context->id);
        debug_add("Entering Context $context->id (old Context: $oldcontext->id)");

        $this->_output();

        midcom::get('style')->leave_context();
        debug_add("Leaving Context $context->id (new Context: $oldcontext->id)");

        $dl_cache_data = ob_get_contents();
        ob_end_flush();
        /* Cache DL the content */
        midcom::get('cache')->content->store_dl_content($context->id, $config, $dl_cache_data);
        unset($dl_cache_data);

        // Leave Context
        $oldcontext->set_current();

        return $context->id;
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
        midcom::get('cache')->content->_finish_caching();

        midcom::get('componentloader')->process_pending_notifies();

        // Store any unshown messages
        midcom::get('uimessages')->store();

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
        midcom::get('cache')->shutdown();

        debug_add("End of MidCOM run: {$_SERVER['REQUEST_URI']}");
    }

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
     */
    private function _process(midcom_core_context $context)
    {
        $success = false;

        $this->_process_variables($context);

        $this->_status = MIDCOM_STATUS_CANHANDLE;

        do
        {
            $object = $context->parser->get_current_object();
            if (   !is_object($object)
                || !$object->guid)
            {
                throw new midcom_error('Root node missing.');
            }

            if (is_a($object, 'midcom_db_attachment'))
            {
                $this->serve_attachment($object);
            }

            // Check whether the component can handle the request.
            // If so, execute it, if not, continue.
            if ($handler = $context->get_handler($object))
            {
                $context->run($handler);
                $success = true;
                break;
            }
        } while ($context->parser->get_object() !== false);

        if (! $success)
        {
            /**
             * Simple: if current context is not '0' we were called from another context.
             * If so we should not break application now - just gracefully continue.
             */
            if ($context->id == 0)
            {
                // We couldn't fetch a node due to access restrictions
                if (midcom_connection::get_error() == MGD_ERR_ACCESS_DENIED)
                {
                    throw new midcom_error_forbidden(midcom::get('i18n')->get_string('access denied', 'midcom'));
                }
                else
                {
                    throw new midcom_error_notfound("This page is not available on this server.");
                }
            }

            $this->_status = MIDCOM_STATUS_ABORT;
            return false;// This will exit.
        }

        if (   $context->id == 0
            && $this->skip_page_style == true)
        {
            $this->_status = MIDCOM_STATUS_CONTENT;

            // Enter Context
            $oldcontext = $context;
            midcom_core_context::get(0)->set_current();
            midcom::get('style')->enter_context(0);

            $this->_output();

            // Leave Context
            midcom::get('style')->leave_context();
            $oldcontext->set_current();

            $this->finish();
            _midcom_stop_request();
        }
        else
        {
            $this->_status = MIDCOM_STATUS_CONTENT;
        }
    }

    private function _process_variables(midcom_core_context $context)
    {
        while (($tmp = $context->parser->get_variable('midcom')) !== false)
        {
            foreach ($tmp as $key => $value)
            {
                switch ($key)
                {
                    case 'substyle':
                        $context->set_key(MIDCOM_CONTEXT_SUBSTYLE, $value);
                        debug_add("Substyle '$value' selected");
                        break;

                    case 'serveattachmentguid':
                        if ($context->parser->argc > 1)
                        {
                            debug_add('Too many arguments remaining for serve_attachment.', MIDCOM_LOG_ERROR);
                        }

                        $attachment = new midcom_db_attachment($value);
                        if (!$attachment->can_do('midgard:autoserve_attachment'))
                        {
                            throw new midcom_error_notfound('Failed to access attachment: Autoserving denied.');
                        }

                        $this->serve_attachment($attachment);
                        $this->finish();
                        _midcom_stop_request();

                    case 'permalink':
                        $guid = $value;
                        $destination = midcom::get('permalinks')->resolve_permalink($guid);
                        if ($destination === null)
                        {
                            throw new midcom_error_notfound("This Permalink is unknown.");
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
                                midcom::get('auth')->require_valid_user('basic');
                                midcom::get('auth')->require_admin_user();
                            }
                            midcom::get('cache')->content->enable_live_mode();
                            midcom::get('cache')->invalidate_all();
                            midcom::get('uimessages')->add($_MIDCOM->i18n->get_string('MidCOM', 'midcom'), "Cache invalidation successful.", 'info');

                            $url = (isset($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : '';
                            $this->relocate($url);
                        }
                        else if ($value == 'nocache')
                        {
                            midcom::get('cache')->content->no_cache();
                        }
                        else
                        {
                            throw new midcom_error_notfound("Invalid cache request URL.");
                        }
                        break;

                    case "logout":
                        // rest of URL used as redirect
                        $remaining_url = false;
                        if (   !empty($tmp['logout'])
                            || !empty($context->parser->argv))
                        {
                            $remaining_url = $tmp['logout'] . '/' ;
                            $remaining_url .= implode($context->parser->argv, '/');
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
                        midcom::get('cache')->content->no_cache();
                        midcom::get('auth')->logout();
                        $this->relocate($redirect_to);
                        // This will exit

                    case "login":
                        // rest of URL used as redirect
                        $remaining_url = false;
                        if (   !empty($tmp['login'])
                            || !empty($context->parser->argv))
                        {
                            $remaining_url = "{$tmp['login']}/" . implode($context->parser->argv, '/');
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
                        if (midcom::get('auth')->is_valid_user())
                        {
                            $this->relocate($redirect_to);
                            // This will exit
                        }
                        midcom::get('auth')->show_login_page();
                        // This will exit too;

                    case 'exec':
                        $this->_exec_file($value);
                        // This will exit

                    default:
                        debug_add("Unknown MidCOM URL Property ignored: {$key} => {$value}", MIDCOM_LOG_WARN);
                        throw new midcom_error_notfound("This MidCOM URL method is unknown.");
                }
            }
        }
    }

    /**
     * Execute the output callback.
     *
     * Launches the output of the currently selected component. It is collected in an output buffer
     * to allow for relocates from style elements.
     *
     * It executes the content_handler that has been determined during the handle
     * phase. It fetches the content_handler from the Component Loader class cache.
     */
    private function _output()
    {
        ob_start();
        if (!$this->skip_page_style)
        {
            midcom_show_style('style-init');
        }
        $context = midcom_core_context::get();

        $component = midcom::get('componentloader')->get_interface_class($context->get_key(MIDCOM_CONTEXT_COMPONENT));
        $component->show_content($context->id);

        if (!$this->skip_page_style)
        {
            midcom_show_style('style-finish');
        }
        ob_end_flush();
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
            throw new midcom_error("Cannot do a substyle_append before the HANDLE phase.");
        }

        $context = midcom_core_context::get();
        $current_style = $context->get_key(MIDCOM_CONTEXT_SUBSTYLE);

        if (strlen($current_style) > 0)
        {
            $newsub = $current_style . '/' . $newsub;
        }

        $context->set_key(MIDCOM_CONTEXT_SUBSTYLE, $newsub);
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
        if ($this->_status < MIDCOM_STATUS_HANDLE)
        {
            throw new midcom_error("Cannot do a substyle_append before the HANDLE phase.");
        }

        $context = midcom_core_context::get();
        $current_style = $context->get_key(MIDCOM_CONTEXT_SUBSTYLE);

        if (strlen($current_style) > 0)
        {
            $newsub .= "/" . $current_style;
        }
        debug_add("Updating Component Context Substyle from $current_style to $newsub");

        $context->set_key(MIDCOM_CONTEXT_SUBSTYLE, $newsub);
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
     * Manually override the current MidCOM processing state.
     * Don't use this unless you know what you're doing
     *
     * @param int One of the MIDCOM_STATUS_... constants indicating current state.
     */
    function set_status($status)
    {
        $this->_status = $status;
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
        $componentloader = midcom::get('componentloader');
        if (! array_key_exists($path, $componentloader->manifests))
        {
            debug_add("Cannot load component {$path} as library, it is not installed.", MIDCOM_LOG_ERROR);
            return false;
        }

        if (! $componentloader->manifests[$path]->purecode)
        {
            debug_add("Cannot load component {$path} as library, it is a full-fledged component.", MIDCOM_LOG_ERROR);
            debug_print_r('Manifest:', $componentloader->manifests[$path]);
            return false;
        }

        $componentloader->load($path);

        return true;
    }

    /* *************************************************************************
     * Generic Helper Functions not directly related with MidCOM:
     *
     * serve_snippet      - Serves snippet including all necessary headers
     * serve_attachment   - Serves attachment including all necessary headers
     * relocate           - executes a HTTP relocation to the given URL
     */

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
        midcom::get('cache')->content->register_sent_header($header);

        if (!is_null($response_code))
        {
            // Send the HTTP response code as requested
            _midcom_header($header, true, $response_code);
        }
        else
        {
            _midcom_header($header);
        }
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
        if (! preg_match('|^https?://|', $url))
        {
            if (   $url == ''
                || substr($url, 0, 1) != "/")
            {
                $prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
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

        midcom::get('cache')->content->no_cache();

        $this->finish();
        debug_add("Relocating to {$location}");
        $this->header($location, $response_code);
        _midcom_stop_request();
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
        if ($snippet->parameter("midcom", "allow_serve") != "true")
        {
            throw new midcom_error_forbidden("This snippet may not be served.");
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
        midcom::get('cache')->content->content_type($content_type);

        // TODO: This should be made aware of the cache headers strategy for content cache module
        if ($expire > 0)
        {
            $this->header("Cache-Control: public max-age=$expires");
            $this->header("Expires: " . gmdate("D, d M Y H:i:s", (time()+$expire)) . " GMT" );
            midcom::get('cache')->content->expires(time()+$expire);
        }
        else if ($expire == 0)
        {
            midcom::get('cache')->content->no_cache();
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
            throw new midcom_error("\$expires has to be a positive integer or zero or -1, is now {$expires}.");
        }

        // Doublecheck that this is registered
        $cache = midcom::get('cache');
        $cache->content->register($attachment->guid);
        $stats = $attachment->stat();
        $last_modified =& $stats[9];


        $etag = md5("{$last_modified}{$attachment->name}{$attachment->mimetype}{$attachment->guid}");

        // Check etag and return 304 if necessary
        if (   $expires <> 0
            && $cache->content->_check_not_modified($last_modified, $etag))
        {
            if (!_midcom_headers_sent())
            {
                $cache->content->cache_control_headers();
                // Doublemakesure these are present
                $this->header('HTTP/1.0 304 Not Modified', 304);
                $this->header("ETag: {$etag}");
            }
            while(@ob_end_flush());
            debug_add("End of MidCOM run: {$_SERVER['REQUEST_URI']}");
            _midcom_stop_request();
        }

        $f = $attachment->open('r');
        if (! $f)
        {
            throw new midcom_error('Failed to open attachment for reading: ' . midcom_connection::get_error_string());
        }

        $this->header("ETag: {$etag}");
        $cache->content->content_type($attachment->mimetype);
        $cache->content->register_sent_header("Content-Type: {$attachment->mimetype}");
        $this->header("Last-Modified: " . gmdate("D, d M Y H:i:s", $last_modified) . ' GMT');
        $this->header("Content-Length: " . $stats[7]);
        $this->header("Content-Description: {$attachment->title}");
        $cache->content->register_sent_header("Content-Description: {$attachment->title}");

        // PONDER: Support ranges ("continue download") somehow ?
        $this->header("Accept-Ranges: none");

        if ($expires > 0)
        {
            // If custom expiry now+expires is set use that
            $cache->content->expires(time()+$expires);
        }
        else if ($expires == 0)
        {
            // expires set to 0 means disable cache, so we shall
            $cache->content->no_cache();
        }
        // TODO: Check metadata service for the real expiry timestamp ?

        $cache->content->cache_control_headers();

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
        if (   !$cache->content->_uncached
            && !$cache->content->_no_cache)
        {
            $cache->content->write_meta_cache('A-' . $etag, $etag);
        }

        while(@ob_end_flush());

        if (!$send_att_body)
        {
            debug_add('NOT sending file (X-Sendfile will take care of that, _midcom_stop_request()ing so nothing has a chance the mess things up anymore');
            _midcom_stop_request();
        }

        fpassthru($f);
        $attachment->close();
        debug_add("End of MidCOM run: {$_SERVER['REQUEST_URI']}");
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
    private function _exec_file($component)
    {
        $context = midcom_core_context::get();
        // Sanity checks
        if ($context->parser->argc < 1)
        {
            throw new midcom_error_notfound("Script exec path invalid, need exactly one argument.");
        }

        // Build the path
        if ($component == 'midcom')
        {
            $path = MIDCOM_ROOT . '/midcom/exec/';
        }
        else
        {
            midcom::get('componentloader')->load($component);
            $context->set_key(MIDCOM_CONTEXT_COMPONENT, $component);
            $path = MIDCOM_ROOT . midcom::get('componentloader')->path_to_snippetpath($component) . '/exec/';
        }
        $path .= $context->parser->argv[0];

        if (   is_dir($path)
            && isset($context->parser->argv[1]))
        {
            $path .= '/' . $context->parser->argv[1];
        }

        if (is_dir($path))
        {
            throw new midcom_error_notfound("Path is a directory.");
        }

        if (! file_exists($path))
        {
            throw new midcom_error_notfound("File not found.");
        }

        // collect remaining arguments and put them to global vars.
        $GLOBALS['argc'] = $context->parser->argc--;
        $GLOBALS['argv'] = $context->parser->argv;
        array_shift($GLOBALS['argv']);

        midcom::get('cache')->content->enable_live_mode();

        $this->_status = MIDCOM_STATUS_CONTENT;

        // We seem to be in a valid place. Exec the file with the current
        // permissions.
        require($path);

        // Exit
        $this->finish();
        _midcom_stop_request('');
    }

    /**
     * Binds the current page view to a particular object. This will automatically connect such things like
     * metadata and toolbars to the correct object.
     *
     * @param midcom_core_dbaobject $object The DBA class instance to bind to.
     * @param string $page_class String describing page type, will be used for substyling
     */
    function bind_view_to_object($object, $page_class = 'default')
    {
        $context = midcom_core_context::get();

        // Bind the object into the view toolbar
        $view_toolbar = midcom::get('toolbars')->get_view_toolbar($context->id);
        $view_toolbar->bind_to($object);

        // Bind the object to the metadata service
        midcom::get('metadata')->bind_metadata_to_object(MIDCOM_METADATA_VIEW, $object, $context->id);

        // Push the object's CSS classes to metadata service
        $page_class = midcom::get('metadata')->get_object_classes($object, $page_class);
        midcom::get('metadata')->set_page_class($page_class, $context->id);

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
     * You may set either of the arguments to null to enforce default usage (based on NAP).
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
        $context = midcom_core_context::get();

        $context->set_key(MIDCOM_CONTEXT_LASTMODIFIED, $lastmodified);
        $context->set_key(MIDCOM_CONTEXT_PERMALINKGUID, $permalinkguid);
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
     * @param int $context_id The context from which the request metadata should be retrieved. Omit
     *     to use the current context.
     * @return Array An array with the two keys 'lastmodified' and 'permalinkguid' containing the
     *     values set with the setter pendant. For ease of use, there is also a key 'permalink'
     *     which contains a ready-made permalink.
     * @see set_26_request_metadata()
     */
    public function get_26_request_metadata($context_id = null)
    {
        $context = midcom_core_context::get($context_id);
        if ($context === false)
        {
            return array();
        }
        $meta = array
        (
            'lastmodified' => $context->get_key(MIDCOM_CONTEXT_LASTMODIFIED),
            'permalinkguid' => $context->get_key(MIDCOM_CONTEXT_PERMALINKGUID),
            'permalink' => midcom::get('permalinks')->create_permalink($context->get_key(MIDCOM_CONTEXT_PERMALINKGUID)),
        );

        if (   is_object($meta['lastmodified'])
            && is_a($meta['lastmodified'], 'midgard_datetime'))
        {
            // Midgard2 compatibility
            $meta['lastmodified'] = $meta['lastmodified']->format('U');
        }

        return $meta;
    }

    /**
     * Helper function that raises some PHP limits for resource-intensive tasks
     */
    public function disable_limits()
    {
        $stat = @ini_set('max_execution_time', $GLOBALS['midcom_config']['midcom_max_execution_time']);
        if (false === $stat)
        {
            debug_add('ini_set("max_execution_time", ' . $GLOBALS['midcom_config']['midcom_max_execution_time'] . ') returned false', MIDCOM_LOG_WARN);
        }
        $stat = @ini_set('memory_limit', $GLOBALS['midcom_config']['midcom_max_memory']);
        if (false === $stat)
        {
            debug_add('ini_set("memory_limit", ' . $GLOBALS['midcom_config']['midcom_max_memory'] . ') returned false', MIDCOM_LOG_WARN);
        }
    }
}
?>