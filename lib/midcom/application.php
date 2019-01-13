<?php
/**
 * @package midcom
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midgard\portable\api\blob;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Main controlling instance of the MidCOM Framework
 *
 * @property midcom_helper_serviceloader $serviceloader
 * @property midcom_services_i18n $i18n
 * @property midcom_helper__componentloader $componentloader
 * @property midcom_services_dbclassloader $dbclassloader
 * @property midcom_helper__dbfactory $dbfactory
 * @property midcom_helper_head $head
 * @property midcom_helper__styleloader $style
 * @property midcom_services_auth $auth
 * @property midcom_services_permalinks $permalinks
 * @property midcom_services_toolbars $toolbars
 * @property midcom_services_uimessages $uimessages
 * @property midcom_services_metadata $metadata
 * @property midcom_services_rcs $rcs
 * @property midcom_services__sessioning $session
 * @property midcom_services_indexer $indexer
 * @property midcom_config $config
 * @property midcom_services_cache $cache
 * @property midcom\events\dispatcher $dispatcher
 * @property midcom_debug $debug
 * @package midcom
 */
class midcom_application
{
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
     * @var Request
     */
    private $request;

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

    public function __construct()
    {
        midcom_compat_environment::initialize();
        midcom_exception_handler::register();
    }

    /**
     * Magic getter for service loading
     */
    public function __get($key)
    {
        return midcom::get($key);
    }

    /**
     * Magic setter
     */
    public function __set($key, $value)
    {
        return midcom::get()->$key = $value;
    }

    /**
     * Main MidCOM initialization.
     *
     * Initialize the Application class. Sets all private variables to a predefined
     * state.
     */
    public function initialize()
    {
        $this->request = Request::createFromGlobals();
        $this->debug->log("Start of MidCOM run" . $this->request->server->get('REQUEST_URI', ''));
        $this->request->setSession($this->session);
        $this->componentloader->load_all_manifests();
        $this->auth->check_for_login_session($this->request);

        /* Load and start up the cache system, this might already end the request
         * on a content cache hit. Note that the cache check hit depends on the i18n and auth code.
         */
        $this->cache->content->start_caching($this->request);

        // Initialize Context Storage
        $context = new midcom_core_context(0);
        $context->set_current();
        // Initialize the UI message stack from session
        $this->uimessages->initialize();
    }

    /* *************************************************************************
     * Control framework:
     * codeinit      - Handle the current request
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

        // Parse the URL
        $context->parser = $this->serviceloader->load(midcom_core_service_urlparser::class);
        $context->parser->parse(midcom_connection::get_url('argv'));

        $response = $this->_process($context, $this->request);
        $this->_output($context, !$this->skip_page_style, $response);
    }

    /**
     * Dynamically execute a subrequest and insert its output in place of the
     * function call.
     *
     * <b>Important Note</b> As with the Midgard Parser, dynamic_load strips a
     * trailing .html from the argument list before actually parsing it.
     *
     * It tries to load the component referenced with the URL $url and executes
     * it as if it was used as primary component.
     *
     * This is only possible if the system is in the Page-Style output phase. It
     * cannot be used within code-init or during the output phase of another
     * component.
     *
     * Example code, executed on a site's homepage, it will load the news listing from
     * the given URL and display it using a substyle of the node style that is assigned
     * to the loaded one:
     *
     * <code>
     * $blog = '/blog/latest/3/';
     * $substyle = 'homepage';
     * midcom::get()->dynamic_load("/midcom-substyle-{$substyle}/{$blog}");
     * </code>
     *
     * Results of dynamic_loads are cached with the system cache strategy
     *
     * @param string $url                The URL, relative to the Midgard Page, that is to be requested.
     */
    public function dynamic_load($url)
    {
        debug_add("Dynamic load of URL {$url}");

        if (substr($url, -5) == '.html') {
            $url = substr($url, 0, -5);
        }

        // Determine new Context ID and set current context,
        // enter that context and prepare its data structure.
        $oldcontext = midcom_core_context::get();
        $context = new midcom_core_context(null, $oldcontext->get_key(MIDCOM_CONTEXT_ROOTTOPIC));
        $uri = midcom_connection::get_url('self') . $url;
        $context->set_key(MIDCOM_CONTEXT_URI, $uri);

        $context->set_current();
        $cached = $this->cache->content->check_dl_hit($context->id);
        if ($cached !== false) {
            echo $cached;
            return;
        }

        // Parser Init: Generate arguments and instantiate it.
        $context->parser = $this->serviceloader->load(midcom_core_service_urlparser::class);
        $argv = $context->parser->tokenize($url);
        $context->parser->parse($argv);

        $request = $this->request->duplicate([], null, []);
        try {
            $response = $this->_process($context, $request);
        } catch (midcom_error $e) {
            if ($e instanceof midcom_error_notfound || $e instanceof midcom_error_forbidden) {
                $e->log();
                // Leave Context
                $oldcontext->set_current();
                return;
            }
            throw $e;
        }

        // Start another buffer for caching DL results
        ob_start();

        $this->_output($context, false, $response);

        $dl_cache_data = ob_get_contents();
        ob_end_flush();
        /* Cache DL the content */
        $this->cache->content->store_dl_content($context->id, $dl_cache_data);

        // Leave Context
        $oldcontext->set_current();
        $this->style->enter_context($oldcontext->id);
    }

