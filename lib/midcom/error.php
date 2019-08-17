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
    public function __construct($message, $code = MIDCOM_ERRCRIT)
    {
        parent::__construct($message, $code);
    }

    public function log($loglevel = MIDCOM_LOG_ERROR)
    {
        debug_add($this->getMessage(), $loglevel);
    }
}