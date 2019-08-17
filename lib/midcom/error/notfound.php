<?php
/**
 * @package midcom
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM not found exception
 *
 * @package midcom
 */
class midcom_error_notfound extends midcom_error
{
    public function __construct($message, $code = MIDCOM_ERRNOTFOUND)
    {
        parent::__construct($message, $code);
    }

    public function log($loglevel = MIDCOM_LOG_INFO)
    {
        parent::log($loglevel);
    }
}