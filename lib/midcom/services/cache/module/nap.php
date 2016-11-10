<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * This is the NAP caching module. It provides the basic management functionality
 * for the various backend databases which will be created based on the root topic of the
 * NAP trees.
 *
 * The actual handling of the various dbs is done with nav/backend.php, this
 * class is responsible for the creation of backend instances and invalidation for NAP
 * cache objects. (Which implies that it is fully aware of the data structures
 * stored in the cache.)
 *
 * All entries are indexed by their Midgard Object GUID. The entries in the NAP cache
 * basically resemble the arrays within the NAP backend node/leaf cache
 *
 * NAP caches can be shared over multiple sites, as all site specific data (like
 * site prefixes) are evaluated during runtime.
 *
 * Most of the cache update work is done in midcom_helper_nav_backend,
 * so you should look there for details about the caching strategy.
 *
 * @see midcom_helper_nav_backend
 *
 * @package midcom.services
 */
class midcom_services_cache_module_nap extends midcom_services_cache_module
{
    /**
     * The cache backend instance to use.
     *
     * @var Doctrine\Common\Cache\CacheProvider
     */
    private $_cache;

    /**
     * Initialization event handler.
     *
     * It will load the cache backends for the current MidCOM topic.
     *
     * Initializes the backend configuration.
     */
    public function _on_initialize()
    {
        if ($driver = midcom::get()->config->get('cache_module_memcache_backend'))
        {
            $config = midcom::get()->config->get('cache_module_memcache_backend_config');
            $config['driver'] = $driver;
            $this->_cache = $this->_create_backend('module_nap', $config);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function invalidate($guid, $object = null)
    {
        $napobject = $this->get_guid($guid);

        if (!$napobject)
        {
            // The object itself is not in cache, but it still might have a parent that
            // needs invalidating (f.x. if it is newly-created or was moved from outside the tree)
            $napobject = $this->_load_from_guid($guid, $object);
            if (!$napobject)
            {
                // We couldn't load the object (because it's deleted f.x.) or it is not in NAP.
                // Either way, there is nothing more we can do here.
                return;
            }
        }

        if ($napobject[MIDCOM_NAV_TYPE] == 'leaf')
        {
            $cached_node_id = $napobject[MIDCOM_NAV_NODEID];
            // Get parent from DB and compare to catch moves
            if ($parent = $napobject[MIDCOM_NAV_OBJECT]->get_parent())
            {
                $parent_entry_from_object = $this->get_guid($parent->guid);
                if (    $parent_entry_from_object
                     && $parent_entry_from_object[MIDCOM_NAV_ID] != $cached_node_id)
                {
                    $this->_cache->delete($this->_prefix . '-' . $parent_entry_from_object[MIDCOM_NAV_ID] . '-leaves');
                }
            }
            if (!empty($napobject[MIDCOM_NAV_GUID]))
            {
                $this->_cache->delete($this->_prefix . '-' . $napobject[MIDCOM_NAV_GUID]);
            }
        }
        else
        {
            $cached_node_id = $napobject[MIDCOM_NAV_ID];

            //Invalidate subnode cache for the (cached) parent
            $parent_id = $napobject[MIDCOM_NAV_NODEID];
            $parent_entry = $this->get_node($parent_id);

            if (   $parent_entry
                && array_key_exists(MIDCOM_NAV_SUBNODES, $parent_entry))
            {
                unset($parent_entry[MIDCOM_NAV_SUBNODES]);
                $this->put_node($parent_id, $parent_entry);
            }

            //Cross-check parent value from object to detect topic moves
            if ($parent = $napobject[MIDCOM_NAV_OBJECT]->get_parent())
            {
                $parent_entry_from_object = $this->get_guid($parent->guid);

                if (    !empty($parent_entry_from_object[MIDCOM_NAV_ID])
                     && !empty($parent_entry[MIDCOM_NAV_ID])
                     && $parent_entry_from_object[MIDCOM_NAV_ID] != $parent_entry[MIDCOM_NAV_ID])
                {
                    unset($parent_entry_from_object[MIDCOM_NAV_SUBNODES]);
                    $this->put_node($parent_entry_from_object[MIDCOM_NAV_ID], $parent_entry_from_object);
                }
            }
        }

        $leaves_key = "{$cached_node_id}-leaves";

        $this->_cache->delete("{$this->_prefix}-{$cached_node_id}");
        $this->_cache->delete($this->_prefix . '-' . $napobject[MIDCOM_NAV_GUID]);
        $this->_cache->delete("{$this->_prefix}-{$leaves_key}");
    }

    private function _load_from_guid($guid, $object = null)
    {
        $napobject = false;
        try
        {
            if (!is_object($object))
            {
                $object = midcom::get()->dbfactory->get_object_by_guid($guid);
            }
            $nav = new midcom_helper_nav;
            if (is_a($object, 'midcom_db_topic'))
            {
                $napobject = $nav->get_node($object->id);
            }
            elseif (   ($node = $nav->find_closest_topic($object))
                     && $nodeobject = $nav->get_node($node->id))
            {
                $napobject = $nav->get_leaf($nodeobject[MIDCOM_NAV_ID] . '-' . $object->id);
            }
        }
        catch (midcom_error $e)
        {
            $e->log();
        }
        return $napobject;
    }

    /**
     * Looks up a node in the cache and returns it. Not existent
     * keys are caught in this call as well, so you do not need
     * to call exists first.
     *
     * @param string $key The key to look up.
     * @return mixed The cached value on success, false on failure.
     */
    public function get_node($key)
    {
        if ($this->_cache === null)
        {
            return false;
        }
        $lang_id = midcom::get()->i18n->get_current_language();
        $result = $this->_cache->fetch("{$this->_prefix}-{$key}");
        if (   !is_array($result)
            || !isset($result[$lang_id]))
        {
            return false;
        }

        return $result[$lang_id];
    }

    /**
     * Looks up a node in the cache and returns it. Not existent
     * keys are caught in this call as well, so you do not need
     * to call exists first.
     *
     * @param string $key The key to look up.
     * @return mixed The cached value on success, false on failure.
     */
    public function get_leaves($key)
    {
        if ($this->_cache === null)
        {
            return false;
        }

        $lang_id = midcom::get()->i18n->get_current_language();
        $result = $this->_cache->fetch("{$this->_prefix}-{$key}");
        if (   !is_array($result)
            || !isset($result[$lang_id]))
        {
            return false;
        }

        return $result[$lang_id];
    }

    /**
     * Checks for the existence of a key in the cache.
     *
     * @param string $key The key to look up.
     * @return boolean Indicating existence
     */
    function exists($key)
    {
        if ($this->_cache === null)
        {
            return false;
        }

        $lang_id = midcom::get()->i18n->get_current_language();
        $result = $this->_cache->fetch("{$this->_prefix}-{$key}");
        if (   !is_array($result)
            || !isset($result[$lang_id]))
        {
            return false;
        }

        return true;
    }

    /**
     * Sets a given node key in the cache.
     *
     * @param string $key The key to look up.
     * @param mixed $data The data to store.
     * @param int $timeout how long the data should live in the cache.
     */
    public function put_node($key, $data, $timeout = false)
    {
        if ($this->_cache === null)
        {
            return;
        }

        $lang_id = midcom::get()->i18n->get_current_language();
        $result = $this->_cache->fetch("{$this->_prefix}-{$key}");
        if (!is_array($result))
        {
            $result = array();
        }
        $result[$lang_id] = $data;
        $this->_cache->save("{$this->_prefix}-{$key}", $result, $timeout);
        $this->_cache->save($this->_prefix . '-' . $data[MIDCOM_NAV_GUID], $result, $timeout);
    }

    /**
     * Save a given array by GUID in the cache.
     *
     * @param string $guid The key to store.
     * @param mixed $data The data to store.
     * @param int $timeout how long the data should live in the cache.
     */
    public function put_guid($guid, $data, $timeout = false)
    {
        if ($this->_cache === null)
        {
            return;
        }

        $lang_id = midcom::get()->i18n->get_current_language();
        $result = $this->_cache->fetch("{$this->_prefix}-{$guid}");
        if (!is_array($result))
        {
            $result = array();
        }
        $result[$lang_id] = $data;
        $this->_cache->save($this->_prefix . '-' . $guid, $result, $timeout);
    }

    /**
     * Get a given array by GUID from the cache.
     *
     * @param string $guid The key to look up.
     * @param int $timeout how long the data should live in the cache.
     */
    public function get_guid($guid, $timeout = false)
    {
        if ($this->_cache === null)
        {
            return;
        }

        $lang_id = midcom::get()->i18n->get_current_language();
        $result = $this->_cache->fetch("{$this->_prefix}-{$guid}");
        if (   !is_array($result)
            || !isset($result[$lang_id]))
        {
            return false;
        }
        return $result[$lang_id];
    }

    /**
     * Sets a given leave key in the cache
     *
     * @param string $key The key to look up.
     * @param mixed $data The data to store.
     * @param int $timeout how long the data should live in the cache.
     */
    public function put_leaves($key, $data, $timeout = false)
    {
        if ($this->_cache === null)
        {
            return;
        }

        $lang_id = midcom::get()->i18n->get_current_language();
        $result = $this->_cache->fetch("{$this->_prefix}-{$key}");
        if (!is_array($result))
        {
            $result = array();
        }
        $result[$lang_id] = $data;
        $this->_cache->save("{$this->_prefix}-{$key}", $result, $timeout);
    }
}
