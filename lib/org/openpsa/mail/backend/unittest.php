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
    public static $mails = [];
    private $_index;

    public function __construct(array $params)
    {
        $this->_index = count(self::$mails);
        self::$mails[$this->_index] = ['params' => $params];
    }

    public function mail(org_openpsa_mail_message $message)
    {
        self::$mails[$this->_index]['recipients'] = $message->get_recipients();
        self::$mails[$this->_index]['headers'] = $message->get_headers();
        self::$mails[$this->_index]['body'] = $message->get_body();
    }

    public static function flush()
    {
        $mails = self::$mails;
        self::$mails = [];
        return $mails;
    }
}
