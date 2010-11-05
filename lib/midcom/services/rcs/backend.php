<?php
/**
 * Created on 25/08/2006
 * @author tarjei huse
 * @package midcom.services.rcs
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 *
 */

/**
 * @package midcom.services.rcs
 */
class midcom_services_rcs_backend
{

    /**
     * The configuration of the service
     */
    var $config = null;

    function __construct(&$object, &$config)
    {
        $this->config = $config;
    }

    /**
     * This function saves a revision of an object, creating a revision
     * if none exists
     *
     * @return boolean true if save succeeded.
     * @throws MIDCOM_ERRCRIT on serious errors.
     * @param string $comment the message to be saved with the object.
     */
    function update(&$object, $comment)
    {
        return false;
    }

    function get_revision($revision)
    {
        return false;
    }

    function version_exists($version)
    {
        return false;
    }

    function get_prev_version($version)
    {
        return false;
    }

    function get_next_version($version)
    {
        return false;
    }

    function list_history_numeric()
    {
        return array();
    }

    function list_history()
    {
        return array();
    }

    function get_diff($oldest_revision, $latest_revision)
    {
        return array();
    }

    function get_comment($revision)
    {
        return false;
    }

    function restore_to_revision($revision)
    {
        return false;
    }
}

/**
 * @package midcom.services.rcs
 */
class midcom_services_rcs_backend_null extends midcom_services_rcs_backend
{

    /**
     * Empty constructor
     */
    function __construct($object, &$config)
    {
        parent::__construct($config, $config);
    }

    function update(&$object, $comment)
    {
        return true;
    }
}
?>