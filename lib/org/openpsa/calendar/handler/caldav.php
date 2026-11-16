<?php
/**
 * @package org.openpsa.calendar
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use Sabre\CalDAV;
use Sabre\DAV;
use Sabre\DAVACL;
use Sabre\HTTP;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CalDAV server (based on sabre/dav)
 *
 * @package org.openpsa.calendar
 */
class org_openpsa_calendar_handler_caldav extends midcom_baseclasses_components_handler
{
    public function _handler_dav(Request $request, string $path) : Response
    {
        midcom::get()->cache->content->no_cache();

        $principals = new org_openpsa_calendar_caldav_principals;
        $backend = new org_openpsa_calendar_caldav_backend($principals, $this->_topic->get_label());
        $base_uri = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX) . 'caldav/';
        $debug = (bool) $this->_config->get('caldav_debug');

        $server = new DAV\Server([
            new DAVACL\PrincipalCollection($principals, org_openpsa_calendar_caldav_principals::PREFIX),
            new CalDAV\CalendarRoot($principals, $backend, org_openpsa_calendar_caldav_principals::PREFIX),
        ], new org_openpsa_calendar_caldav_sapi);
        $server->setBaseUri($base_uri);
        $server->httpRequest = $this->convert_request($request, $base_uri, $path);
        $server->debugExceptions = $debug;
        DAV\Server::$exposeVersion = false;
        $server->on('exception', function (Throwable $e) {
            if (!$e instanceof DAV\Exception) {
                debug_add('CalDAV request failed: ' . get_class($e) . ': ' . $e->getMessage(), MIDCOM_LOG_ERROR);
            }
        });

        $server->addPlugin(new DAV\Auth\Plugin(new org_openpsa_calendar_caldav_auth));
        $acl = new DAVACL\Plugin;
        $acl->hideNodesFromListings = true;
        $server->addPlugin($acl);
        $server->addPlugin(new CalDAV\Plugin);
        $server->addPlugin(new CalDAV\ICSExportPlugin);
        $server->addPlugin(new DAV\Sync\Plugin);
        if ($debug) {
            $server->addPlugin(new DAV\Browser\Plugin);
        } else {
            // Browsers GET collections, which only the browser plugin handles, so we show a hint instead of a 501
            $client_url = $request->getSchemeAndHttpHost() . $base_uri;
            $server->on('method:GET', function (HTTP\RequestInterface $dav_request, HTTP\ResponseInterface $dav_response) use ($client_url) {
                $dav_response->setStatus(Response::HTTP_OK);
                $dav_response->setHeader('Content-Type', 'text/plain; charset=utf-8');
                $dav_response->setBody("This is a CalDAV server. To use it, add this URL in your calendar client:\n\n" . $client_url . "\n");
                return false;
            }, 200);
        }

        org_openpsa_calendar_caldav_sapi::$response = null;
        $server->start();

        return $this->convert_response(org_openpsa_calendar_caldav_sapi::$response ?? $server->httpResponse);
    }

    private function convert_request(Request $request, string $base_uri, string $path) : HTTP\Request
    {
        // The route parameter is decoded, sabre expects the encoded form
        $url = $base_uri . implode('/', array_map(rawurlencode(...), explode('/', $path)));
        if ($query = $request->getQueryString()) {
            $url .= '?' . $query;
        }
        $headers = $request->headers->all();
        unset($headers['php-auth-user'], $headers['php-auth-pw']);

        $dav_request = new HTTP\Request($request->getMethod(), $url, $headers, $request->getContent());
        $dav_request->setAbsoluteUrl($request->getSchemeAndHttpHost() . $url);
        return $dav_request;
    }

    private function convert_response(HTTP\ResponseInterface $dav_response) : Response
    {
        $response = new Response($dav_response->getBodyAsString(), $dav_response->getStatus() ?: Response::HTTP_INTERNAL_SERVER_ERROR);
        foreach ($dav_response->getHeaders() as $name => $values) {
            $response->headers->set($name, $values);
        }
        return $response;
    }
}
