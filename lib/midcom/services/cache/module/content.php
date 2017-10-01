<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Symfony\Component\HttpFoundation\Response;

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
 * Special care is taken when HTTP POST request data is present. In that case, the
 * caching engine will automatically and transparently go into no_cache mode for
 * that request only, allowing your application to process form data. This feature
 * does neither invalidate the cache or drop the page that would have been delivered
 * normally from the cache. If you change the content, you need to do that yourself.
 *
 * HTTP 304 Not Modified support is built into this module, and it will kill the
 * output buffer and send a 304 reply if applicable.
 *
 * <b>Internal notes</b>
 *
 * This module's startup code will exit with _midcom_stop_request() in case of
 * a cache hit, and it will enclose the entire request using PHP's output buffering.
 *
 * <b>Module configuration (see also midcom/config/main.php)</b>
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
    private $_expires = null;

    /**
     * The time of the last modification, set during auto-header-completion.
     *
     * @var int
     */
    private $_last_modified = 0;

    /**
     * An array storing all HTTP headers registered through register_sent_header().
     * They will be sent when a cached page is delivered.
     *
     * @var array
     */
    private $_sent_headers = [];

    /**
     * The MIME content-type of the current request. It defaults to text/html, but
     * must be set correctly, so that the client gets the correct type delivered
     * upon cache deliveries.
     *
     * @var string
     */
    private $_content_type = 'text/html';

    /**
     * Internal flag indicating whether the output buffering is active.
     *
     * @var boolean
     */
    private $_obrunning = false;

    /**
     * This flag is true if the live mode has been activated. This prevents the
     * cache processing at the end of the request.
     *
     * @var boolean
     */
    private $_live_mode = false;

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
     * 'revalidate' is the default which sets must-revalidate and expiry to current time
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
    private $_meta_cache = null;

    /**
     * A cache backend used to store the actual cached pages.
     *
     * @var Doctrine\Common\Cache\CacheProvider
     */
    private $_data_cache = null;

    /**
     * GUIDs loaded per context in this request
     */
    private $context_guids = [];

    /**
     * Forced headers
     */
    private $_force_headers = [];

    /**
     * Generate a valid cache identifier for a context of the current request
     */
    private function generate_request_identifier($context, $customdata = null)
    {
        // Cache the request identifier so that it doesn't change between start and end of request
        static $identifier_cache = [];
        if (isset($identifier_cache[$context])) {
            // FIXME: Use customdata here, too
            return $identifier_cache[$context];
        }

        $module_name = midcom::get()->config->get('cache_module_content_name');
        if ($module_name == 'auto') {
            $module_name = midcom_connection::get_unique_host_name();
        }
        $identifier_source = 'CACHE:' . $module_name;

        if (!isset($customdata['cache_module_content_caching_strategy'])) {
            $cache_strategy = midcom::get()->config->get('cache_module_content_caching_strategy');
        } else {
            $cache_strategy = $customdata['cache_module_content_caching_strategy'];
        }

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

        if (midcom::get()) {
            $identifier_source .= ';URL=' . midcom_core_context::get()->get_key(MIDCOM_CONTEXT_URI);
        } else {
            $identifier_source .= ';URL=' . $_SERVER['REQUEST_URI'];
        }
        // check_dl_hit needs to take config changes into account...
        if (!is_null($customdata)) {
            $identifier_source .= ';' . serialize($customdata);
        }

        debug_add("Generating context {$context} request-identifier from: {$identifier_source}");
        debug_print_r('$customdata was: ', $customdata);

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
        $this->_headers_strategy = strtolower(midcom::get()->config->get('cache_module_content_headers_strategy'));
        $this->_headers_strategy_authenticated = strtolower(midcom::get()->config->get('cache_module_content_headers_strategy_authenticated'));
        $this->_default_lifetime = (int)midcom::get()->config->get('cache_module_content_default_lifetime');
        $this->_default_lifetime_authenticated = (int)midcom::get()->config->get('cache_module_content_default_lifetime_authenticated');
        $this->_force_headers = midcom::get()->config->get('cache_module_content_headers_force');

        switch ($this->_headers_strategy) {
            case 'no-cache':
                $this->no_cache();
                break;
            case 'revalidate':
            case 'public':
            case 'private':
                break;
            default:
                $message = "Cache headers strategy '{$this->_headers_strategy}' is not valid, try 'no-cache', 'revalidate', 'public' or 'private'";
                debug_add($message, MIDCOM_LOG_ERROR);
                $this->no_cache();

                throw new midcom_error($message);
        }
    }

    /**
     * This function holds the cache hit check mechanism. It searches the requested
     * URL in the cache database. If found, it checks, whether the cache page has
     * expired. If not, the cached page is delivered to the client and processing
     * ends. In all other cases this method simply returns.
     *
     * The midcom-cache URL methods are handled before checking for a cache hit.
     *
     * Also, any HTTP POST request will automatically circumvent the cache so that
     * any component can process the request. It will set no_cache automatically
     * to avoid any cache pages being overwritten by, for example, search results.
     *
     * Note, that HTTP GET is <b>not</b> checked this way, as GET requests can be
     * safely distinguished by their URL.
     */
    private function _check_hit()
    {
        foreach (midcom_connection::get_url('argv') as $arg) {
            switch ($arg) {
                case "midcom-cache-invalidate":
                case "midcom-cache-nocache":
                    // Don't cache these.
                    debug_add("X-MidCOM-cache: " . $arg . " uncached");
                    return;
            }
        }

        // Check for POST variables, if any is found, go for no_cache.
        if (count($_POST) > 0) {
            debug_add('POST variables have been found, setting no_cache and not checking for a hit.');
            $this->no_cache();
            return;
        }

        // Check for uncached operation
        if ($this->_uncached) {
            debug_add("Uncached mode");
            return;
        }

        // Check that we have cache for the identifier
        $request_id = $this->generate_request_identifier(0);
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

        if (!isset($data['last_modified'])) {
            debug_add('Current page is in cache, but has insufficient information', MIDCOM_LOG_INFO);
            return;
        }

        debug_add("X-MidCOM-meta-cache: HIT {$content_id}");

        // Check If-Modified-Since and If-None-Match, do content output only if
        // we have a not modified match.
        if (!$this->_check_not_modified($data['last_modified'], $data['etag'])) {
            $content = $this->_data_cache->fetch($content_id);
            if ($content === false) {
                debug_add("Current page is in not in the data cache, possible ghost read.", MIDCOM_LOG_WARN);
                return;
            }
            debug_add("HIT {$content_id}");

            array_map('_midcom_header', $data['sent_headers']);

            echo $content;
        } else {
            // Drop the output buffer, if any.
            $this->disable_ob();

            // Emit the 304 header, then exit.
            _midcom_header('HTTP/1.0 304 Not Modified');
            _midcom_header("ETag: {$data['etag']}");
            array_map('_midcom_header', $data['sent_headers']);
        }

        _midcom_stop_request();
    }

    /**
     * Start the output cache. Call this before any output
     * is made. MidCOM's startup sequence will automatically do this.
     */
    public function start_caching()
    {
        // Note, that check_hit might _midcom_stop_request().
        $this->_check_hit();

        ob_implicit_flush(false);
        ob_start();
        $this->_obrunning = true;
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
    public function no_cache($return = false)
    {
        $headers = [
            'Cache-Control' => ['no-store', 'no-cache', 'must-revalidate', 'post-check=0', 'pre-check=0'],
        ];

        //Suppress "Pragma: no-cache" header, because otherwise file downloads don't work in IE < 9 with https.
        if (   !isset($_SERVER['HTTPS'])
            || !isset($_SERVER['HTTP_USER_AGENT'])
            || !preg_match('/MSIE/', $_SERVER['HTTP_USER_AGENT'])) {
            $headers['Pragma'] = 'no-cache';
        }

        if (!$this->_no_cache) {
            $this->_no_cache = true;

            if (!$return) {
                if (_midcom_headers_sent()) {
                    // Whatever is wrong here, we return.
                    debug_add('Warning, we should move to no_cache but headers have already been sent, skipping header transmission.', MIDCOM_LOG_ERROR);
                    return;
                }
                $this->process_headers($headers, true);
                // PONDER: Send expires header (set to long time in past) as well ??
            }
        }
        if ($return) {
            return $headers;
        }
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
    public function uncached()
    {
        $this->_uncached = true;
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
        if (   is_null($this->_expires)
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
        $this->_content_type = $type;

        // Send header (don't register yet to avoid duplicates, this is done during finish
        // caching).
        $header = "Content-type: " . $this->_content_type;
        _midcom_header($header);
    }

    /**
     * Put the cache into a "live mode". This will disable the
     * cache during runtime, correctly flushing the output buffer and sending cache
     * control headers. You will not be able to send any additional headers after
     * executing this call therefore you should adjust the headers in advance.
     *
     * The midcom-exec URL handler of the core will automatically enable live mode.
     *
     * @see midcom_application::_exec_file()
     */
    public function enable_live_mode()
    {
        if ($this->_live_mode) {
            debug_add('Cannot enter live mode twice, ignoring request.', MIDCOM_LOG_WARN);
            return;
        }

        $this->_live_mode = true;
        $this->no_cache();

        if ($this->_obrunning) {
            // Flush any remaining output buffer.
            // We do this only if there is actually content in the output buffer. If not, we won't
            // send anything, so that you can still send HTTP Headers after enabling the live mode.
            // Check is for nonzero and non-false
            Response::closeOutputBuffers(0, ob_get_length() > 0);
            $this->_obrunning = false;
        }
    }

    public function disable_ob()
    {
        Response::closeOutputBuffers(0, false);
        $this->_obrunning = false;
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
        $this->_sent_headers[] = $header;
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
     * Checks whether the browser supplied if-modified-since or if-none-match headers
     * match the passed etag/last modified timestamp. If yes, true is returned. Otherwise
     * the function will return false
     *
     * If the headers have already been sent, something is definitely wrong, so we
     * ignore the request silently returning false.
     *
     * Note, that if both If-Modified-Since and If-None-Match are present, both must
     * actually match.
     *
     * @param int $last_modified The last modified timestamp of the current document. This timestamp
     *     is assumed to be in <i>local time</i>, and will be implicitly converted to a GMT time for
     *     correct HTTP header comparisons.
     * @param string $etag The etag header associated with the current document.
     * @return boolean Indicating match
     */
    function _check_not_modified($last_modified, $etag)
    {
        if (_midcom_headers_sent()) {
            debug_add("The headers have already been sent, cannot do a not modified check.", MIDCOM_LOG_INFO);
            return false;
        }

        // These variables are set to true if the corresponding header indicates a 304 is possible.
        $if_modified_since = false;
        $if_none_match = false;
        if (array_key_exists('HTTP_IF_NONE_MATCH', $_SERVER)) {
            if (trim($_SERVER['HTTP_IF_NONE_MATCH'], '"') != $etag) {
                // The E-Tag is different, so we cannot 304 here.
                debug_add("The HTTP supplied E-Tag requirement does not match: {$_SERVER['HTTP_IF_NONE_MATCH']} (!= {$etag})");
                return false;
            }
            $if_none_match = true;
        }
        if (array_key_exists('HTTP_IF_MODIFIED_SINCE', $_SERVER)) {
            $tmp = $_SERVER['HTTP_IF_MODIFIED_SINCE'];
            if (strpos($tmp, 'GMT') === false) {
                $tmp .= ' GMT';
            }
            $modified_since = strtotime($tmp);
            if ($modified_since < $last_modified) {
                // Last Modified does not match, so we cannot 304 here.
                debug_add("The supplied HTTP Last Modified requirement does not match: {$_SERVER['HTTP_IF_MODIFIED_SINCE']}.");
                debug_add("If-Modified-Since: ({$modified_since}) " . gmdate("D, d M Y H:i:s", $modified_since) . ' GMT');
                debug_add("Last-Modified: ({$last_modified}) " . gmdate("D, d M Y H:i:s", $last_modified) . ' GMT');
                return false;
            }
            $if_modified_since = true;
        }

        return ($if_modified_since || $if_none_match);
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
     */
    public function finish_caching()
    {
        // make it safe to call this multiple times
        // done this way since it's slightly less hacky than mucking about with the cache->_modules etc
        static $run_count = 0;
        if (++$run_count > 1) {
            return;
        }

        if ($this->_no_cache) {
            if ($this->_obrunning) {
                if (ob_get_contents()) {
                    ob_end_flush();
                } elseif (ob_get_level()) {
                    ob_end_clean();
                }
            }
            return;
        }

        $cache_data = ob_get_contents();
        $this->_obrunning = false;

        /**
         * WARNING:
         *   From here on anything added to content is not included in cached
         *   data, so make sure nothing content-wise is added after this
         */

        // Generate E-Tag header.
        if (strlen($cache_data) == 0) {
            $etag = md5(serialize($this->_sent_headers));
        } else {
            $etag = md5($cache_data);
        }

        midcom::get()->header("ETag: {$etag}");

        // Register additional Headers around the current output request
        // It has been sent already during calls to content_type
        $header = "Content-type: " . $this->_content_type;
        $this->register_sent_header($header);
        $this->_complete_sent_headers();
        //we need to run this after complete_sent_headers, since it's used for content-length calculation
        ob_end_clean();

        // If-Modified-Since / If-None-Match checks, if no match, flush the output.
        if (!$this->_check_not_modified($this->_last_modified, $etag)) {
            echo $cache_data;
        } else {
            _midcom_header('HTTP/1.0 304 Not Modified');
            _midcom_header("ETag: {$etag}");
            array_map('_midcom_header', $this->_sent_headers);
        }

        /**
         * WARNING:
         *   Stuff below here is executed *after* we have flushed output,
         *   so here we should only write out our caches but do nothing else
         */
        if ($this->_uncached) {
            debug_add('Not writing cache file, we are in uncached operation mode.');
            return;
        }
        $content_id = 'C-' . $etag;
        $this->write_meta_cache($content_id, $etag);
        $this->_data_cache->save($content_id, $cache_data);
    }

    /**
     * Writes meta-cache entry from context data using given content id
     * Used to be part of finish_caching, but needed by serve-attachment method in midcom_application as well
     */
    public function write_meta_cache($content_id, $etag)
    {
        if (   $this->_uncached
            || $this->_no_cache) {
            return;
        }

        if (!is_null($this->_expires)) {
            $lifetime = $this->_expires - time();
        } else {
            // Use default expiry for cache entry, most components don't bother calling expires() properly
            $lifetime = $this->_default_lifetime;
        }

        // Construct cache identifier
        $context = midcom_core_context::get()->id;
        $request_id = $this->generate_request_identifier($context);

        $entries = [
            $request_id => $content_id,
            $content_id => [
                'etag' => $etag,
                'last_modified' => $this->_last_modified,
                'sent_headers' => $this->_sent_headers
            ]
        ];

        $this->_meta_cache->saveMultiple($entries, $lifetime);

        // Cache where the object have been
        $this->store_context_guid_map($context, $content_id, $request_id);
    }

    private function store_context_guid_map($context, $content_id, $request_id)
    {
        // non-existent context
        if (!array_key_exists($context, $this->context_guids)) {
            return;
        }

        $maps = $this->_meta_cache->fetchMultiple($this->context_guids[$context]);
        $to_save = [];
        foreach ($this->context_guids[$context] as $guid) {
            // Getting old map from cache or create new, empty one
            $guidmap = (empty($maps[$guid])) ? [] : $maps[$guid];

            if (!in_array($content_id, $guidmap)) {
                $guidmap[] = $content_id;
                $to_save[$guid] = $guidmap;
            }

            if (   $content_id !== $request_id
                && !in_array($request_id, $guidmap)) {
                $guidmap[] = $request_id;
                $to_save[$guid] = $guidmap;
            }
        }

        $this->_meta_cache->saveMultiple($to_save);
    }

    public function check_dl_hit($context, $dl_config)
    {
        if ($this->_no_cache) {
            return false;
        }
        $dl_request_id = 'DL' . $this->generate_request_identifier($context, $dl_config);
        $dl_content_id = $this->_meta_cache->fetch($dl_request_id);
        if ($dl_content_id === false) {
            return false;
        }

        return $this->_data_cache->fetch($dl_content_id);
    }

    public function store_dl_content($context, $dl_config, $dl_cache_data)
    {
        if (   $this->_no_cache
            || $this->_uncached) {
            return;
        }
        $dl_request_id = 'DL' . $this->generate_request_identifier($context, $dl_config);
        $dl_content_id = 'DLC-' . md5($dl_cache_data);

        if (!is_null($this->_expires)) {
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
     * This little helper ensures that the headers Accept-Ranges, Content-Length
     * and Last-Modified are present. The lastmod timestamp is taken out of the
     * component context information if it is populated correctly there; if not, the
     * system time is used instead.
     *
     * To force browsers to revalidate the page on every request (login changes would
     * go unnoticed otherwise), the Cache-Control header max-age=0 is added automatically.
     */
    private function _complete_sent_headers()
    {
        // Detected headers flags
        $ranges = false;
        $size = false;
        $lastmod = false;

        foreach ($this->_sent_headers as $header) {
            if (strncasecmp($header, 'Accept-Ranges', 13) == 0) {
                $ranges = true;
            } elseif (strncasecmp($header, 'Content-Length', 14) == 0) {
                $size = true;
            } elseif (strncasecmp($header, 'Last-Modified', 13) == 0) {
                $lastmod = true;
                // Populate last modified timestamp (force GMT):
                $tmp = substr($header, 15);
                if (strpos($tmp, 'GMT') === false) {
                    $tmp .= ' GMT';
                }
                $this->_last_modified = strtotime($tmp);
                if ($this->_last_modified == -1) {
                    debug_add("Failed to extract the timecode from the last modified header '{$header}', defaulting to the current time.", MIDCOM_LOG_WARN);
                    $this->_last_modified = time();
                }
            }
        }

        if (!$ranges) {
            midcom::get()->header("Accept-Ranges: none");
        }
        if (!$size) {
            /* TODO: Doublecheck the way this is handled, it seems it's one byte too short
               which causes issues with Squid for example (could be that we output extra
               whitespace somewhere or something), now we just don't send it if headers_strategy
               implies caching */
            switch ($this->_headers_strategy) {
                case 'public':
                case 'private':
                    break;
                default:
                    midcom::get()->header("Content-Length: " . ob_get_length());
                    break;
            }
        }
        if (!$lastmod) {
            /* Determine Last-Modified using MidCOM's component context,
             * Fallback to time() if this fails.
             */
            $time = 0;
            foreach (midcom_core_context::get_all() as $context) {
                $meta = midcom::get()->metadata->get_request_metadata($context);
                if ($meta['lastmodified'] > $time) {
                    $time = $meta['lastmodified'];
                }
            }
            if (   $time == 0
                || !is_numeric($time)) {
                $time = time();
            }

            midcom::get()->header("Last-Modified: " . gmdate('D, d M Y H:i:s', $time) . ' GMT');
            $this->_last_modified = $time;
        }

        $this->process_headers($this->cache_control_headers());

        if (   is_array($this->_force_headers)
            && !empty($this->_force_headers)) {
            foreach ($this->_force_headers as $header => $value) {
                $header_string = "{$header}: {$value}";
                _midcom_header($header_string, true);
                $this->_replace_sent_header($header, $header_string);
            }
        }
    }

    private function process_headers(array $headers, $skip_registration = false)
    {
        foreach ($headers as $header => $value) {
            if (is_array($value)) {
                $header_string = $header . ': ' . implode(', ', $value);
            } else {
                $header_string = "{$header}: {$value}";
            }
            if ($skip_registration) {
                _midcom_header($header_string);
            } else {
                midcom::get()->header($header_string);
            }
        }
    }

    /**
     * Scans the _sent_headers array for similar header and replaces with new value,
     * if header is not found adds it to the array
     *
     * @param string $header name of the header, for example "Cache-Control"
     * @param string $header_string full header string with value, for example "Cache-Control: no-cache"
     */
    private function _replace_sent_header($header, $header_string)
    {
        $matched = false;
        foreach ($this->_sent_headers as &$value) {
            if (!preg_match("%^{$header}:%", $value)) {
                continue;
            }
            $value = $header_string;
            $matched = true;
            break;
        }
        if (!$matched) {
            $this->_sent_headers[] = $header_string;
        }
    }

    /**
     * @return array
     */
    public function cache_control_headers()
    {
        // Just to be sure not to mess the headers sent by no_cache in case it was called
        if ($this->_no_cache) {
            return $this->no_cache(true);
        }
        // Add Expiration and Cache Control headers
        $headers = [];
        $expires = false;
        $strategy = $this->_headers_strategy;
        $default_lifetime = $this->_default_lifetime;
        if (   midcom::get()->auth->is_valid_user()
            || midcom_connection::get_user()) {
            $strategy = $this->_headers_strategy_authenticated;
            $default_lifetime = $this->_default_lifetime_authenticated;
        }
        switch ($strategy) {
            // included in case _headers_strategy_authenticated sets this
            case 'no-cache':
                return $this->no_cache(true);
            case 'revalidate':
                // Currently, we *force* a cache client to revalidate the copy every time.
                // I hope that this fixes most of the problems outlined in #297 for the time being.
                // The timeout of a content cache entry is not affected by this.
                $headers['Cache-Control'] = ['max-age=0', 'must-revalidate'];
                $expires = time();
                break;
            case 'private':
                // Fall-strough intentional
            case 'public':
                $headers['Cache-Control'] = [$strategy];
                $headers['Pragma'] = $strategy;
                if (!is_null($this->_expires)) {
                    $expires = $this->_expires;
                    $max_age = $this->_expires - time();
                } else {
                    $expires = time() + $default_lifetime;
                    $max_age = $default_lifetime;
                }
                $headers['Cache-Control'][] = 'max-age=' . $max_age;
                if ($max_age == 0) {
                    $headers['Cache-Control'][] = 'must-revalidate';
                }
                break;
        }

        $headers['Expires'] = gmdate("D, d M Y H:i:s", $expires) . " GMT";
        return $headers;
    }
}
