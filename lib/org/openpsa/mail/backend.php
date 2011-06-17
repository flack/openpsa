<?php
/**
 * @package org.openpsa.mail
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Backend for mailer operations
 *
 * @package org.openpsa.mail
 */
abstract class org_openpsa_mail_backend
{
    public $error = false;

    abstract function __construct(array $params);

    /**
     * This function sends the actual email
     *
     * @param string $recipients Recipients
     * @param array $headers Mail headers
     * @param string $body Mail body
     */
    abstract function mail($recipients, array $headers, $body);

    /**
     * Factory method that prepares the mail backend
     */
    public static function get($implementation, array $params)
    {
        if ($implementation = 'try_default')
        {
            $try_backends = midcom_baseclasses_components_configuration::get('org.openpsa.mail', 'config')->get('default_try_backends');
            //Use first available backend in list
            foreach ($try_backends as $backend)
            {
                try
                {
                    $object = self::_load_backend($backend, $params);
                    debug_add('Using backend ' . $backend);
                    return $object;
                }
                catch (midcom_error $e)
                {
                    debug_add('Failed to load backend ' . $backend . ', message:' . $e->getMessage());
                }
            }
            throw new midcom_error('All configured backends failed to load');
        }
        else
        {
            return self::_load_backend($backend, $params);
        }
    }

    private static function _load_backend($backend, array $params)
    {
        $default_params = midcom_baseclasses_components_configuration::get('org.openpsa.mail', 'config')->get($backend . '_params');
        if (is_array($default_params))
        {
            $params = array_merge($default_params, $params);
        }
        $classname = 'org_openpsa_mail_backend_' . $backend;
        return new $classname($params);
    }

    final public function send($to, $headers, $body)
    {
        $recipients = $this->_merge_address_headers($to, $headers);
        $ret = $this->mail($recipients, $headers, $body);
        if (!$ret)
        {
            $this->error = $ret;
        }
        else
        {
            $this->error = false;
        }
        return $ret;
    }

    /**
     * Merges all email recipients into a comma-separated string
     *
     * @todo Support arrays of addresses as well
     */
    private function _merge_address_headers($to, array $headers)
    {
        $addresses = $to;
        if (!empty($headers['Cc']))
        {
            $addresses .= ', ' . $headers['Cc'];
        }
        if (!empty($this->headers['Bcc']))
        {
            $addresses .= ', ' . $headers['Bcc'];
        }
        return $addresses;
    }

    public function get_error_message()
    {
        if ($this->error === false)
        {
            return false;
        }

        if (is_object($this->error))
        {
            return $this->error->getMessage();
        }
        if (   is_string($this->error)
            && !empty($this->error))
        {
            return $this->error;
        }
        return 'Unknown error';
    }
}
?>