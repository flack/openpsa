<?php
/**
 * @package midcom
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use midgard\portable\api\blob;

/**
 * MidCOM URL methods.
 *
 * See individual methods for documentation.
 *
 * <b>midcom-substyle-{stylename}</b>
 *
 * Different from the methods in this class, "substyle" does not produce a response.
 * Instead, it will set a substyle to the current component, which is appended to the
 * style selected by the component at the moment the component style is loaded.
 * The methods substyle_(append|prepend)'s work on the basis of this value then.
 *
 * Note, that this first assignment is done between can_handle and handle, so
 * it will serve as a basis for all component-side style switching operations.
 *
 * The substyle URL switch is most useful in conjunction with
 * midcom_application::dynamic_load().
 *
 * @package midcom
 */
class midcom_core_urlmethods
{
    public function process_config()
    {
        return new StreamedResponse(function() {
            midcom::get()->style->show_midcom('config-test');
        });
    }

    /**
     * This method will serve the attachment denoted by the given ID/GUID.
     * It should enable caching of browsers for Navigation images and so on.
     *
     * @param string $guid
     * @throws midcom_error_notfound
     */
    public function process_serveattachmentguid(Request $request, $guid)
    {
        $attachment = new midcom_db_attachment($guid);
        if (!$attachment->can_do('midgard:autoserve_attachment')) {
            throw new midcom_error_notfound('Failed to access attachment: Autoserving denied.');
        }

        // Doublecheck that this is registered
        midcom::get()->cache->content->register($attachment->guid);

        $blob = new blob($attachment->__object);
        $response = new BinaryFileResponse($blob->get_path());
        $last_modified = (int) $response->getLastModified()->format('U');
        $etag = md5("{$last_modified}{$attachment->name}{$attachment->mimetype}{$attachment->guid}");
        $response->setEtag($etag);

        if (!$response->isNotModified($request)) {
            $response->prepare($request);

            if (midcom::get()->config->get('attachment_xsendfile_enable')) {
                BinaryFileResponse::trustXSendfileTypeHeader();
                $response->headers->set('X-Sendfile-Type', 'X-Sendfile');
            }
        }
        midcom::get()->cache->content->cache_control_headers($response);
        // Store metadata in cache so _check_hit() can help us
        midcom::get()->cache->content->write_meta_cache('A-' . $etag, $etag, $request);
        return $response;
    }

    /**
     * This will resolve the given GUID into the MidCOM NAP tree, relocating to the
     * URL corresponding to the node/leaf. The Permalink can be created by using the
     * key MIDCOM_NAV_PERMALINK of any NAP data array. Upon resolving it, MidCOM will
     * relocate to the automatically computed MIDCOM_NAV_FULLURL.
     *
     * @param string $guid
     * @throws midcom_error_notfound
     * @return midcom_response_relocate
     */
    public function process_permalink($guid)
    {
        $destination = midcom::get()->permalinks->resolve_permalink($guid);
        if ($destination === null) {
            throw new midcom_error_notfound("This Permalink is unknown.");
        }

        // We use "302 Found" here so that search engines and others will keep using the PermaLink instead of the temporary
        return new midcom_response_relocate($destination, 302);
    }

    /**
     * May take one of the following values:
     *
     * - "invalidate" will clear the cache of the current site
     * - "nocache" will bypass the cache for the current request by
     *   calling midcom::get()->cache->content->no_cache();
     *
     * @param string $action
     * @return midcom_response_relocate
     */
    public function process_cache(Request $request, $action)
    {
        if ($action == 'invalidate') {
            if (!in_array($request->getClientIp(), midcom::get()->config->get('indexer_reindex_allowed_ips', []))) {
                midcom::get()->auth->require_valid_user('basic');
                midcom::get()->auth->require_admin_user();
            }
            midcom::get()->cache->content->enable_live_mode();
            midcom::get()->cache->invalidate_all();
            $l10n = midcom::get()->i18n->get_l10n('midcom');
            midcom::get()->uimessages->add($l10n->get('MidCOM'), $l10n->get('cache invalidation successful'), 'info');

            $url = $request->server->get('HTTP_REFERER') ?: midcom_connection::get_url('self');
            return new midcom_response_relocate($url);
        }
        midcom::get()->cache->content->no_cache();
    }

    public function process_logout(Request $request, $url)
    {
        midcom::get()->cache->content->no_cache();
        midcom::get()->auth->logout();
        return $this->redirect($request, $url);
    }

    public function process_login(Request $request, $url)
    {
        if (midcom::get()->auth->is_valid_user()) {
            return $this->redirect($request, $url);
        }
        return new midcom_response_login;
    }

    private function redirect(Request $request, $redirect_to)
    {
        if (!empty($request->server->get('QUERY_STRING'))) {
            $redirect_to .= '?' . $request->getQueryString();
        }
        return new midcom_response_relocate($redirect_to);
    }

    /**
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
     * Example: http://$host/midcom-exec-midcom/reindex.php
     *
     * The script file is executed in the cache's live mode to allow for long running
     * scripts (just produce any output regularly, or Apache will kill you after ~ 2 mins.).
     *
     * The remaining arguments will be placed into the global $argv.
     *
     * @param Request $request
     * @param string $component The component to look in ("midcom" uses core scripts)
     * @param string $filename
     * @see midcom_services_cache_module_content::enable_live_mode()
     */
    public function process_exec(Request $request, $component, $filename, $argv)
    {
        $componentloader = midcom::get()->componentloader;
        if (!$componentloader->is_installed($component)) {
            throw new midcom_error_notfound('The requested component is not installed');
        }
        $path = $componentloader->path_to_snippetpath($component) . '/exec/' . $filename;
        if (!is_file($path)) {
            throw new midcom_error_notfound("File not found.");
        }

        // We seem to be in a valid place
        $context = $request->attributes->get('context');
        if ($component !== 'midcom') {
            $context->set_key(MIDCOM_CONTEXT_COMPONENT, $component);
        }
        // Collect remaining arguments and put them to global vars.
        $GLOBALS['argv'] = explode('/', $argv);

        return new StreamedResponse(function() use ($path) {
            midcom::get()->cache->content->enable_live_mode();

            // Exec the file with the current permissions.
            require $path;
        });
    }
}
