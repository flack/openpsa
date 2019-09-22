<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * This is the Output Caching Engine of MidCOM. It will intercept page output,
 * map it using the currently used URL and use the cached output on subsequent
 * requests.
 *
 * <b>Important note for application developers</b>
 *
 * Please read the documentation of the following functions thoroughly:
 *
 * - midcom_services_cache_module_content::no_cache();
 * - midcom_services_cache_module_content::uncached();
 * - midcom_services_cache_module_content::expires();
 * - midcom_services_cache_module_content::invalidate_all();
 * - midcom_services_cache_module_content::content_type();
 * - midcom_services_cache_module_content::enable_live_mode();
 *
 * You have to use these functions everywhere where it is applicable or the cache
 * will not work reliably.
 *
 * <b>Caching strategy</b>
 *
 * The cache takes three parameters into account when storing in or retrieving from
 * the cache: The current User ID, the current language and the request's URL.
 *
 * Only on a complete match a cached page is displayed, which should take care of any
 * permission checks done on the page. When you change the permissions of users, you
 * need to manually invalidate the cache though, as MidCOM currently cannot detect
 * changes like this (of course, this is true if and only if you are not using a
 * MidCOM to change permissions).
 *
 * When the HTTP request is not cacheable, the caching engine will automatically and
 * transparently go into no_cache mode for that request only. This feature
 * does neither invalidate the cache or drop the page that would have been delivered
 * normally from the cache. If you change the content, you need to do that yourself.
 *
 * HTTP 304 Not Modified support is built into this module, and will send a 304 reply if applicable.
 *
 * <b>Module configuration (see also midcom_config)</b>
 *
 * - <i>string cache_module_content_name</i>: The name of the cache database to use. This should usually be tied to the actual
 *   MidCOM site to have exactly one cache per site. This is mandatory (and populated by a sensible default
 *   by midcom_config, see there for details).
 * - <i>boolean cache_module_content_uncached</i>: Set this to true to prevent the saving of cached pages. This is useful
 *   for development work, as all other headers (like E-Tag or Last-Modified) are generated
 *   normally. See the uncached() and _uncached members.
 *
 * @package midcom.services
 */
class midcom_services_cache_module_content extends midcom_services_cache_module
{
    /**
     * Flag, indicating whether the current page may be cached. If
     * false, the usual no-cache headers will be generated.
     *
     * @var boolean
     */
    private $_no_cache = false;

    /**
     * Page expiration in seconds. If null (unset), the page does
     * not expire.
     *
     * @var int
     */
    private $_expires;

    /**
     * An array storing all HTTP headers registered through register_sent_header().
     * They will be sent when a cached page is delivered.
     *
     * @var array
     */
    private $_sent_headers = [];

    /**
     * Set this to true if you want to inhibit storage of the generated pages in
     * the cache database. All other headers will be created as usual though, so
     * 304 processing will kick in for example.
     *
     * @var boolean
     */
    private $_uncached = false;

    /**
     * Controls cache headers strategy
     * 'no-cache' activates no-cache mode that actively tries to circumvent all caching
     * 'revalidate' is the default which sets must-revalidate. Expiry defaults to current time, so this effectively behaves like no-cache if expires() was not called
     * 'public' and 'private' enable caching with the cache-control header of the same name, default expiry timestamps are generated using the default_lifetime
     *
     * @var string
     */
    private $_headers_strategy = 'revalidate';

    /**
     * Controls cache headers strategy for authenticated users, needed because some proxies store cookies, too,
     * making a horrible mess when used by mix of authenticated and non-authenticated users
     *
     * @see $_headers_strategy
     * @var string
     */
    private $_headers_strategy_authenticated = 'private';

    /**
     * Default lifetime of page for public/private headers strategy
     * When generating the default expires header this is added to time().
     *
     * @var int
     */
    private $_default_lifetime = 0;

    /**
     * Default lifetime of page for public/private headers strategy for authenticated users
     *
     * @see $_default_lifetime
     * @var int
     */
    private $_default_lifetime_authenticated = 0;

