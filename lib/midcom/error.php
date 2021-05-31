<?php
/**
 * @package midcom
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Basic MidCOM exception
 *
 * @package midcom
 */
class midcom_error extends Exception
{
    public function __construct(string $message, int $code = MIDCOM_ERRCRIT)
    {
        parent::__construct($message, $code);
    }

    public function log(int $loglevel = MIDCOM_LOG_ERROR)
    {
        midcom::get()->debug->log($this->getMessage(), $loglevel);
    }
}