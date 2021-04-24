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
use Symfony\Component\HttpFoundation\Response;

/**
 * MidCOM URL methods.
 *
 * See individual methods for documentation.
 *
 * @package midcom
 */
class midcom_core_urlmethods
{
    public function process_config() : Response
    {
        return new StreamedResponse(function() {
            midcom::get()->style->show_midcom('config-test');
        });
    }

    /**
     * This method will serve the attachment denoted by the given ID/GUID.
     * It should enable caching of browsers for Navigation images and so on.
     */
    public function process_serveattachmentguid(Request $request, string $guid) : Response
    {
        $attachment = new midcom_db_attachment($guid);
        if (!$attachment->can_do('midgard:autoserve_attachment')) {
            throw new midcom_error_notfound('Failed to access attachment: Autoserving denied.');
        }

        // Doublecheck that this is registered
        midcom::get()->cache->content->register($attachment->guid);

        $response = new BinaryFileResponse($attachment->get_path());
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
        midcom::get()->cache->content->write_meta_cache('A-' . $etag, $request, $response);
        return $response;
    }

    /**
     * This will resolve the given GUID into the MidCOM NAP tree, relocating to the
     * URL corresponding to the node/leaf. The Permalink can be created by using the
     * key MIDCOM_NAV_PERMALINK of any NAP data array. Upon resolving it, MidCOM will
     * relocate to the automatically computed MIDCOM_NAV_FULLURL.
     *
     * @throws midcom_error_notfound
     */
    public function process_permalink(string $guid) : Response
    {
        $destination = midcom::get()->permalinks->resolve_permalink($guid);
        if ($destination === null) {
            throw new midcom_error_notfound("This Permalink is unknown.");
        }

        // We use "302 Found" here so that search engines and others will keep using the PermaLink instead of the temporary
        return new midcom_response_relocate($destination, 302);
    }

    /**
     * will clear the cache of the current site
     */
    public function invalidate_cache(Request $request)
    {
        if (!in_array($request->getClientIp(), midcom::get()->config->get_array('indexer_reindex_allowed_ips'))) {
            midcom::get()->auth->require_valid_user('basic');
            midcom::get()->auth->require_admin_user();
        }
        midcom::get()->cache->content->enable_live_mode();
        midcom::get()->cache->invalidate_all();
        midcom::get()->uimessages->add(
            midcom::get()->i18n->get_string('MidCOM', 'midcom'),
            midcom::get()->i18n->get_string('cache invalidation successful', 'midcom'),
            'info'
        );

        $url = $request->server->get('HTTP_REFERER') ?: midcom_connection::get_url('self');
        return new midcom_response_relocate($url);
    }

    public function process_logout(Request $request, string $url) : Response
    {
        midcom::get()->cache->content->no_cache();
        midcom::get()->auth->logout();
        return $this->redirect($request, $url);
    }

    public function process_login(Request $request, string $url) : Response
    {
        if (midcom::get()->auth->is_valid_user()) {
            return $this->redirect($request, $url);
        }
        return new midcom_response_login;
    }

    private function redirect(Request $request, string $redirect_to) : Response
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
     * @see midcom_services_cache_module_content::enable_live_mode()
     */
    public function process_exec(Request $request, string $component, string $filename, string $argv) : Response
    {
        $componentloader = midcom::get()->componentloader;
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