    /**
     * Cache backend instance.
     *
     * @var Doctrine\Common\Cache\CacheProvider
     */
    private $_meta_cache;

    /**
     * A cache backend used to store the actual cached pages.
     *
     * @var Doctrine\Common\Cache\CacheProvider
     */
    private $_data_cache;

    /**
     * GUIDs loaded per context in this request
     */
    private $context_guids = [];

    /**
     * @param GetResponseEvent $event
     */
    public function on_request(GetResponseEvent $event)
    {
        if ($event->isMasterRequest()) {
            $request = $event->getRequest();
            /* Load and start up the cache system, this might already end the request
             * on a content cache hit. Note that the cache check hit depends on the i18n and auth code.
             */
            if ($response = $this->_check_hit($request)) {
                $event->setResponse($response);
            }
        }
    }

    /**
     * This function holds the cache hit check mechanism. It searches the requested
     * URL in the cache database. If found, it checks, whether the cache page has
     * expired. If not, the response is returned. In all other cases this method simply
     * returns void.
     *
     * The midcom-cache URL methods are handled before checking for a cache hit.
     *
     * Also, any HTTP POST request will automatically circumvent the cache so that
     * any component can process the request. It will set no_cache automatically
     * to avoid any cache pages being overwritten by, for example, search results.
     *
     * Note, that HTTP GET is <b>not</b> checked this way, as GET requests can be
     * safely distinguished by their URL.
     *
     * @param Request $request The request object
     * @return void|Response
     */
    private function _check_hit(Request $request)
    {
        foreach (midcom_connection::get_url('argv') as $arg) {
            if (in_array($arg, ["midcom-cache-invalidate", "midcom-cache-nocache"])) {
                // Don't cache these.
                debug_add("uncached: $arg");
                return;
            }
        }

        if (!$request->isMethodCacheable()) {
            debug_add('Request method is not cacheable, setting no_cache');
            $this->no_cache();
            return;
        }

        // Check for uncached operation
        if ($this->_uncached) {
            debug_add("Uncached mode");
            return;
        }

        // Check that we have cache for the identifier
        $request_id = $this->generate_request_identifier($request);
        // Load metadata for the content identifier connected to current request
        $content_id = $this->_meta_cache->fetch($request_id);
        if ($content_id === false) {
            debug_add("MISS {$request_id}");
            // We have no information about content cached for this request
            return;
        }
        debug_add("HIT {$request_id}");

        $data = $this->_meta_cache->fetch($content_id);
        if ($data === false) {
            debug_add("MISS meta_cache {$content_id}");
            // Content cache data is missing
            return;
        }

        if (!isset($data['last-modified'])) {
            debug_add('Current page is in cache, but has insufficient information', MIDCOM_LOG_INFO);
            return;
        }

        debug_add("HIT {$content_id}");

        $response = new Response('', Response::HTTP_OK, $data);
        if (!$response->isNotModified($request)) {
            $content = $this->_data_cache->fetch($content_id);
            if ($content === false) {
                debug_add("Current page is in not in the data cache, possible ghost read.", MIDCOM_LOG_WARN);
                return;
            }
            $response->setContent($content);
        }
        // disable cache writing in on_response
        $this->_no_cache = true;
        return $response;
    }

    /**
     * This completes the output caching, post-processes it and updates the cache databases accordingly.
     *
     * The first step is to check against _no_cache pages, which will be delivered immediately
     * without any further post processing. Afterwards, the system will complete the sent
     * headers by adding all missing headers. Note, that E-Tag will be generated always
     * automatically, you must not set this in your component.
     *
     * If the midcom configuration option cache_uncached is set or the corresponding runtime function
     * has been called, the cache file will not be written, but the header stuff will be added like
     * usual to allow for browser-side caching.
     *
     * @param FilterResponseEvent $event The request object
     */
    public function on_response(FilterResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }
        $response = $event->getResponse();
        if ($response instanceof BinaryFileResponse) {
            return;
        }
        foreach ($this->_sent_headers as $header => $value) {
            // This can happen in streamed responses which enable_live_mode
            if (!headers_sent()) {
                header_remove($header);
            }
            $response->headers->set($header, $value);
        }
        $request = $event->getRequest();
        if ($this->_no_cache) {
            $response->prepare($request);
            return;
        }

