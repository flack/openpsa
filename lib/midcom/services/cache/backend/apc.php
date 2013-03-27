<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * APC caching backend.
 *
 * Requires the APC PECL extension to work.
 *
 * @package midcom.services
 * @see http://www.php.net/manual/en/ref.apc.php
 */

class midcom_services_cache_backend_apc extends midcom_services_cache_backend
{
    /**
     * Whether apc is working
     */
    static $apc_operational = true;

    /**
     * This handler completes the configuration.
     */
    public function _on_initialize()
    {
        $this->_auto_serialize = false; // apc doesn't need serialization to work
        self::$apc_operational = extension_loaded('apc') && ini_get('apc.enabled');
    }

    /**
     * This method is unused
     */
    function _open($write = false) {}

    /**
     * This method is unused
     */
    function _close() {}

    function _get($key)
    {
        if (!self::$apc_operational)
        {
            return;
        }

        $key = "{$this->_name}-{$key}";
        $result = apc_fetch($key, $success);

        if (false === $success)
        {
            return false;
        }
        return $result;
    }

    function _put($key, $data, $timeout=false)
    {
        if (!self::$apc_operational)
        {
            return;
        }

        $key = "{$this->_name}-{$key}";

        if ($timeout !== false)
        {
            apc_store($key, $data, $timeout);
        }
        else
        {
            apc_store($key, $data);
        }
    }

    function _remove($key)
    {
        if (!self::$apc_operational)
        {
            return;
        }

        $key = "{$this->_name}-{$key}";
        apc_delete($key);
    }

    function _remove_all()
    {
        if (!self::$apc_operational)
        {
            return;
        }

        apc_clear_cache('user');
    }

    function _exists($key)
    {
        if (!self::$apc_operational)
        {
            return false;
        }

        $key = "{$this->_name}-{$key}";
        apc_fetch($key, $success);

        return $success;
    }
}
?>