    /**
     * Deliver a blob to the client. It will add the following HTTP Headers:
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
     * If expires is set to -1, no expires header gets sent.
     *
     * @param midcom_db_attachment $attachment    The attachment to be delivered.
     */
    public function serve_attachment(midcom_db_attachment $attachment)
    {
        // Doublecheck that this is registered
        $this->cache->content->register($attachment->guid);

        $blob = new blob($attachment->__object);
        $response = new BinaryFileResponse($blob->get_path());
        $last_modified = (int) $response->getLastModified()->format('U');
        $etag = md5("{$last_modified}{$attachment->name}{$attachment->mimetype}{$attachment->guid}");
        $response->setEtag($etag);

        if (!$response->isNotModified($this->request)) {
            $response->prepare($this->request);

            if ($this->config->get('attachment_xsendfile_enable')) {
                BinaryFileResponse::trustXSendfileTypeHeader();
                $response->headers->set('X-Sendfile-Type', 'X-Sendfile');
            }
        }
        $this->cache->content->cache_control_headers($response);
        // Store metadata in cache so _check_hit() can help us
        $this->cache->content->write_meta_cache('A-' . $etag, $etag);
        $response->send();

        debug_add("End of MidCOM run: " . $this->request->server->get('REQUEST_URI'));
        _midcom_stop_request();
    }

    /**
     * Exit from the framework, execute after all output has been made.
     *
     * Does all necessary clean-up work. Must be called after output is completed as
     * the last call of any MidCOM Page.
     *
     * <b>WARNING:</b> Anything done after calling this method will be lost.
     */
    public function finish()
    {
        // Shutdown content-cache (ie flush content to user :)
        $this->cache->content->finish_caching($this->request);

        // Shutdown rest of the caches
        $this->cache->shutdown();

        debug_add("End of MidCOM run: " . $this->request->server->get('REQUEST_URI'));
        _midcom_stop_request();
    }

