<?php
/**
 * The class containing the configuration options for RCS.
 *
 * @author tarjei huse
 * @package midcom.services.rcs
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * The config class is used to generate the RCS configuration.
 *
 * @see midcom_services_rcs for an overview of the options
 * @package midcom.services.rcs
 */
class midcom_services_rcs_config
{
    /**
     * The array of configuration options
     *
     * @var array
     */
    private $config = null;

    /**
     * Constructor
     */
    public function __construct($config_array)
    {
        $this->config = $config_array;
    }

    /**
     * Factory function for the handler object.
     *
     * @return midcom_services_rcs_backend
     */
    public function get_handler($object)
    {
        $class = $this->_get_handler_class();
        return new $class($object, $this);
    }

    /**
     * Returns the root of the directory containg the RCS files.
     */
    public function get_rcs_root()
    {
        if (empty($this->config['midcom_services_rcs_root'])) {
            $basedir = "/var/lib/midgard";
            // TODO: Would be good to include DB name into the path
            if (extension_loaded('midgard')) {
                $prefix = midcom_connection::get('config', 'prefix');
                if ($prefix == '/usr/local') {
                    $basedir = '/var/local/lib/midgard';
                }
            } elseif (midgard_connection::get_instance()) {
                $basedir = dirname(midgard_connection::get_instance()->config->sharedir);
            }
            $this->config['midcom_services_rcs_root'] = $basedir . '/rcs';
        }

        return $this->config['midcom_services_rcs_root'];
    }

    /**
     * If the RCS service is enabled
     * (set by midcom_services_rcs_use)
     *
     * @return boolean true if it is enabled
     */
    public function use_rcs()
    {
        return (!empty($this->config['midcom_services_rcs_enable']));
    }

    /**
     * Returns the prefix for the rcs utilities.
     */
    public function get_bin_prefix()
    {
        if (!isset($this->config['midcom_services_rcs_bin_dir'])) {
            return null;
        }
        return $this->config['midcom_services_rcs_bin_dir'] . '/';
    }

    /**
     * Loads the backend file needed and returns the class.
     *
     * @return string of the backend to start
     */
    private function _get_handler_class()
    {
        if ($this->use_rcs()) {
            $this->test_rcs_config();
            return 'midcom_services_rcs_backend_rcs';
        }

        return 'midcom_services_rcs_backend_null';
    }

    /**
     * Checks if the basic rcs service is usable.
     */
    public function test_rcs_config()
    {
        if (!is_writable($this->get_rcs_root())) {
            throw new midcom_error("The root RCS directory {$this->config['midcom_services_rcs_root']} is not writable!");
        }

        if ($this->get_bin_prefix() === null) {
            throw new midcom_error("midcom_services_rcs_bin_dir not found in configuration. This must be defined before RCS will work.");
        }

        if (!is_executable($this->config['midcom_services_rcs_bin_dir'] . "/ci")) {
            throw new midcom_error("Cannot execute {$this->config['midcom_services_rcs_bin_dir']}/ci.");
        }
    }
}
