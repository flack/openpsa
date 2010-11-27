<?php
/**
 * Created on 31/07/2006
 * @author tarjei huse
 * @package midcom.services.rcs
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 *
 * The config class is used to generate the RCS configuration.
 *
 */

/**
 * The class containing the configuration options for RCS.
 * @see midcom_services_rcs for an overview of the options
 *
 * @package midcom.services.rcs
 *
 */
 class midcom_services_rcs_config
 {
    /**
     * The array of configuration options
     * @var array
     * @access public
     */
    var $config = null;

    /**
     * Constructor
     */
    function __construct($config_array)
    {
        $this->config = $config_array;
    }

    /**
     * Factory function for the handler object.
     */
    function get_handler(&$object)
    {
        $class = $this->_get_handler_class();
        $config = $this->_get_config();
        return new $class($object, $this);
    }

    /**
     *
     * This method should return an array() of config options.
     */
    function _get_config()
    {
        return $this->config;
    }

    /**
     * Returns the root of the directory containg the RCS files.
     */
    function get_rcs_root()
    {
        return $this->config['midcom_services_rcs_root'];
    }

    /**
     * If the RCS service is enabled
     * (set by midcom_services_rcs_use)
     * @return boolean true if it is enabled
     */
    function use_rcs()
    {
        if (array_key_exists('midcom_services_rcs_enable', $this->config))
        {
            return $this->config['midcom_services_rcs_enable'];
        }

        return false;
    }

    /**
     * Returns the prefix for the rcs utilities.
     */
    function get_bin_prefix()
    {
        return $this->config['midcom_services_rcs_bin_dir'];
    }

    /**
     * Loads the backend file needed and returns the class.
     * @return string of the backend to start
     */
    function _get_handler_class()
    {
        if (   array_key_exists('midcom_services_rcs_enable',$this->config)
            && $this->config['midcom_services_rcs_enable'])
        {
            if ( $this->_test_rcs_config())
            {
                require_once MIDCOM_ROOT. '/midcom/services/rcs/backend/rcs.php';
                return 'midcom_services_rcs_backend_rcs';
            }
            else
            {
                $_MIDCOM->generate_error(MIDCOM_ERRCRIT, 'Tried to use RCS as wanted but failed. Please read the errorlog for more information.');
                return false;
            }
        }
        else
        {
            return 'midcom_services_rcs_backend_null';
        }
    }

    /**
     * Checks if the basic rcs service is usable.
     */
    function _test_rcs_config()
    {
        if (!array_key_exists('midcom_services_rcs_root', $this->config))
        {
            debug_add("midcom_services_rcs_root configuration not defined.\n", MIDCOM_LOG_ERROR);
            return false;
        }

        if (!is_writable($this->config['midcom_services_rcs_root']))
        {
            debug_add("The root RCS directory {$this->config['midcom_services_rcs_root']} is not writable!.\n", MIDCOM_LOG_ERROR);
            return false;
        }

        if (!array_key_exists('midcom_services_rcs_bin_dir', $this->config)) {
            debug_add("midcom_services_rcs_bin_dir configuration not defined. This must be defined before RCS will work.\n", MIDCOM_LOG_ERROR);
            return false;
        }

        if (!is_executable($this->config['midcom_services_rcs_bin_dir'] . "/ci")) {
            debug_add("Cannot execute {$this->config['midcom_services_rcs_bin_dir']}/ci.\n" .
                    " This must be changed before RCS will work.\n", MIDCOM_LOG_ERROR);
            return false;
        }
        return true;
    }
 }
?>