    /**
     * Process the request
     *
     * Basically this method will parse the URL and search for a component that can
     * handle the request. If one is found, it will process the request, if not, it
     * will report an error, depending on the situation.
     *
     * Details: The logic will traverse the node tree, and for the last node it will load
     * the component that is responsible for it. This component gets the chance to
     * accept the request, which is basically a call to can_handle. If the component
     * declares to be able to handle the call, its handle function is executed. Depending
     * if the handle was successful or not, it will either display an HTTP error page or
     * prepares the content handler to display the content later on.
     *
     * If the parsing process doesn't find any component that declares to be able to
     * handle the request, an HTTP 404 - Not Found error is triggered.
     *
     * @param midcom_core_context $context
     * @param Request $request The request object
     * @return Response
     */
    private function _process(midcom_core_context $context, Request $request)
    {
        $urlmethods = new midcom_core_urlmethods($context);
        if ($response = $urlmethods->process()) {
            return $response;
        }

        return $context->run($request);
    }

    /**
     * Execute the output callback.
     *
     * Launches the output of the currently selected component. It is collected in an output buffer
     * to allow for relocates from style elements.
     *
     * It executes the content_handler that has been determined during the handle
     * phase. It fetches the content_handler from the Component Loader class cache.
     *
     * @param midcom_core_context $context
     * @param boolean $include_template
     * @param Response $response
     */
    private function _output(midcom_core_context $context, $include_template, Response $response)
    {
        // Enter Context
        $oldcontext = midcom_core_context::get();
        if ($oldcontext->id != $context->id) {
            debug_add("Entering Context {$context->id} (old Context: {$oldcontext->id})");
            $context->set_current();
        }

        $backup = $this->skip_page_style;
        $this->skip_page_style = !$include_template;
        $response->send();
        $this->skip_page_style = $backup;

        // Leave Context
        if ($oldcontext->id != $context->id) {
            debug_add("Leaving Context {$context->id} (new Context: {$oldcontext->id})");
            $oldcontext->set_current();
        }
    }

    /* *************************************************************************
     * Framework Access Helper functions
     */

    /**
     * Retrieves the name of the current host, fully qualified with protocol and
     * port.
     *
     * @return string Full Hostname (http[s]://www.my.domain.com[:1234])
     */
    function get_host_name()
    {
        return $this->request->getSchemeAndHttpHost();
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
        if (!$this->_cached_page_prefix) {
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
        if (!$this->_cached_host_prefix) {
            $host_name = $this->get_host_name();
            $host_prefix = midcom_connection::get_url('prefix');
            if (substr($host_prefix, 0, 1) != '/') {
                $host_prefix = "/{$host_prefix}";
            }
            if (substr($host_prefix, -1, 1) != '/') {
                $host_prefix .= '/';
            }
            $this->_cached_host_prefix = "{$host_name}{$host_prefix}";
        }

        return $this->_cached_host_prefix;
    }

    /* *************************************************************************
     * Generic Helper Functions not directly related with MidCOM:
     *
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
        $this->cache->content->register_sent_header($header);

        if (!is_null($response_code)) {
            // Send the HTTP response code as requested
            _midcom_header($header, true, $response_code);
        } else {
            _midcom_header($header);
        }
    }

    /**
     * Relocate to another URL.
     *
     * The helper actually can distinguish between site-local, absolute redirects and external
     * redirects. If the url does not start with http[s] or /, it is taken as a URL relative to
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
     * @param int $response_code HTTP response code to send with the relocation, from 3xx series
     */
    public function relocate($url, $response_code = 302)
    {
        $response = new midcom_response_relocate($url, $response_code);
        $response->send();
    }

    /**
     * Raise some PHP limits for resource-intensive tasks
     */
    public function disable_limits()
    {
        $stat = @ini_set('max_execution_time', $this->config->get('midcom_max_execution_time'));
        if (false === $stat) {
            debug_add('ini_set("max_execution_time", ' . $this->config->get('midcom_max_execution_time') . ') returned false', MIDCOM_LOG_WARN);
        }
        $stat = @ini_set('memory_limit', $this->config->get('midcom_max_memory'));
        if (false === $stat) {
            debug_add('ini_set("memory_limit", ' . $this->config->get('midcom_max_memory') . ') returned false', MIDCOM_LOG_WARN);
        }
    }
}
