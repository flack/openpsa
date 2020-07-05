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
     * @var midcom_config
     */
    private $config;

    /**
     * Constructor
     */
    public function __construct(midcom_config $config)
    {
        $this->config = $config;
    }

    /**
     * Returns the root of the directory containing the version-controlled files.
     */
    public function get_rootdir() : string
    {
        if (empty($this->config['midcom_services_rcs_root'])) {
            $basedir = dirname(midgard_connection::get_instance()->config->sharedir);
            $this->config['midcom_services_rcs_root'] = $basedir . '/rcs';
        }

        return $this->config['midcom_services_rcs_root'];
    }

    /**
     * If the RCS service is enabled
     * (set by midcom_services_rcs_enable)
     *
     * @return boolean true if it is enabled
     */
    public function use_rcs() : bool
    {
        return !empty($this->config['midcom_services_rcs_enable']);
    }

    /**
     * Returns the prefix for the rcs utilities.
     */
    public function get_bin_prefix() : string
    {
        return $this->config['midcom_services_rcs_bin_dir'] . '/';
    }

    /**
     * Returns the backend classname.
     */
    public function get_backend_class() : string
    {
        if ($this->use_rcs()) {
            return midcom_services_rcs_backend_rcs::class;
        }

        return midcom_services_rcs_backend_null::class;
    }
}
