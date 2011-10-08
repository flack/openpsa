<?php
/**
 * @package org.openpsa.mail
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Send backend for org_openpsa_mail, used in unit tests. All it does is collect
 * the passed parameters. Think of it as a poor man's mock object
 *
 * @package org.openpsa.mail
 */
class org_openpsa_mail_backend_unittest extends org_openpsa_mail_backend
{
    public static $instance;
    public static $params;
    public static $recipients;
    public static $headers;
    public static $body;

    public function __construct(array $params)
    {
        self::$params = $params;
    }

    public function mail($recipients, array $headers, $body)
    {
        self::$recipients = $recipients;
        self::$headers = $headers;
        self::$body = $body;
        return true;
    }
}
?>