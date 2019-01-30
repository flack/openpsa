<?php
/**
 * @package midcom
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Main controlling instance of the MidCOM Framework
 *
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
     * @var HttpKernelInterface
     */
    private $httpkernel;

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

    public function __construct(HttpKernelInterface $httpkernel)
    {
        $this->httpkernel = $httpkernel;
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
        midcom::get()->$key = $value;
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
        midcom_core_context::enter(midcom_connection::get_url('uri'));
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
     */
    public function codeinit()
    {
        $context = midcom_core_context::get();
        $context->set_key(MIDCOM_CONTEXT_URI, midcom_connection::get_url('uri'));
        $this->request->attributes->set('context', $context);

        $this->httpkernel->handle($this->request)->send();
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
        $url = midcom_connection::get_url('prefix') . $url;

        // Determine new Context ID and set current context,
        // enter that context and prepare its data structure.
        $oldcontext = midcom_core_context::get();
        $context = midcom_core_context::enter($url, $oldcontext->get_key(MIDCOM_CONTEXT_ROOTTOPIC));

        $cached = $this->cache->content->check_dl_hit($context->id);
        if ($cached !== false) {
            echo $cached;
            midcom_core_context::leave();
            return;
        }

        $request = $this->request->duplicate([], null, []);
        $request->attributes->set('context', $context);

        try {
            $response = $this->httpkernel->handle($request, HttpKernelInterface::SUB_REQUEST, false);
        } catch (midcom_error $e) {
            if ($e instanceof midcom_error_notfound || $e instanceof midcom_error_forbidden) {
                $e->log();
                midcom_core_context::leave();
                return;
            }
            throw $e;
        }

        $backup = $this->skip_page_style;
        $this->skip_page_style = true;
        $dl_cache_data = $response->getContent();
        $this->skip_page_style = $backup;
        echo $dl_cache_data;

        /* Cache DL the content */
        $this->cache->content->store_dl_content($context->id, $dl_cache_data);

        midcom_core_context::leave();
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

        if ($response_code !== null) {
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
