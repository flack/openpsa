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
    const WARNING = 1;
    const ERROR = 2;

    private $messages = [
        'midcom' => [],
        'php' => [],
        'external' => []
    ];

    private $section;

    private $status = self::OK;

    public function check()
    {
        $this->check_midcom();
        $this->check_php();
        $this->check_external();
    }

    public function get_status() : int
    {
        return $this->status;
    }

    private function add(string $testname, int $result_code, string $recommendations = '&nbsp;')
    {
        $this->messages[$this->section][$testname] = [
            'result' => $result_code,
            'message' => $recommendations
        ];
        $this->status = max($this->status, $result_code);
    }

    private function check_midcom()
    {
        $this->section = 'midcom';

        // Validate the Cache Base Directory.
        $cachedir = midcom::get()->getCacheDir();
        if (!is_dir($cachedir)) {
            $this->add('MidCOM cache base directory', self::ERROR, "The configured MidCOM cache base directory ({$cachedir}) does not exist or is not a directory. You have to create it as a directory writable by the Apache user.");
        } elseif (!is_writable($cachedir)) {
            $this->add('MidCOM cache base directory', self::ERROR, "The configured MidCOM cache base directory ({$cachedir}) is not writable by the Apache user. You have to create it as a directory writable by the Apache user.");
        } else {
            $this->add('MidCOM cache base directory', self::OK, $cachedir);
        }

        $lang = midcom::get()->i18n->get_current_language();
        $locale = Locale::getDefault();
        if ($lang != substr($locale, 0, 2)) {
            $this->add('MidCOM language', self::WARNING, 'Language is set to "' . $lang . '", but the locale "' . $locale . '" is used. This might lead to problems in datamanager number inputs if decimal separators diverge');
        } else {
            $this->add('MidCOM language', self::OK, $locale);
        }

        $this->check_rcs();
    }

    private function check_rcs()
    {
        $config = midcom::get()->config;
        if ($config->get('midcom_services_rcs_enable')) {
            try {
                $config = new midcom_services_rcs_config($config);
                $config->test_rcs_config();
                $this->add("MidCOM RCS", self::OK);
            } catch (midcom_error $e) {
                $this->add("MidCOM RCS", self::ERROR, $e->getMessage());
            }
        } else {
            $this->add("MidCOM RCS", self::WARNING, "The MidCOM RCS service is disabled.");
        }
    }

    private function check_php()
    {
        $this->section = 'php';

        $cur_limit = $this->ini_get_filesize('memory_limit');
        if ($cur_limit >= (40 * 1024 * 1024)) {
            $this->add('Setting: memory_limit', self::OK, ini_get('memory_limit'));
        } else {
            $this->add('Setting: memory_limit', self::ERROR, "MidCOM requires a minimum memory limit of 40 MB to operate correctly. Smaller amounts will lead to PHP Errors. Detected limit was {$cur_limit}.");
        }

        $upload_limit = $this->ini_get_filesize('upload_max_filesize');
        if ($upload_limit >= (50 * 1024 * 1024)) {
            $this->add('Setting: upload_max_filesize', self::OK, ini_get('upload_max_filesize'));
        } else {
            $this->add('Setting: upload_max_filesize',
                             self::WARNING, "To make bulk uploads (for exampe in the Image Gallery) useful, you should increase the Upload limit to something above 50 MB. (Current setting: {$upload_limit})");
        }

        $post_limit = $this->ini_get_filesize('post_max_size');
        if ($post_limit >= $upload_limit) {
            $this->add('Setting: post_max_size', self::OK, ini_get('post_max_size'));
        } else {
            $this->add('Setting: post_max_size', self::WARNING, 'post_max_size should be larger than upload_max_filesize, as both limits apply during uploads.');
        }

        if (ini_get("opcache.enable") == "1") {
            $this->add("OPCache", self::OK);
        } else {
            $this->add("OPCache", self::WARNING, "OPCache is recommended for efficient MidCOM operation");
        }

        $this->check_memcached();

        if (!function_exists('exif_read_data')) {
            $this->add('EXIF reader', self::WARNING, 'PHP-EXIF is not available. It required for proper operation of Image Gallery components.');
        } else {
            $this->add('EXIF reader', self::OK);
        }
    }

    private function check_memcached()
    {
        if (!class_exists('Memcached')) {
            $this->add('Memcache', self::WARNING, 'The PHP memcached module is recommended for efficient MidCOM operation.');
        } elseif (!midcom::get()->config->get('cache_module_memcache_backend')) {
            $this->add('Memcache', self::WARNING, 'The PHP memcached module is recommended for efficient MidCOM operation. It is available but is not set to be in use.');
        } else {
            $config = midcom::get()->config->get('cache_module_memcache_backend_config');
            $memcached = midcom_services_cache_module::prepare_memcached($config);
            // Sometimes, addServer returns true even if the server is not running, so we call a command to make sure it's actually working
            if ($memcached && $memcached->getVersion()) {
                $this->add('Memcache', self::OK);
            } else {
                $this->add('Memcache', self::ERROR, "The PHP memcached module is available and set to be in use, but it cannot be connected to.");
            }
        }
    }

    private function ini_get_filesize(string $setting) : int
    {
        $result = ini_get($setting);
        $last_char = substr($result, -1);
        if ($last_char == 'M') {
            $result = substr($result, 0, -1) * 1024 * 1024;
        } elseif ($last_char == 'K') {
            $result = substr($result, 0, -1) * 1024;
        } elseif ($last_char == 'G') {
            $result = substr($result, 0, -1) * 1024 * 1024 * 1024;
        }
        return $result;
    }

    private function check_external()
    {
        $this->section = 'external';
        // ImageMagick
        $cmd = midcom::get()->config->get('utility_imagemagick_base') . "identify -version";
        exec($cmd, $output, $result);
        if ($result !== 0 && $result !== 1) {
            $this->add('ImageMagick', self::ERROR, 'The existence ImageMagick toolkit could not be verified, it is required for all kinds of image processing in MidCOM.');
        } else {
            $this->add('ImageMagick', self::OK);
        }

        $this->check_for_utility('jpegtran', self::WARNING, 'The jpegtran utility is used for lossless JPEG operations, even though ImageMagick can do the same conversions, the lossless features provided by this utility are used where appropriate, so its installation is recommended unless it is known to cause problems.', 'The jpegtran utility is used for lossless rotations of JPEG images. If there are problems with image rotations, disabling jpegtran, which will cause ImageMagick to be used instead, probably helps.');

        if (midcom::get()->config->get('indexer_backend')) {
            $this->check_for_utility('catdoc', self::ERROR, 'Catdoc is required to properly index Microsoft Word documents. It is strongly recommended to install it, otherwise Word documents will be indexed as binary files.');
            $this->check_for_utility('pdftotext', self::ERROR, 'pdftotext is required to properly index Adobe PDF documents. It is strongly recommended to install it, otherwise PDF documents will be indexed as binary files.');
            $this->check_for_utility('unrtf', self::ERROR, 'unrtf is required to properly index Rich Text Format documents. It is strongly recommended to install it, otherwise RTF documents will be indexed as binary files.');
        }
    }

    private function check_for_utility(string $testname, int $fail_code, string $fail_recommendations, string $recommendations = '&nbsp;')
    {
        $executable = midcom::get()->config->get("utility_{$testname}");
        if ($executable === null) {
            $this->add($testname, $fail_code, "The path to the utility {$testname} is not configured. {$fail_recommendations}");
        } elseif (!exec('which which')) {
            $this->add('which', self::ERROR, "The 'which' utility cannot be found.");
        } else {
            exec("which {$executable}", $output, $exitcode);
            if ($exitcode == 0) {
                $this->add($testname, self::OK, $recommendations);
            } else {
                $this->add($testname, $fail_code, "The utility {$testname} is not correctly configured: File ({$executable}) not found. {$fail_recommendations}");
            }
        }
    }

    public function show()
    {
        echo '<table>';

        $this->print_section('MidCOM ' . midcom::VERSION, $this->messages['midcom']);
        $this->print_section($_SERVER['SERVER_SOFTWARE'], $this->messages['php']);
        $this->print_section('External Utilities', $this->messages['external']);

        echo '</table>';
    }

    private function print_section(string $heading, array $messages)
    {
        echo "  <tr>\n";
        echo "    <th colspan=\"2\">{$heading}</th>\n";
        echo "  </tr>\n";

        foreach ($messages as $testname => $data) {
            echo "  <tr class=\"test\">\n    <th>\n";
            switch ($data['result']) {
                case self::OK:
                    echo "    <i class='fa fa-check' style='color: green;' title='OK'></i>";
                    break;

                case self::WARNING:
                    echo "    <i class='fa fa-exclamation-triangle' style='color: orange;' title='WARNING'></i>";
                    break;

                case self::ERROR:
                    echo "    <i class='fa fa-exclamation-circle' style='color: red;' title='ERROR'></i>";
                    break;

                default:
                    throw new midcom_error("Unknown error code {$data['result']}.");
            }

            echo " {$testname}</th>\n";
            echo "    <td>{$data['message']}</td>\n";
            echo "  </tr>\n";
        }
    }
}
