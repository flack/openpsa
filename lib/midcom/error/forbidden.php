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
    public function __construct($message = null, $code = MIDCOM_ERRFORBIDDEN)
    {
        if ($message === null) {
            $message = midcom::get()->i18n->get_string('access denied', 'midcom');
        }
        parent::__construct($message, $code);
    }

    public function log($loglevel = MIDCOM_LOG_DEBUG)
    {
        parent::log($loglevel);
    }
}