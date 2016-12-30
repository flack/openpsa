<?php
/**
 * @package midcom
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Matches the given context to a handler or to one of the central URL methods.
 * URL methods are handled directly from here, handlers are passed back to
 * midcom_application
 *
 * <b>URL METHODS</b>
 *
 * The following URL parameters are recognized and are
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
 * Example: http://$host/midcom-exec-midcom/update_storage.php
 *
 * <b>string cache</b>
 *
 * May take one of the following values: "invalidate" will clear the cache of the
 * current site, "nocache" will bypass the cache for the current request by
 * calling midcom::get()->cache->content->no_cache();
 *
 * @package midcom
 */
class midcom_core_urlmethods
{
    /**
     * The context we're working on
     *
     * @var midcom_core_context
     */
    private $_context;

    public function __construct(midcom_core_context $context)
    {
        $this->_context = $context;
    }

    public function process()
    {
        while (($tmp = $this->_context->parser->get_variable('midcom')) !== false) {
            foreach ($tmp as $key => $value) {
                if ($key == 'substyle') {
                    $this->_context->set_key(MIDCOM_CONTEXT_SUBSTYLE, $value);
                    debug_add("Substyle '$value' selected");
                } else {
                    $method_name = '_process_' . $key;
                    if (!method_exists($this, $method_name)) {
                        debug_add("Unknown URL method: {$key} => {$value}", MIDCOM_LOG_WARN);
                        throw new midcom_error_notfound("This URL method is unknown.");
                    }
                    $this->$method_name($value);
                }
            }
        }
    }

    private function _process_serveattachmentguid($value)
    {
        if ($this->_context->parser->argc > 1) {
            debug_add('Too many arguments remaining for serve_attachment.', MIDCOM_LOG_ERROR);
        }

        $attachment = new midcom_db_attachment($value);
        if (!$attachment->can_do('midgard:autoserve_attachment')) {
            throw new midcom_error_notfound('Failed to access attachment: Autoserving denied.');
        }

        midcom::get()->serve_attachment($attachment);
    }

    private function _process_permalink($value)
    {
        $destination = midcom::get()->permalinks->resolve_permalink($value);
        if ($destination === null) {
            throw new midcom_error_notfound("This Permalink is unknown.");
        }

        // We use "302 Found" here so that search engines and others will keep using the PermaLink instead of the temporary
        $response = new midcom_response_relocate($destination, 302);
        $response->send();
    }

    private function _process_cache($value)
    {
        if ($value == 'invalidate') {
            if (   !is_array(midcom::get()->config->get('indexer_reindex_allowed_ips'))
                || !in_array($_SERVER['REMOTE_ADDR'], midcom::get()->config->get('indexer_reindex_allowed_ips'))) {
                midcom::get()->auth->require_valid_user('basic');
                midcom::get()->auth->require_admin_user();
            }
            midcom::get()->cache->content->enable_live_mode();
            midcom::get()->cache->invalidate_all();
            midcom::get()->uimessages->add(midcom::get()->i18n->get_string('MidCOM', 'midcom'), midcom::get()->i18n->get_string("cache invalidation successful", 'midcom'), 'info');

            $url = (isset($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : midcom_connection::get('self');
            $response = new midcom_response_relocate($url);
            $response->send();
        } elseif ($value == 'nocache') {
            midcom::get()->cache->content->no_cache();
        } else {
            throw new midcom_error_notfound("Invalid cache request URL.");
        }
    }

    private function _process_logout($value)
    {
        // rest of URL used as redirect
        $redirect_to = $this->_get_remaining_url($value);

        midcom::get()->cache->content->no_cache();
        midcom::get()->auth->logout();
        $response = new midcom_response_relocate($redirect_to);
        $response->send();
    }

    private function _process_login($value)
    {
        // rest of URL used as redirect
        $redirect_to = $this->_get_remaining_url($value);

        if (midcom::get()->auth->is_valid_user()) {
            $response = new midcom_response_relocate($redirect_to);
            $response->send();
            // This will exit
        }
        midcom::get()->auth->show_login_page();
        // This will exit too
    }

    private function _get_remaining_url($value)
    {
        $redirect_to = '';
        if (   !empty($value)
            || !empty($this->_context->parser->argv)) {
            $redirect_to = "{$value}/" . implode($this->_context->parser->argv, '/');
            $redirect_to = preg_replace('%^(.*?):/([^/])%', '\\1://\\2', $redirect_to);
        }

        if (!empty($_SERVER['QUERY_STRING'])) {
            $redirect_to .= "?{$_SERVER['QUERY_STRING']}";
        }
        return $redirect_to;
    }

    /**
     * Execute any given Script in the current MidCOM context. All files have to be
     * in $component_dir/exec directly, otherwise the script will not execute.
     *
     * The script's name is taken from the current argv[0].
     *
     * The script file is executed in the cache's live mode to allow for long running
     * scripts (just produce any output regularly, or Apache will kill you after ~ 2 mins.).
     *
     * The remaining arguments will be placed into the globals $argc/argv.
     *
     * @param string $component The component to look in ("midcom" uses core scripts)
     * @see midcom_services_cache_module_content::enable_live_mode()
     */
    private function _process_exec($component)
    {
        // Sanity checks
        if ($this->_context->parser->argc < 1) {
            throw new midcom_error_notfound("Script exec path invalid, need exactly one argument.");
        }

        // Build the path
        if ($component == 'midcom') {
            $path = MIDCOM_ROOT . '/midcom/exec/';
        } else {
            $componentloader = midcom::get()->componentloader;
            if (!$componentloader->is_installed($component)) {
                throw new midcom_error_notfound('The requested component is not installed');
            }
            $componentloader->load($component);
            $this->_context->set_key(MIDCOM_CONTEXT_COMPONENT, $component);
            $path = $componentloader->path_to_snippetpath($component) . '/exec/';
        }
        $path .= $this->_context->parser->argv[0];

        if (!is_file($path)) {
            throw new midcom_error_notfound("File not found.");
        }

        // collect remaining arguments and put them to global vars.
        $GLOBALS['argc'] = $this->_context->parser->argc--;
        $GLOBALS['argv'] = $this->_context->parser->argv;
        array_shift($GLOBALS['argv']);

        midcom::get()->cache->content->enable_live_mode();

        midcom::get()->set_status(MIDCOM_STATUS_CONTENT);

        // We seem to be in a valid place. Exec the file with the current permissions.
        require($path);

        // Exit
        midcom::get()->finish();
    }
}
