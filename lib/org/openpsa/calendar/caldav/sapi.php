<?php
/**
 * @package org.openpsa.calendar
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use Sabre\HTTP\Request;
use Sabre\HTTP\ResponseInterface;
use Sabre\HTTP\Sapi;

/**
 * Sapi that captures sabre's response instead of sending it,
 * so that MidCOM's kernel stays in charge of output
 *
 * (Sapi's methods are static, so the response is stored in a static property)
 *
 * @package org.openpsa.calendar
 */
class org_openpsa_calendar_caldav_sapi extends Sapi
{
    public static ?ResponseInterface $response = null;

    /**
     * Placeholder, the real request is set on the server by the handler
     */
    public static function getRequest(): Request
    {
        return new Request('GET', '/');
    }

    public static function sendResponse(ResponseInterface $response)
    {
        static::$response = $response;
    }
}
