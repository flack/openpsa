<?php
/**
 * @package midcom
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM unauthorized exception
 *
 * @package midcom
 */
class midcom_error_forbidden extends midcom_error
{
    private $method;

    public function __construct(string $message = null, int $code = MIDCOM_ERRFORBIDDEN, string $method = 'form')
    {
        if ($message === null) {
            $message = midcom::get()->i18n->get_string('access denied', 'midcom');
        }
        $this->method = $method;
        parent::__construct($message, $code);
    }

    public function get_method() : string
    {
        return $this->method;
    }

    public function log(int $loglevel = MIDCOM_LOG_DEBUG)
    {
        parent::log($loglevel);
    }
}