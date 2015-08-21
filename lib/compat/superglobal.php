<?php
/**
 * @package midcom.compat
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Compatibility wrapper that provides the API found in Ragnaroek's $_MIDCOM superglobal
 *
 * @package midcom.compat
 * @property midcom_helper_serviceloader $serviceloader
 * @property midcom_services_i18n $i18n
 * @property midcom_helper__componentloader $componentloader
 * @property midcom_services_dbclassloader $dbclassloader
 * @property midcom_helper__dbfactory $dbfactory
 * @property midcom_helper_head $head
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
class midcom_compat_superglobal
{
    /**
     * Redirect calls to the midcom_application singleton
     */
    public function __call($method, $arguments)
    {
        switch (count($arguments))
        {
            case 0:
                return midcom::get()->{$method}();
            case 1:
                return midcom::get()->{$method}($arguments[0]);
            case 2:
                return midcom::get()->{$method}($arguments[0], $arguments[1]);
            case 3:
                return midcom::get()->{$method}($arguments[0], $arguments[1], $arguments[3]);
            default:
                //There is no function in midcom_application with more than 3 parameters, but you never know...
                return call_user_func_array(array(midcom::get(), $method), $arguments);
        }
    }

    /**
     * Magic getter for service loading
     */
    public function __get($key)
    {
        return $this->get_service($key);
    }

    /**
     * Magic setter
     */
    public function __set($key, $value)
    {
        return midcom::get()->$key = $value;
    }

    function generate_host_url($host)
    {
        $protocol = ($host->port == 443) ? 'https' : 'http';

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
     * Deliver a snippet to the client.
     *
     * This function can serve the code field of an arbitrary snippet. There is no checking on
     * permissions done here, the callee has to ensure this.
     *
     * Two parameters can be used to influence the behavior of this method:
     * "midcom/content-type" will set the content-type header sent with the code
     * field's content. If this is not set, application/octet-stream is used as a
     * default. "midcom/expire" is a count of seconds used for content expiration,
     * both for the HTTP headers and for the caching engine. If this is no valid
     * integer or less than or equal to zero or not set, the value is set to "1".
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

        $midcom = midcom::get();

        $midcom->header("Last-Modified: " . gmdate("D, d M Y H:i:s", $lastmod) . ' GMT');
        $midcom->header("Content-Length: " . strlen($snippet->code));
        $midcom->header("Accept-Ranges: none");
        $midcom->header("Content-Type: $content_type");
        midcom::get()->cache->content->content_type($content_type);

        // TODO: This should be made aware of the cache headers strategy for content cache module
        if ($expire > 0)
        {
            $midcom->header("Cache-Control: public max-age=$expire");
            $midcom->header("Expires: " . gmdate("D, d M Y H:i:s", (time()+$expire)) . " GMT" );
            midcom::get()->cache->content->expires(time()+$expire);
        }
        else if ($expire == 0)
        {
            midcom::get()->cache->content->no_cache();
        }
        echo $snippet->code;
    }

    /**
     * Deliver a blob to the client.
     *
     * This will add the following HTTP Headers:
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
     * @param MidgardAttachment &$attachment    A reference to the attachment to be delivered.
     * @param int $expires HTTP-Expires timeout in seconds, set this to 0 for uncacheable pages, or to -1 for no Expire header.
     */
    function serve_attachment(& $attachment, $expires = -1)
    {
        $resolver = new midcom_core_resolver(midcom_core_context::get());
        $resolver->serve_attachment($attachment, $expires);
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
        return midcom::get()->$name;
    }

    /**
     * Return the reference to the component loader.
     *
     * @return midcom_helper__componentloader The reference of the component loader in use.
     */
    public function get_component_loader()
    {
        return midcom::get()->componentloader;
    }

    /**
     * Load a code library
     *
     * This will load the pure-code library denoted by the MidCOM Path $path. It will
     * return true if the component truly was a pure-code library, false otherwise.
     * If the component loader cannot load the component, midcom_error will be
     * thrown by it.
     *
     * Common example:
     *
     * <code>
     * midcom::get()->componentloader->load_library('midcom.helper.datamanager');
     * </code>
     *
     * @param string $path    The name of the code library to load.
     * @return boolean            Indicates whether the library was successfully loaded.
     */
    function load_library($path)
    {
        return midcom::get()->componentloader->load_library($path);
    }

    /**
     * Access the MidCOM component context
     *
     * @param int param1    Either the ID of the context (two parameters) or the key requested (one parameter).
     * @param int param2    Either the key requested (two parameters) or null (one parameter, the default).
     * @return mixed    The content of the key being requested.
     */
    public function get_context_data($param1, $param2 = null)
    {
        if (is_null($param2))
        {
            $context = midcom_core_context::get();
            $key = $param1;
        }
        else
        {
            $context = midcom_core_context::get($param1);
            $key = $param2;
        }

        if (!$context)
        {
            return false;
        }

        return $context->get_key($key);
    }

    /**
     * Update the component context
     *
     * @param mixed $value    The value to be stored
     * @param int $param1    See get_context_data()
     * @param int $param2    See get_context_data()
     * @see get_context_data()
     */
    function _set_context_data($value, $param1, $param2 = null)
    {
        if (is_null($param2))
        {
            $context = midcom_core_context::get();
            $key = $param1;
        }
        else
        {
            $context = midcom_core_context::get($param1);
            $key = $param2;
        }

        if (!$context)
        {
            return false;
        }

        $context->set_key($key, $value);
    }

    /**
     * Store arbitrary, component-specific information in the component context
     *
     * @param mixed $key    The key associated to the value or the context id
     * @param mixed &$value    The value to store. (This is stored by-reference!)
     * @param mixed $contextid    The key if a context was specified as first parameter
     */
    function set_custom_context_data($key, &$value, $contextid = null)
    {
        $context = midcom_core_context::get($contextid);
        $context->set_custom_key($key, $value);
    }

    /**
     * Retrieve arbitrary, component-specific information in the component context
     *
     * @param int $param1    See get_context_data()
     * @param int $param2    See get_context_data()
     * @return mixed        The requested value, which is returned by Reference!
     */
    function & get_custom_context_data($param1, $param2 = null)
    {
        if (is_null($param2))
        {
            $context = midcom_core_context::get();
            $key = $param1;
        }
        else
        {
            $context = midcom_core_context::get($param1);
            $key = $param2;
        }

        if (!$context)
        {
            $result = false;
            return $result;
        }

        return $context->get_custom_key($key);
    }

    /**
     * Prepends a substyle before the currently selected component style.
     *
     * Prepends a substyle before the currently selected component style, effectively
     * enabling a depth of more than one style during substyle selection. This is only
     * effective if done during the handle phase of the component and allows the
     * component. The currently selected substyle therefore is now searched one level
     * deeper below "subStyle".
     *
     * The system must have completed the CAN_HANDLE Phase before this function will
     * be available.
     *
     * @param string $newsub The substyle to prepend.
     */
    function substyle_prepend($newsub)
    {
        midcom::get()->style->prepend_substyle($newsub);
    }

    /**
     * Appends a substyle after the currently selected component style.
     *
     * Appends a substyle after the currently selected component style, effectively
     * enabling a depth of more than one style during substyle selection. This is only
     * effective if done during the handle phase of the component and allows the
     * component. The currently selected substyle therefore is now searched one level
     * deeper below "subStyle".
     *
     * The system must have completed the CAN_HANDLE Phase before this function will
     * be available.
     *
     * @param string $newsub The substyle to append.
     */
    function substyle_append($newsub)
    {
        midcom::get()->style->append_substyle($newsub);
    }

    /**
     * Returns the ID of the currently active context. This is false if there is no
     * context running.
     *
     * @return int The context ID.
     */
    function get_current_context ()
    {
        return midcom_core_context::get()->id;
    }

    /**
     * Returns the complete context data array
     *
     * @return array The data of all contexts
     */
    function get_all_contexts ()
    {
        return midcom_core_context::get_all();
    }

    /**
     * Sets a new context, doing some minor sanity checking.
     */
    public function _set_current_context($id)
    {
        midcom_core_context::get($id)->set_current();
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
        midcom::get()->metadata->set_request_metadata($lastmodified, $permalinkguid);
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
        return midcom::get()->metadata->get_request_metadata($context_id);
    }

    /**
     * Sets the page title for the current context.
     *
     * This can be retrieved by accessing the component context key
     * MIDCOM_CONTEXT_PAGETITLE.
     *
     * @param string $string    The title to set.
     */
    public function set_pagetitle($string)
    {
        midcom::get()->head->set_pagetitle($string);
    }

    /* *************************************************************************
     * Generic Helper Functions not directly related with MidCOM:
     *
     * generate_error     - Generate HTTP Error
     * serve_snippet      - Serves snippet including all necessary headers
     * serve_attachment   - Serves attachment including all necessary headers
     * add_jsfile         - Add a JavaScript URL to the load queue
     * add_jscript        - Add JavaScript code to the load queue
     * add_jsonload       - Add a JavaScript method call to the bodies onload tag
     * add_object_head    - Add object links to the page's head.
     * add_style_head     - Add style tags to the page's head.
     * add_meta_head      - Add metatags to the page's head.
     * print_head_elements     - Print the queued-up JavaScript code (for inclusion in the HEAD section)
     * print jsonload     - Prints the onload command if required (for inclusion as a BODY attribute)
     * check_memberships  - Checks whether the user is in a given group
     * relocate           - executes a HTTP relocation to the given URL
     */

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
                    $prefix = midcom::get()->get_page_prefix();
                }
                $url =  "{$prefix}{$url}";
                debug_add("This is a relative URL from the local site, prepending anchor prefix: {$url}");
            }
            else
            {
                $url = midcom::get()->get_host_name() . $url;
                debug_add("This is an absolute URL from the local host, prepending host name: {$url}");
            }
        }
        $response = new midcom_response_relocate($url, $response_code);
        $response->send();
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
        $view_toolbar = midcom::get()->toolbars->get_view_toolbar($context->id);
        $view_toolbar->bind_to($object);

        // Bind the object to the metadata service
        midcom::get()->metadata->bind_metadata_to_object(MIDCOM_METADATA_VIEW, $object, $context->id);

        // Push the object's CSS classes to metadata service
        $page_class = midcom::get()->metadata->get_object_classes($object, $page_class);
        midcom::get()->metadata->set_page_class($page_class, $context->id);

        midcom::get()->style->append_substyle($page_class);
    }

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

        $error_shown = true;
        $error_handler = new midcom_exception_handler();
        $error_handler->show($httpcode, $message);
        // This will exit
    }

    /**
     * Register JavaScript File for referring in the page.
     *
     * @param string $url    The URL to the file to-be referenced.
     * @param boolean $prepend Whether to add the JS include to beginning of includes
     */
    public function add_jsfile($url, $prepend = false)
    {
        midcom::get()->head->add_jsfile($url, $prepend);
    }

    /**
     * Register JavaScript Code for output directly in the page.
     *
     * @param string $script    The code to be included directly in the page.
     */
    public function add_jscript($script, $defer = '', $prepend = false)
    {
        midcom::get()->head->add_jscript($script, $defer, $prepend);
    }

    /**
     * Register JavaScript snippets to jQuery states.
     *
     * @param string $script    The code to be included in the state.
     * @param string $state    The state where to include the code to. Defaults to document.ready
     */
    public function add_jquery_state_script($script, $state = 'document.ready')
    {
        midcom::get()->head->add_jquery_state_script($script, $state);
    }

    /**
     * Register some object tags to be added to the head element.
     *
     * @param  string $script    The input between the <object></object> tags.
     * @param  array  $attributes Array of attribute=> value pairs to be placed in the tag.
     */
    public function add_object_head ($script, $attributes = null)
    {
        midcom::get()->head->add_object_head ($script, $attributes);
    }

    /**
     *  Register a metatag  to be added to the head element.
     *
     *  @param  array  $attributes Array of attribute=> value pairs to be placed in the tag.
     */
    public function add_meta_head($attributes = null)
    {
        midcom::get()->head->add_meta_head($attributes);
    }

    /**
     * Register a styleblock / style link  to be added to the head element.
     *
     * @param  string $script    The input between the <style></style> tags.
     * @param  array  $attributes Array of attribute=> value pairs to be placed in the tag.
     */
    public function add_style_head($script, $attributes = null)
    {
        midcom::get()->head->add_style_head($script, $attributes);
    }

    /**
     * Register a linkelement to be placed in the pagehead.
     *
     * @param  array  $attributes Array of attribute=> value pairs to be placed in the tag.
     */
    public function add_link_head( $attributes = null )
    {
        return midcom::get()->head->add_link_head($attributes);
    }

    /**
     * Convenience shortcut for adding CSS files
     *
     * @param string $url The stylesheet URL
     * @param string $media The media type(s) for the stylesheet, if any
     */
    public function add_stylesheet($url, $media = false)
    {
        midcom::get()->head->add_stylesheet($url, $media);
    }

    /**
     * Register a JavaScript method for the body onload event
     *
     * @param string $method    The name of the method to be called on page startup, including parameters but excluding the ';'.
     */
    public function add_jsonload($method)
    {
        midcom::get()->head->add_jsonload($method);
    }

    /**
     * Echo the registered javascript code.
     */
    public function print_jsonload()
    {
        midcom::get()->head->print_jsonload();
    }

    /**
     * Echo the _head elements added.
     */
    public function print_head_elements()
    {
        midcom::get()->head->print_head_elements();
    }

    /**
     * Init jQuery
     */
    public function enable_jquery($version = null)
    {
        midcom::get()->head->enable_jquery($version);
    }

    /**
     * Echo the jquery statuses
     */
    public function print_jquery_statuses()
    {
        midcom::get()->head->print_jquery_statuses();
    }
}
