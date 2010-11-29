<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: null.php 22991 2009-07-23 16:09:46Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Database backend that does not do anything.
 * @package midcom.services
 */
class midcom_services_cache_backend_null extends midcom_services_cache_backend
{
    /**
     * The full directory filename.
     *
     * @var string
     */
    private $_dirname = null;

    /**
     * This handler completes the configuration.
     */
    function _on_initialize()
    {
        return;
    }

    function _check_cache_dir()
    {
        return;
    }

    function _open($write = false) {}

    function _close() {}

    function get($key)
    {
        return null;
    }

    function put($key, $data)
    {
       return;
    }

    function remove($key)
    {
        return;
    }

    function remove_all()
    {
        return;
    }

    function exists($key)
    {
        return false;
    }
}
?>
