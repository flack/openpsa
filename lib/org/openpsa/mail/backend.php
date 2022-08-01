<?php
/**
 * @package org.openpsa.mail
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport\TransportInterface;

/**
 * Backend for mailer operations
 *
 * @package org.openpsa.mail
 */
abstract class org_openpsa_mail_backend
{
    public $error;

    /**
     * @var Mailer
     */
    protected $mailer;

    abstract public function __construct(array $params);

    /**
     * Send the actual email
     */
    abstract public function mail(org_openpsa_mail_message $message);

    /**
     * Prepare the mail backend
     */
    public static function get(string $implementation, array $params) : self
    {
        if (defined('OPENPSA2_UNITTEST_RUN')) {
            return self::_load_backend('unittest', $params);
        }
        if ($implementation == 'try_default') {
            $try_backends = midcom_baseclasses_components_configuration::get('org.openpsa.mail', 'config')->get('default_try_backends');
            //Use first available backend in list
            foreach ($try_backends as $backend) {
                try {
                    $object = self::_load_backend($backend, $params);
                    debug_add('Using backend ' . $backend);
                    return $object;
                } catch (midcom_error $e) {
                    debug_add('Failed to load backend ' . $backend . ', message:' . $e->getMessage());
                }
            }
            throw new midcom_error('All configured backends failed to load');
        }
        return self::_load_backend($implementation, $params);
    }

    protected function prepare_mailer(TransportInterface $transport)
    {
        $this->mailer = new Mailer($transport);
    }

    private static function _load_backend(string $backend, array $params) : self
    {
        $default_params = midcom_baseclasses_components_configuration::get('org.openpsa.mail', 'config')->get($backend . '_params');
        if (is_array($default_params)) {
            $params = array_merge($default_params, $params);
        }
        $classname = 'org_openpsa_mail_backend_' . $backend;
        return new $classname($params);
    }

    final public function send(org_openpsa_mail_message $message)
    {
        try {
            $ret = $this->mail($message);
            $this->error = null;
            return $ret;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    public function get_error_message() : string
    {
        if ($this->error === null) {
            return '';
        }

        return $this->error ?: 'Unknown error';
    }
}