        $cache_data = $response->getContent();

        // Register additional Headers around the current output request
        $this->complete_sent_headers($response);
        $response->prepare($request);

        // Generate E-Tag header.
        if (empty($cache_data)) {
            $etag = md5(serialize($response->headers->all()));
        } else {
            $etag = md5($cache_data);
        }
        $response->setEtag($etag);

        if ($this->_uncached) {
            debug_add('Not writing cache file, we are in uncached operation mode.');
            return;
        }
        $content_id = 'C-' . $etag;
        $this->write_meta_cache($content_id, $request, $response);
        $this->_data_cache->save($content_id, $cache_data);
    }

    /**
     * Generate a valid cache identifier for a context of the current request
     */
    private function generate_request_identifier(Request $request) : string
    {
        $context = $request->attributes->get('context')->id;
        // Cache the request identifier so that it doesn't change between start and end of request
        static $identifier_cache = [];
        if (isset($identifier_cache[$context])) {
            return $identifier_cache[$context];
        }

        $module_name = midcom::get()->config->get('cache_module_content_name');
        if ($module_name == 'auto') {
            $module_name = midcom_connection::get_unique_host_name();
        }
        $identifier_source = 'CACHE:' . $module_name;

        $cache_strategy = midcom::get()->config->get('cache_module_content_caching_strategy');

        switch ($cache_strategy) {
            case 'memberships':
                if (!midcom_connection::get_user()) {
                    $identifier_source .= ';USER=ANONYMOUS';
                    break;
                }
                $mc = new midgard_collector('midgard_member', 'uid', midcom_connection::get_user());
                $mc->set_key_property('gid');
                $mc->execute();
                $gids = $mc->list_keys();
                $identifier_source .= ';GROUPS=' . implode(',', array_keys($gids));
                break;
            case 'public':
                $identifier_source .= ';USER=EVERYONE';
                break;
            case 'user':
            default:
                $identifier_source .= ';USER=' . midcom_connection::get_user();
                break;
        }

        $identifier_source .= ';URL=' . $request->getRequestUri();
        debug_add("Generating context {$context} request-identifier from: {$identifier_source}");

        $identifier_cache[$context] = 'R-' . md5($identifier_source);
        return $identifier_cache[$context];
    }

    /**
     * Initialize the cache.
     *
     * The first step is to initialize the cache backends. The names of the
     * cache backends used for meta and data storage are derived from the name
     * defined for this module (see the 'name' configuration parameter above).
     * The name is used directly for the meta data cache, while the actual data
     * is stored in a backend postfixed with '_data'.
     *
     * After core initialization, the module checks for a cache hit (which might
     * trigger the delivery of the cached page and exit) and start the output buffer
     * afterwards.
     */
    public function _on_initialize()
    {
        $backend_config = midcom::get()->config->get('cache_module_content_backend');
        if (!isset($backend_config['directory'])) {
            $backend_config['directory'] = 'content/';
        }
        if (!isset($backend_config['driver'])) {
            $backend_config['driver'] = 'null';
        }

        $this->_meta_cache = $this->_create_backend('content_meta', $backend_config);
        $this->_data_cache = $this->_create_backend('content_data', $backend_config);

        $this->_uncached = midcom::get()->config->get('cache_module_content_uncached');
        $this->_headers_strategy = $this->get_strategy('cache_module_content_headers_strategy');
        $this->_headers_strategy_authenticated = $this->get_strategy('cache_module_content_headers_strategy_authenticated');
        $this->_default_lifetime = (int)midcom::get()->config->get('cache_module_content_default_lifetime');
        $this->_default_lifetime_authenticated = (int)midcom::get()->config->get('cache_module_content_default_lifetime_authenticated');

        if ($this->_headers_strategy == 'no-cache') {
            $this->no_cache();
        }
    }

    private function get_strategy(string $name) : string
    {
        $strategy = strtolower(midcom::get()->config->get($name));
        $allowed = ['no-cache', 'revalidate', 'public', 'private'];
        if (!in_array($strategy, $allowed)) {
            throw new midcom_error($name . ' is not valid, try ' . implode(', ', $allowed));
        }
        return $strategy;
    }

    /**
     * Call this, if the currently processed output must not be cached for any
     * reason. Dynamic pages with sensitive content are a candidate for this
     * function.
     *
     * Note, that this will prevent <i>any</i> content invalidation related headers
     * like E-Tag to be generated automatically, and that the appropriate
     * no-store/no-cache headers from HTTP 1.1 and HTTP 1.0 will be sent automatically.
     * This means that there will also be no 304 processing.
     *
     * You should use this only for sensitive content. For simple dynamic output,
     * you are strongly encouraged to use the less strict uncached() function.
     *
     * @see uncached()
     */
    public function no_cache(Response $response = null)
    {
        $settings = 'no-store, no-cache, must-revalidate';
        // PONDER: Send expires header (set to long time in past) as well ??

        if ($response) {
            $response->headers->set('Cache-Control', $settings);
        } elseif (!$this->_no_cache) {
            if (headers_sent()) {
                debug_add('Warning, we should move to no_cache but headers have already been sent, skipping header transmission.', MIDCOM_LOG_ERROR);
            } else {
                midcom::get()->header('Cache-Control: ' . $settings);
            }
        }
        $this->_no_cache = true;
    }

    /**
     * Call this, if the currently processed output must not be cached for any
     * reason. Dynamic pages or form processing results are the usual candidates
     * for this mode.
     *
     * Note, that this will still keep the caching engine active so that it can
     * add the usual headers (ETag, Expires ...) in respect to the no_cache flag.
     * As well, at the end of the processing, the usual 304 checks are done, so if
     * your page doesn't change in respect of E-Tag and Last-Modified, only a 304
     * Not Modified reaches the client.
     *
     * Essentially, no_cache behaves the same way as if the uncached configuration
     * directive is set to true, it is just limited to a single request.
     *
     * If you need a higher level of client side security, to avoid storage of sensitive
     * information on the client side, you should use no_cache instead.
     *
     * @see no_cache()
     */
    public function uncached($uncached = true)
    {
        $this->_uncached = $uncached;
    }

    /**
     * Sets the expiration time of the current page (Unix (GMT) Timestamp).
     *
     * <b>Note:</B> This generate error call will add browser-side cache control
     * headers as well to force a browser to revalidate a page after the set
     * expiry.
     *
     * You should call this at all places where you have timed content in your
     * output, so that the page will be regenerated once a certain article has
     * expired.
     *
     * Multiple calls to expires will only save the
     * "youngest" timestamp, so you can safely call expires where appropriate
     * without respect to other values.
     *
     * The cache's default (null) will disable the expires header. Note, that once
     * an expiry time on a page has been set, it is not possible, to reset it again,
     * this is for dynamic_load situation, where one component might depend on a
     * set expiry.
     *
     * @param int $timestamp The UNIX timestamp from which the cached page should be invalidated.
     */
    public function expires($timestamp)
    {
        if (   $this->_expires === null
            || $this->_expires > $timestamp) {
            $this->_expires = $timestamp;
        }
    }

    /**
     * Sets the content type for the current page. The required HTTP Headers for
     * are automatically generated, so, to the contrary of expires, you just have
     * to set this header accordingly.
     *
     * This is usually set automatically by MidCOM for all regular HTML output and
     * for all attachment deliveries. You have to adapt it only for things like RSS
     * output.
     *
     * @param string $type    The content type to use.
     */
    public function content_type($type)
    {
        midcom::get()->header('Content-Type: ' . $type);
    }

    /**
     * Put the cache into a "live mode". This will disable the
     * cache during runtime, correctly flushing the output buffer (if it's not empty)
     * and sending cache control headers.
     *
     * The midcom-exec URL handler of the core will automatically enable live mode.
     *
     * @see midcom_application::_exec_file()
     */
    public function enable_live_mode()
    {
        $this->no_cache();
        Response::closeOutputBuffers(0, ob_get_length() > 0);
    }

    /**
     * Store a sent header into the cache database, so that it will
     * be resent when the cache page is delivered. midcom_application::header()
     * will automatically call this function, you need to do this only if you use
     * the PHP header function.
     *
     * @param string $header The header that was sent.
     */
    public function register_sent_header($header)
    {
        if (strpos($header, ': ') !== false) {
            list($header, $value) = explode(': ', $header, 2);
            $this->_sent_headers[$header] = $value;
        }
    }

    /**
     * Looks for list of content and request identifiers paired with the given guid
     * and removes all of those from the caches.
     *
     * {@inheritDoc}
     */
    public function invalidate($guid, $object = null)
    {
        $guidmap = $this->_meta_cache->fetch($guid);
        if ($guidmap === false) {
            debug_add("No entry for {$guid} in meta cache, ignoring invalidation request.");
            return;
        }

        foreach ($guidmap as $content_id) {
            if ($this->_meta_cache->contains($content_id)) {
                $this->_meta_cache->delete($content_id);
            }

            if ($this->_data_cache->contains($content_id)) {
                $this->_data_cache->delete($content_id);
            }
        }
    }

    /**
     * All objects loaded within a request are stored into a list for cache invalidation purposes
     */
    public function register($guid)
    {
        // Check for uncached operation
        if ($this->_uncached) {
            return;
        }

        $context = midcom_core_context::get()->id;
        if ($context != 0) {
            // We're in a dynamic_load, register it for that as well
            if (!isset($this->context_guids[$context])) {
                $this->context_guids[$context] = [];
            }
            $this->context_guids[$context][] = $guid;
        }

        // Register all GUIDs also to the root context
        if (!isset($this->context_guids[0])) {
            $this->context_guids[0] = [];
        }
        $this->context_guids[0][] = $guid;
    }

    /**
     * Writes meta-cache entry from context data using given content id
     * Used to be part of on_request, but needed by serve-attachment method in midcom_core_urlmethods as well
     */
    public function write_meta_cache($content_id, Request $request, Response $response)
    {
        if (   $this->_uncached
            || $this->_no_cache) {
            return;
        }

        if ($this->_expires !== null) {
            $lifetime = $this->_expires - time();
        } else {
            // Use default expiry for cache entry, most components don't bother calling expires() properly
            $lifetime = $this->_default_lifetime;
        }

        // Construct cache identifier
        $request_id = $this->generate_request_identifier($request);

        $entries = [
            $request_id => $content_id,
            $content_id => $response->headers->all()
        ];
        $this->_meta_cache->saveMultiple($entries, $lifetime);

        // Cache where the object have been
        $context = midcom_core_context::get()->id;
        $this->store_context_guid_map($context, $content_id, $request_id);
    }

    private function store_context_guid_map($context, string $content_id, string $request_id)
    {
        // non-existent context
        if (!array_key_exists($context, $this->context_guids)) {
            return;
        }

        $maps = $this->_meta_cache->fetchMultiple($this->context_guids[$context]);
        $to_save = [];
        foreach ($this->context_guids[$context] as $guid) {
            // Getting old map from cache or create new, empty one
            $guidmap = $maps[$guid] ?? [];

            if (!in_array($content_id, $guidmap)) {
                $guidmap[] = $content_id;
                $to_save[$guid] = $guidmap;
            }

            if (!in_array($request_id, $guidmap)) {
                $guidmap[] = $request_id;
                $to_save[$guid] = $guidmap;
            }
        }

        $this->_meta_cache->saveMultiple($to_save);
    }

    public function check_dl_hit(Request $request)
    {
        if ($this->_no_cache) {
            return false;
        }
        $dl_request_id = 'DL' . $this->generate_request_identifier($request);
        $dl_content_id = $this->_meta_cache->fetch($dl_request_id);
        if ($dl_content_id === false) {
            return false;
        }

        return $this->_data_cache->fetch($dl_content_id);
    }

    public function store_dl_content($context, $dl_cache_data, Request $request)
    {
        if (   $this->_no_cache
            || $this->_uncached) {
            return;
        }
        $dl_request_id = 'DL' . $this->generate_request_identifier($request);
        $dl_content_id = 'DLC-' . md5($dl_cache_data);

        if ($this->_expires !== null) {
            $lifetime = $this->_expires - time();
        } else {
            // Use default expiry for cache entry, most components don't bother calling expires() properly
            $lifetime = $this->_default_lifetime;
        }
        $this->_meta_cache->save($dl_request_id, $dl_content_id, $lifetime);
        $this->_data_cache->save($dl_content_id, $dl_cache_data, $lifetime);
        // Cache where the object have been
        $this->store_context_guid_map($context, $dl_content_id, $dl_request_id);
    }

    /**
     * This little helper ensures that the headers Content-Length
     * and Last-Modified are present. The lastmod timestamp is taken out of the
     * component context information if it is populated correctly there; if not, the
     * system time is used instead.
     *
     * To force browsers to revalidate the page on every request (login changes would
     * go unnoticed otherwise), the Cache-Control header max-age=0 is added automatically.
     */
    private function complete_sent_headers(Response $response)
    {
        if ($date = $response->getLastModified()) {
            if ((int) $date->format('U') == -1) {
                debug_add("Failed to extract the timecode from the last modified header, defaulting to the current time.", MIDCOM_LOG_WARN);
                $response->setLastModified(new DateTime);
            }
        } else {
            /* Determine Last-Modified using MidCOM's component context,
             * Fallback to time() if this fails.
             */
            $time = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_LASTMODIFIED) ?: time();
            $response->setLastModified(DateTime::createFromFormat('U', (string) $time));
        }

        if (!$response->headers->has('Content-Length')) {
            /* TODO: Doublecheck the way this is handled, now we just don't send it
             * if headers_strategy implies caching */
            if (!in_array($this->_headers_strategy, ['public', 'private'])) {
                $response->headers->set("Content-Length", strlen($response->getContent()));
            }
        }

        $this->cache_control_headers($response);
    }

    /**
     * @param Response $response
     */
    public function cache_control_headers(Response $response)
    {
        // Just to be sure not to mess the headers sent by no_cache in case it was called
        if ($this->_no_cache) {
            $this->no_cache($response);
        } else {
            // Add Expiration and Cache Control headers
            $strategy = $this->_headers_strategy;
            $default_lifetime = $this->_default_lifetime;
            if (   midcom::get()->auth->is_valid_user()
                || midcom_connection::get_user()) {
                $strategy = $this->_headers_strategy_authenticated;
                $default_lifetime = $this->_default_lifetime_authenticated;
            }

            $now = time();
            if ($strategy == 'revalidate') {
                // If expires is not set, we force the client to revalidate every time.
                // The timeout of a content cache entry is not affected by this.
                $expires = $this->_expires ?? $now;
            } else {
                $expires = $this->_expires ?? $now + $default_lifetime;
                if ($strategy == 'private') {
                    $response->setPrivate();
                } else {
                    $response->setPublic();
                }
            }
            $max_age = $expires - $now;

            $response
                ->setExpires(DateTime::createFromFormat('U', $expires))
                ->setMaxAge($max_age);
            if ($max_age == 0) {
                $response->headers->addCacheControlDirective('must-revalidate');
            }
        }
    }
}
