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
    const OK = 0;
    const WARNING =  1;
    const ERROR = 2;

    public function println($testname, $result_code, $recommendations = '&nbsp;')
    {
        echo "  <tr class=\"test\">\n";
        echo "    <th>{$testname}</th>\n";
        switch ($result_code)
        {
            case self::OK:
                echo "    <td style='color: green;'>OK</td>\n";
                break;

            case self::WARNING:
                echo "    <td style='color: orange;'>WARNING</td>\n";
                break;

            case self::ERROR:
                echo "    <td style='color: red;'>ERROR</td>\n";
                break;

            default:
                throw new midcom_error("Unknown error code {$result_code}.");
        }

        echo "    <td>{$recommendations}</td>\n";
        echo "  </tr>\n";
    }

    public function print_header($heading)
    {
        echo "  <tr>\n";
        echo "    <th colspan=\"3\">{$heading}</th>\n";
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

    public function check_for_utility ($testname, $fail_code, $fail_recommendations, $ok_notice = '&nbsp;')
    {
        $executable = midcom::get('config')->get("utility_{$testname}");
        if (is_null($executable))
        {
            $this->println($testname, $fail_code, "The path to the utility {$testname} is not configured. {$fail_recommendations}");
        }
        else
        {
            exec ("which {$executable}", $output, $exitcode);
            if ($exitcode == 0)
            {
                $this->println($testname, self::OK, $ok_notice);
            }
            else
            {
                $this->println($testname, $fail_code, "The utility {$testname} is not correctly configured: File ({$executable}) not found. {$fail_recommendations}");
            }
        }
    }

    private function _check_rcs()
    {
        $config = $GLOBALS['midcom_config'];
        if (!empty($config['midcom_services_rcs_enable']))
        {
            if (!is_writable($config['midcom_services_rcs_root']))
            {
                $this->println("MidCOM RCS", self::ERROR, "You must make the directory <b>{$config['midcom_services_rcs_root']}</b> writable by your webserver!");
            }
            else if (!is_executable($config['midcom_services_rcs_bin_dir'] . "/ci"))
            {
                $this->println("MidCOM RCS", self::ERROR, "You must make <b>{$config['midcom_services_rcs_bin_dir']}/ci</b> executable by your webserver!");
            }
            else
            {
                $this->println("MidCOM RCS", self::OK);
            }
        }
        else
        {
            $this->println("MidCOM RCS", self::WARNING, "The MidCOM RCS service is disabled.");
        }
    }

    public function check_midcom()
    {
        $this->print_header('Framework');
        if (version_compare(mgd_version(), '8.09.9', '<'))
        {
            $this->println('Midgard Version', self::ERROR, 'Midgard 8.09.9 or greater is required for OpenPSA.');
        }
        else if (   extension_loaded('midgard2')
                 && version_compare(mgd_version(), '10.05.5', '<'))
        {
            $this->println('Midgard Version', self::ERROR, 'Midgard2 10.05.5 or greater is required for OpenPSA.');
        }
        else
        {
            $this->println('Midgard Version', self::OK, mgd_version());
        }

        // Validate the Cache Base Directory.
        $cachedir = midcom::get('config')->get('cache_base_directory');
        if  (! is_dir($cachedir))
        {
            $this->println('MidCOM cache base directory', self::ERROR, "The configured MidCOM cache base directory ({$cachedir}) does not exist or is not a directory. You have to create it as a directory writable by the Apache user.");
        }
        else if (! is_writable($cachedir))
        {
            $this->println('MidCOM cache base directory', self::ERROR, "The configured MidCOM cache base directory ({$cachedir}) is not writable by the Apache user. You have to create it as a directory writable by the Apache user.");
        }
        else
        {
            $this->println('MidCOM cache base directory', self::OK, $cachedir);
        }

        $this->_check_rcs();
    }

    public function check_php()
    {
        $this->print_header('PHP');
        if (version_compare(phpversion(), '5.3.0', '<'))
        {
            $this->println('Version', self::ERROR, 'PHP 5.3.0 or greater is required for MidCOM.');
        }
        else
        {
            $this->println('Version', self::OK, phpversion());
        }

        $cur_limit = $this->ini_get_filesize('memory_limit');
        if ($cur_limit >= (40 * 1024 * 1024))
        {
            $this->println('Setting: memory_limit', self::OK, ini_get('memory_limit'));
        }
        else
        {
            $this->println('Setting: memory_limit', self::ERROR, "MidCOM requires a minimum memory limit of 40 MB to operate correctly. Smaller amounts will lead to PHP Errors. Detected limit was {$cur_limit}.");
        }

        if ($this->ini_get_boolean('register_globals'))
        {
            $this->println('Setting: register_globals', self::WARNING, 'register_globals is enabled, it is recommended to turn this off for security reasons');
        }
        else
        {
            $this->println('Setting: register_globals', self::OK);
        }

        if ($this->ini_get_boolean('track_errors'))
        {
            $this->println('Setting: track_errors', self::OK);
        }
        else
        {
            $this->println('Setting: track_errors', self::WARNING, 'track_errors is disabled, it is strongly suggested to be activated as this allows the framework to handle more errors gracefully.');
        }

        $upload_limit = $this->ini_get_filesize('upload_max_filesize');
        if ($upload_limit >= (50 * 1024 * 1024))
        {
            $this->println('Setting: upload_max_filesize', self::OK, ini_get('upload_max_filesize'));
        }
        else
        {
            $this->println('Setting: upload_max_filesize',
                             self::WARNING, "To make bulk uploads (for exampe in the Image Gallery) useful, you should increase the Upload limit to something above 50 MB. (Current setting: {$upload_limit})");
        }

        $post_limit = $this->ini_get_filesize('post_max_size');
        if ($post_limit >= $upload_limit)
        {
            $this->println('Setting: post_max_size', self::OK, ini_get('post_max_size'));
        }
        else
        {
            $this->println('Setting: post_max_size', self::WARNING, 'post_max_size should be larger than upload_max_filesize, as both limits apply during uploads.');
        }

        if (! $this->ini_get_boolean('magic_quotes_gpc'))
        {
            $this->println('Setting: magic_quotes_gpc', self::OK);
        }
        else
        {
            $this->println('Setting: magic_quotes_gpc', self::ERROR, 'Magic Quotes must be turned off, Midgard/MidCOM does this explicitly where required.');
        }
        if (! $this->ini_get_boolean('magic_quotes_runtime'))
        {
            $this->println('Setting: magic_quotes_runtime', self::OK);
        }
        else
        {
            $this->println('Setting: magic_quotes_runtime', self::ERROR, 'Magic Quotes must be turned off, Midgard/MidCOM does this explicitly where required.');
        }

        if (! function_exists('mb_strlen'))
        {
            $this->println('Multi-Byte String functions', self::ERROR, 'The Multi-Byte String functions are unavailable, they are required for MidCOM operation.');
        }
        else
        {
            $this->println('Multi-Byte String functions', self::OK);
        }

        if (ini_get("apc.enabled") == "1")
        {
            $this->println("Bytecode cache", self::OK, "APC is enabled");
        }
        else if (ini_get("eaccelerator.enable") == "1")
        {
            $this->println("Bytecode cache", self::OK, "eAccelerator is enabled");
        }
        else
        {
            $this->println("Bytecode cache", self::WARNING, "A PHP bytecode cache is recommended for efficient MidCOM operation");
        }

        if (! class_exists('Memcache'))
        {
            $this->println('Memcache', self::WARNING, 'The PHP Memcache module is recommended for efficient MidCOM operation.');
        }
        else
        {
            if (!midcom::get('config')->get('cache_module_memcache_backend'))
            {
                $this->println('Memcache', self::WARNING, 'The PHP Memcache module is recommended for efficient MidCOM operation. It is available but is not set to be in use.');
            }
            else
            {
                if (midcom_services_cache_backend_memcached::$memcache_operational)
                {
                    $this->println('Memcache', self::OK);
                }
                else
                {
                    $this->println('Memcache', self::ERROR, "The PHP Memcache module is available and set to be in use, but it cannot be connected to.");
                }
            }
        }

        if (! function_exists('exif_read_data'))
        {
            $this->println('EXIF reader', self::WARNING, 'PHP-EXIF is not available. It required for proper operation of Image Gallery components.');
        }
        else
        {
            $this->println('EXIF reader', self::OK);
        }
        if (! function_exists('iconv'))
        {
            $this->println('iconv', self::ERROR, 'The PHP iconv module is required for MidCOM operation.');
        }
        else
        {
            $this->println('iconv', self::OK);
        }
    }
}
?>