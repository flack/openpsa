<?php
/**
 * @package org.openpsa.httplib
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Q'n'D wrappers to make the httplib somewhat usable without midcom
 *
 */
if (!defined('MIDCOM_ROOT'))
{
    $try = array('/usr/share/pear', '/usr/share/php4', '/usr/share/php');
    foreach ($try as $pear_root)
    {
        $path = "{$pear_root}/midcom/lib";
        if (is_dir($path))
        {
            define('MIDCOM_ROOT', $path);
            break;
        }
    }
}
if (!defined('MIDCOM_ROOT'))
{
    _midcom_stop_request("Could not define MIDCOM_ROOT (none of the automatically tried directories are good)");
}

if (!defined('MIDCOM_LOG_DEBUG'))
{
    define('MIDCOM_LOG_DEBUG', 'DEBUG');
    define('MIDCOM_LOG_INFO', 'INFO');
    define('MIDCOM_LOG_ERROR', 'ERROR');
    define('MIDCOM_LOG_WARN', 'WARNING');
}

if (!class_exists('midcom_baseclasses_components_purecode'))
{
    class midcom_baseclasses_components_purecode
    {
        var $_config = null;
        var $_i18n = null;

        function __construct()
        {
            $this->_config = new midcom_helper_configuration();
            $this->_i18n = new midcom_helper_i18n();
        }
    }
}

if (!class_exists('midcom_helper_i18n'))
{
    class midcom_helper_i18n
    {
        var $charset = 'UTF-8';

        function get_current_charset()
        {
            return $this->charset;
        }
    }
}

if (!class_exists('midcom_helper_configuration'))
{
    class midcom_helper_configuration
    {
        var $options = array();

        function __construct()
        {
            $data = file_get_contents(MIDCOM_ROOT . '/org/openpsa/httplib/config/config.inc');
            $code = "\$tmparray = array(\n$data\n);";
            eval($code);
            $this->options = $tmparray;
        }

        function get($var)
        {
            if (isset($this->options[$var]))
            {
                return $this->options[$var];
            }
            return false;
        }
    }
}

if (!function_exists('debug_add'))
{
    function debug_add($var, $level='DEBUG')
    {
        $time_hr = date('Y-m-d H:i:s');
        switch ($level)
        {
            case 'INFO':
            case 'DEBUG':
                break;
            default:
                error_log("{$time_hr} {$level}: {$var}");
                break;
        }
        return;
    }
}

if (!function_exists('sprint_r'))
{
    function sprint_r($var)
    {
        return;
    }
}

if (!function_exists('mgd_version'))
{
    function mgd_version()
    {
        return 'none (org.openpsa.httplib/nonmidcom.php)';
    }
}


$_SERVER['REMOTE_ADDR'] = 'cli-script: n/a';
require_once(MIDCOM_ROOT . '/org/openpsa/httplib/helpers.php');
require_once(MIDCOM_ROOT . '/org/openpsa/httplib/main.php');
require_once(MIDCOM_ROOT . '/org/openpsa/httplib/Snoopy.php');
?>