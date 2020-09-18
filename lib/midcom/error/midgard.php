<?php
/**
 * @package midcom
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */


use midgard\portable\api\error\exception as mgd_exception;

/**
 * MidCOM wrapped Midgard exception
 *
 * @package midcom
 */
class midcom_error_midgard extends midcom_error
{
    public function __construct(mgd_exception $e, $id)
    {
        if ($id !== null) {
            if ($e->getCode() === MGD_ERR_NOT_EXISTS) {
                $code = MIDCOM_ERRNOTFOUND;
                $message = "The object with identifier {$id} was not found.";
            } elseif ($e->getCode() == MGD_ERR_ACCESS_DENIED) {
                $code = MIDCOM_ERRFORBIDDEN;
                $message = midcom::get()->i18n->get_string('access denied', 'midcom');
            } elseif ($e->getCode() == MGD_ERR_OBJECT_DELETED) {
                $code = MIDCOM_ERRNOTFOUND;
                $message = "The object with identifier {$id} was deleted.";
            }
        }
        //If other options fail, go for the server error
        if (!isset($code)) {
            $code = MIDCOM_ERRCRIT;
            $message = $e->getMessage();
        }
        parent::__construct($message, $code);
    }

    public function log(int $loglevel = MIDCOM_LOG_WARN)
    {
        parent::log($loglevel);
    }
}