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

    private $messages = array
    (
        'midcom' => array(),
        'php' => array(),
        'external' => array()
    );

    private $section;

    private $status = self::OK;

    public function check()
    {
        $this->check_midcom();
        $this->check_php();
        $this->check_external();
    }

    public function get_status()
    {
        return $this->status;
    }

    private function add($testname, $result_code, $recommendations = '&nbsp;')
    {
        $this->messages[$this->section][$testname] = array
        (
            'result' => $result_code,
            'message' => $recommendations
        );
        $this->status = max($this->status, $result_code);
    }

    private function check_midcom()
    {
        $this->section = 'midcom';
        if (   extension_loaded('midgard')
            && version_compare(mgd_version(), '8.09.9', '<'))
        {
            $this->add('Midgard Version', self::ERROR, 'Midgard 8.09.9 or greater is required for OpenPSA.');
        }
        else if (   extension_loaded('midgard2')
                 && version_compare(mgd_version(), '10.05.5', '<'))
        {
            $this->add('Midgard Version', self::ERROR, 'Midgard2 10.05.5 or greater is required for OpenPSA.');
        }
        else
        {
            $this->add('Midgard Version', self::OK, mgd_version());
        }

        // Validate the Cache Base Directory.
        $cachedir = midcom::get()->config->get('cache_base_directory');
        if  (! is_dir($cachedir))
        {
            $this->add('MidCOM cache base directory', self::ERROR, "The configured MidCOM cache base directory ({$cachedir}) does not exist or is not a directory. You have to create it as a directory writable by the Apache user.");
        }
        else if (! is_writable($cachedir))
        {
            $this->add('MidCOM cache base directory', self::ERROR, "The configured MidCOM cache base directory ({$cachedir}) is not writable by the Apache user. You have to create it as a directory writable by the Apache user.");
        }
        else
        {
            $this->add('MidCOM cache base directory', self::OK, $cachedir);
        }

        $this->check_rcs();
    }

    private function check_rcs()
    {
        $config = midcom::get()->config;
        if ($config->get('midcom_services_rcs_enable'))
        {
            try
            {
                $config = new midcom_services_rcs_config($config);
                $config->test_rcs_config();
                $this->add("MidCOM RCS", self::OK);
            }
            catch (midcom_error $e)
            {
                $this->add("MidCOM RCS", self::ERROR, $e->getMessage());
            }
        }
        else
        {
            $this->add("MidCOM RCS", self::WARNING, "The MidCOM RCS service is disabled.");
        }
    }

    private function check_php()
    {
        $this->section = 'php';

        $cur_limit = $this->ini_get_filesize('memory_limit');
        if ($cur_limit >= (40 * 1024 * 1024))
        {
            $this->add('Setting: memory_limit', self::OK, ini_get('memory_limit'));
        }
        else
        {
            $this->add('Setting: memory_limit', self::ERROR, "MidCOM requires a minimum memory limit of 40 MB to operate correctly. Smaller amounts will lead to PHP Errors. Detected limit was {$cur_limit}.");
        }

        if ($this->ini_get_boolean('register_globals'))
        {
            $this->add('Setting: register_globals', self::WARNING, 'register_globals is enabled, it is recommended to turn this off for security reasons');
        }
        else
        {
            $this->add('Setting: register_globals', self::OK);
        }

        $upload_limit = $this->ini_get_filesize('upload_max_filesize');
        if ($upload_limit >= (50 * 1024 * 1024))
        {
            $this->add('Setting: upload_max_filesize', self::OK, ini_get('upload_max_filesize'));
        }
        else
        {
            $this->add('Setting: upload_max_filesize',
                             self::WARNING, "To make bulk uploads (for exampe in the Image Gallery) useful, you should increase the Upload limit to something above 50 MB. (Current setting: {$upload_limit})");
        }

        $post_limit = $this->ini_get_filesize('post_max_size');
        if ($post_limit >= $upload_limit)
        {
            $this->add('Setting: post_max_size', self::OK, ini_get('post_max_size'));
        }
        else
        {
            $this->add('Setting: post_max_size', self::WARNING, 'post_max_size should be larger than upload_max_filesize, as both limits apply during uploads.');
        }

        if (! $this->ini_get_boolean('magic_quotes_gpc'))
        {
            $this->add('Setting: magic_quotes_gpc', self::OK);
        }
        else
        {
            $this->add('Setting: magic_quotes_gpc', self::ERROR, 'Magic Quotes must be turned off, Midgard/MidCOM does this explicitly where required.');
        }
        if (! $this->ini_get_boolean('magic_quotes_runtime'))
        {
            $this->add('Setting: magic_quotes_runtime', self::OK);
        }
        else
        {
            $this->add('Setting: magic_quotes_runtime', self::ERROR, 'Magic Quotes must be turned off, Midgard/MidCOM does this explicitly where required.');
        }

        if (ini_get("opcache.enable") == "1")
        {
            $this->add("Bytecode cache", self::OK, "OPCache is enabled");
        }
        else if (ini_get("apc.enabled") == "1")
        {
            $this->add("Bytecode cache", self::OK, "APC is enabled");
        }
        else
        {
            $this->add("Bytecode cache", self::WARNING, "A PHP bytecode cache is recommended for efficient MidCOM operation");
        }

        if (! class_exists('Memcache'))
        {
            $this->add('Memcache', self::WARNING, 'The PHP Memcache module is recommended for efficient MidCOM operation.');
        }
        else if (!midcom::get()->config->get('cache_module_memcache_backend'))
        {
            $this->add('Memcache', self::WARNING, 'The PHP Memcache module is recommended for efficient MidCOM operation. It is available but is not set to be in use.');
        }
        else if (midcom_services_cache_backend_memcached::$memcache_operational)
        {
            $this->add('Memcache', self::OK);
        }
        else
        {
            $this->add('Memcache', self::ERROR, "The PHP Memcache module is available and set to be in use, but it cannot be connected to.");
        }

        if (!function_exists('exif_read_data'))
        {
            $this->add('EXIF reader', self::WARNING, 'PHP-EXIF is not available. It required for proper operation of Image Gallery components.');
        }
        else
        {
            $this->add('EXIF reader', self::OK);
        }
    }

    private function ini_get_filesize($setting)
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

    private function ini_get_boolean($setting)
    {
        $result = ini_get($setting);
        if (empty($result) || strtolower($result) == "off" || $result == "0")
        {
            return false;
        }
        return true;
    }

    private function check_external()
    {
        $this->section = 'external';
        // ImageMagick
        $cmd = midcom::get()->config->get('utility_imagemagick_base') . "identify -version";
        exec ($cmd, $output, $result);
        if ($result !== 0 && $result !== 1)
        {
            $this->add('ImageMagick', self::ERROR, 'The existence ImageMagick toolkit could not be verified, it is required for all kinds of image processing in MidCOM.');
        }
        else
        {
            $this->add('ImageMagick', self::OK);
        }

        // Other utilities
        $this->check_for_utility('find', self::WARNING, 'The find utility is required for bulk upload processing in the image galleries, you should install it if you plan to deploy Image Galleries.');
        $this->check_for_utility('unzip', self::WARNING, 'The unzip utility is required for bulk upload processing in the image galleries, you should install it if you plan to deploy Image Galleries.');
        $this->check_for_utility('tar', self::WARNING, 'The tar utility is required for bulk upload processing in the image galleries, you should install it if you plan to deploy Image Galleries.');
        $this->check_for_utility('gzip', self::WARNING, 'The gzip utility is required for bulk upload processing in the image galleries, you should install it if you plan to deploy Image Galleries.');
        $this->check_for_utility('jpegtran', self::WARNING, 'The jpegtran utility is used for lossless JPEG operations, even though ImageMagick can do the same conversions, the lossless features provided by this utility are used where appropriate, so its installation is recommended unless it is known to cause problems.', 'The jpegtran utility is used for lossless rotations of JPEG images. If there are problems with image rotations, disabling jpegtran, which will cause ImageMagick to be used instead, probably helps.');

        $this->check_for_utility('diff', self::WARNING, 'diff is needed by the versioning library');

        if (midcom::get()->config->get('indexer_backend'))
        {
            $this->check_for_utility('catdoc', self::ERROR, 'Catdoc is required to properly index Microsoft Word documents. It is strongly recommended to install it, otherwise Word documents will be indexed as binary files.');
            $this->check_for_utility('pdftotext', self::ERROR, 'pdftotext is required to properly index Adobe PDF documents. It is strongly recommended to install it, otherwise PDF documents will be indexed as binary files.');
            $this->check_for_utility('unrtf', self::ERROR, 'unrtf is required to properly index Rich Text Format documents. It is strongly recommended to install it, otherwise RTF documents will be indexed as binary files.');
        }
    }

    private function check_for_utility ($testname, $fail_code, $fail_recommendations, $ok_notice = '&nbsp;')
    {
        $executable = midcom::get()->config->get("utility_{$testname}");
        if (is_null($executable))
        {
            $this->add($testname, $fail_code, "The path to the utility {$testname} is not configured. {$fail_recommendations}");
        }
        else if (!exec('which which'))
        {
            $this->add('which', self::ERROR, "The 'which' utility cannot be found.");
        }
        else
        {
            exec ("which {$executable}", $output, $exitcode);
            if ($exitcode == 0)
            {
                $this->add($testname, self::OK, $ok_notice);
            }
            else
            {
                $this->add($testname, $fail_code, "The utility {$testname} is not correctly configured: File ({$executable}) not found. {$fail_recommendations}");
            }
        }
    }

    public function show()
    {
        echo '<table border="1" cellspacing="0" cellpadding="2">
              <tr>
                <th>Test</th>
                <th>Result</th>
                <th>Recommendations</th>
              </tr>';

        $this->print_section('Framework', $this->messages['midcom']);
        $this->print_section('PHP ' . PHP_VERSION, $this->messages['php']);
        $this->print_section('External Utilities', $this->messages['external']);

        echo '</table>';
    }

    private function print_section($heading, array $messages)
    {
        echo "  <tr>\n";
        echo "    <th colspan=\"3\">{$heading}</th>\n";
        echo "  </tr>\n";

        foreach ($messages as $testname => $data)
        {
            echo "  <tr class=\"test\">\n";
            echo "    <th>{$testname}</th>\n";
            switch ($data['result'])
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
                    throw new midcom_error("Unknown error code {$data['result']}.");
            }

            echo "    <td>{$data['message']}</td>\n";
            echo "  </tr>\n";
        }
    }
}
