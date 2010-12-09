<?php
/**
 * @package midcom
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Collection of simple helper methods for testing site configuration
 *
 * @package midcom
 */
class midcom_config_test
{

    public function __construct()
    {
        define('OK', 0);
        define('WARNING', 1);
        define('ERROR', 2);
    }

    public function println($testname, $result_code, $recommendations = '&nbsp;')
    {
        echo "  <tr>\n";
        echo "    <td>{$testname}</td>\n";
        switch ($result_code)
        {
            case OK:
                echo "    <td style='color: green;'>OK</td>\n";
                break;

            case WARNING:
                echo "    <td style='color: orange;'>WARNING</td>\n";
                break;

            case ERROR:
                echo "    <td style='color: red;'>ERROR</td>\n";
                break;

            default:
                _midcom_stop_request("Unknown error code {$result_code}. Aborting.");
        }

        echo "    <td>{$recommendations}</td>\n";
        echo "  </tr>\n";
    }

    public function ini_get_filesize($setting)
    {
        $result = ini_get($setting);
        $last_char = substr($result, -1);
        if ($last_char == 'M')
        {
            $result = substr($result, 0, -1) * 1024 * 1024;
        }
        else if ($last_char == 'K')
        {
            $result = substr($result, 0, -1) * 1024;
        }
        else if ($last_char == 'G')
        {
            $result = substr($result, 0, -1) * 1024 * 1024 * 1024;
        }
        return $result;
    }

    public function ini_get_boolean($setting)
    {
        $result = ini_get($setting);
        if ($result == false || $result == "Off" || $result == "off" || $result == "" || $result == "0")
        {
            return false;
        }
        else
        {
            return true;
        }
    }

    public function check_for_utility ($name, $fail_code, $fail_recommendations, $ok_notice = '&nbsp;')
    {
        $executable = $GLOBALS['midcom_config']["utility_{$name}"];
        $testname = "External Utility: {$name}";
        if (is_null($executable))
        {
            var_dump($testname);

            $this->println($testname, $fail_code, "The path to the utility {$name} is not configured. {$fail_recommendations}");
        }
        else
        {
            exec ("which {$executable}", $output, $exitcode);
            if ($exitcode == 0)
            {
                $this->println($testname, OK, $ok_notice);
            }
            else
            {
                $this->println($testname, $fail_code, "The utility {$name} is not correctly configured: File ({$executable}) not found. {$fail_recommendations}");
            }
        }
    }

    private function _check_rcs()
    {
        $config = $GLOBALS['midcom_config'];
        if (array_key_exists('midcom_services_rcs_enable', $config) && $config['midcom_services_rcs_enable'])
        {
            if (!is_writable($config['midcom_services_rcs_root']))
            {
                $this->println("MidCOM RCS", ERROR, "You must make the directory <b>{$config['midcom_services_rcs_root']}</b> writable by your webserver!");
            }
            else if (!is_executable($config['midcom_services_rcs_bin_dir'] . "/ci"))
            {
                $this->println("MidCOM RCS", ERROR, "You must make <b>{$config['midcom_services_rcs_bin_dir']}/ci</b> executable by your webserver!");
            }
            else
            {
                $this->println("MidCOM RCS", OK);
            }
        }
        else
        {
            $this->println("MidCOM RCS", WARNING, "The MidCOM RCS service is disabled.");
        }
    }

