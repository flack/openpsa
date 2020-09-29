<?php
/**
 * @package midcom
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use midcom\bundle\midcomBundle;

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
 * @property Symfony\Component\EventDispatcher\EventDispatcher $dispatcher
 * @property midcom_debug $debug
 * @package midcom
 */
class midcom_application extends Kernel
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
     * immediately after the handle phase
     *
     * Changing this flag after the handle phase or for dynamically loaded
     * components won't change anything.
     *
     * @var boolean
     */
    public $skip_page_style = false;

    private $project_dir;

    /**
     * @var midcom_config
     */
    private $cfg;

    public function __construct(string $environment, bool $debug)
    {
        midcom_compat_environment::initialize();
        $this->request = Request::createFromGlobals();
        $this->cfg = new midcom_config;
        parent::__construct($environment, $debug);
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        if (file_exists($this->getProjectDir() . '/config/services.yml')) {
            $loader->load($this->getProjectDir() . '/config/services.yml');
        }
        if ($classes = midcom::get_registered_service_classes()) {
            $loader->load(function (ContainerBuilder $container) use ($classes) {
                foreach ($classes as $id => $class) {
                    $container->findDefinition($id)->setClass($class);
                }
            });
        }
    }

    protected function initializeContainer()
    {
        parent::initializeContainer();
        $this->container->set('config', $this->cfg);
    }

    protected function buildContainer()
    {
        $container = parent::buildContainer();
        $this->cfg->export_to($container);
        return $container;
    }

    public function registerBundles()
    {
        return [new midcomBundle];
    }

    public function getProjectDir()
    {
        if ($this->project_dir === null) {
            if (basename(dirname(__DIR__, 4)) === 'vendor') {
                // this is the case where we're installed as a dependency
                $this->project_dir = dirname(__DIR__, 5);
            } else {
                $this->project_dir = dirname(__DIR__, 2);
            }
        }
        return $this->project_dir;
    }

    public function getCacheDir()
    {
        return $this->cfg->get('cache_base_directory') ?: parent::getCacheDir();
    }

    /**
     * Magic getter for service loading
     */
    public function __get($key)
    {
        return $this->getContainer()->get($key);
    }

    /**
     * Magic setter
     */
    public function __set($key, $value)
    {
        $this->getContainer()->set($key, $value);
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
        try {
            $response = $this->handle($this->request);
            $response->send();
            $this->terminate($this->request, $response);
        } catch (Error $e) {
            $this->getHttpKernel()->terminateWithException($e);
        }
    }

    /**
     * Dynamically execute a subrequest and insert its output in place of the
     * function call.
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
    public function dynamic_load(string $url, string $substyle = '')
    {
        debug_add("Dynamic load of URL {$url}");
        $url = midcom_connection::get_url('prefix') . $url;

        // Determine new Context ID and set current context,
        // enter that context and prepare its data structure.
        $oldcontext = midcom_core_context::get();
        $context = midcom_core_context::enter($url, $oldcontext->get_key(MIDCOM_CONTEXT_ROOTTOPIC));
        if ($substyle) {
            $context->set_key(MIDCOM_CONTEXT_SUBSTYLE, $substyle);
        }

        $request = $this->request->duplicate([], null, []);
        $request->attributes->set('context', $context);

        $cached = $this->cache->content->check_dl_hit($request);
        if ($cached !== false) {
            echo $cached;
            midcom_core_context::leave();
            return;
        }

        $backup = $this->skip_page_style;
        $this->skip_page_style = true;
        try {
            $response = $this->handle($request, HttpKernelInterface::SUB_REQUEST, false);
        } catch (midcom_error $e) {
            if ($e instanceof midcom_error_notfound || $e instanceof midcom_error_forbidden) {
                $e->log();
                midcom_core_context::leave();
                return;
            }
            throw $e;
        } finally {
            $this->skip_page_style = $backup;
        }

        $dl_cache_data = $response->getContent();
        echo $dl_cache_data;

        /* Cache DL the content */
        $this->cache->content->store_dl_content($context->id, $dl_cache_data, $request);

        midcom_core_context::leave();
    }

    /**
     * Stop the PHP process
     *
     * @deprecated
     */
    public function finish()
    {
        _midcom_stop_request();
    }

    /* *************************************************************************
     * Framework Access Helper functions
     */

    /**
     * Retrieves the name of the current host, fully qualified with protocol and
     * port (http[s]://www.my.domain.com[:1234])
     */
    function get_host_name() : string
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
     */
    function get_page_prefix() : string
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
     */
    function get_host_prefix() : string
    {
        if (!$this->_cached_host_prefix) {
            $host_name = $this->get_host_name();
            $host_prefix = midcom_connection::get_url('prefix');
            if (!str_starts_with($host_prefix, '/')) {
                $host_prefix = "/{$host_prefix}";
            }
            if (!str_ends_with($host_prefix, '/')) {
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
     */
    public function header(string $header, int $response_code = null)
    {
        $this->cache->content->register_sent_header($header);
        midcom_compat_environment::get()->header($header, true, $response_code);
    }

    /**
     * Relocate to another URL.
     *
     * Note, that this function automatically makes the page uncacheable, calls
     * midcom_finish and exit, so it will never return. If the headers have already
     * been sent, this will leave you with a partially completed page, so beware.
     */
    public function relocate(string $url, int $response_code = 302)
    {
        $response = new midcom_response_relocate($url, $response_code);
        $response->send();
        $this->finish();
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