    public function check_midcom()
    {
        if (version_compare(mgd_version(), '8.09.9', '<'))
        {
            $this->println('Midgard Version', ERROR, 'Midgard 8.09.9 or greater is required for this version of MidCOM.');
        }
        else
        {
            $this->println('Midgard Version', OK);
        }

        // Validate the Cache Base Directory.
        if  (! is_dir($GLOBALS['midcom_config']['cache_base_directory']))
        {
            $this->println('MidCOM cache base directory', ERROR, "The configured MidCOM cache base directory ({$GLOBALS['midcom_config']['cache_base_directory']}) does not exist or is not a directory. You have to create it as a directory writable by the Apache user.");
        }
        else if (! is_writable($GLOBALS['midcom_config']['cache_base_directory']))
        {
            $this->println('MidCOM cache base directory', ERROR, "The configured MidCOM cache base directory ({$GLOBALS['midcom_config']['cache_base_directory']}) is not writable by the Apache user. You have to create it as a directory writable by the Apache user.");
        }
        else
        {
            $this->println('MidCOM cache base directory', OK);
        }

        $this->_check_rcs();
    }

    public function check_php()
    {
        if (version_compare(phpversion(), '5.2.0', '<'))
        {
            $this->println('PHP Version', ERROR, 'PHP 5.2.0 or greater is required for MidCOM.');
        }
        else
        {
            $this->println('PHP Version', OK);
        }

        $cur_limit = $this->ini_get_filesize('memory_limit');
        if ($cur_limit >= (40 * 1024 * 1024))
        {
            $this->println('PHP Setting: memory_limit', OK);
        }
        else
        {
            $this->println('PHP Setting: memory_limit', ERROR, "MidCOM requires a minimum memory limit of 40 MB to operate correctly. Smaller amounts will lead to PHP Errors. Detected limit was {$cur_limit}.");
        }

        if ($this->ini_get_boolean('register_globals'))
        {
            $this->println('PHP Setting: register_globals', WARNING, 'register_globals is enabled, it is recommended to turn this off for security reasons');
        }
        else
        {
            $this->println('PHP Setting: register_globals', OK);
        }

        if ($this->ini_get_boolean('track_errors'))
        {
            $this->println('PHP Setting: track_errors', OK);
        }
        else
        {
            $this->println('PHP Setting: track_errors', WARNING, 'track_errors is disabled, it is strongly suggested to be activated as this allows the framework to handle more errors gracefully.');
        }

        $upload_limit = $this->ini_get_filesize('upload_max_filesize');
        if ($upload_limit >= (50 * 1024 * 1024))
        {
            $this->println('PHP Setting: upload_max_filesize', OK);
        }
        else
        {
            $this->println('PHP Setting: upload_max_filesize',
                             WARNING, "To make bulk uploads (for exampe in the Image Gallery) useful, you should increase the Upload limit to something above 50 MB. (Current setting: {$upload_limit})");
        }

        $post_limit = $this->ini_get_filesize('post_max_size');
        if ($post_limit >= $upload_limit)
        {
            $this->println('PHP Setting: post_max_size', OK);
        }
        else
        {
            $this->println('PHP Setting: post_max_size', WARNING, 'post_max_size should be larger then upload_max_filesize, as both limits apply during uploads.');
        }

        if (! $this->ini_get_boolean('magic_quotes_gpc'))
        {
            $this->println('PHP Setting: magic_quotes_gpc', OK);
        }
        else
        {
            $this->println('PHP Setting: magic_quotes_gpc', ERROR, 'Magic Quotes must be turned off, Midgard/MidCOM does this explicitly where required.');
        }
        if (! $this->ini_get_boolean('magic_quotes_runtime'))
        {
            $this->println('PHP Setting: magic_quotes_runtime', OK);
        }
        else
        {
            $this->println('PHP Setting: magic_quotes_runtime', ERROR, 'Magic Quotes must be turned off, Midgard/MidCOM does this explicitly where required.');
        }

        if (! function_exists('mb_strlen'))
        {
            $this->println('Multi-Byte String functions', ERROR, 'The Multi-Byte String functions are unavailable, they are required for MidCOM operation.');
        }
        else
        {
            $this->println('Multi-Byte String functions', OK);
        }

        if (! function_exists('iconv'))
        {
            $this->println('iconv', ERROR, 'The PHP iconv module is required for MidCOM operation.');
        }
        else
        {
            $this->println('iconv', OK);
        }
    }
}
?>