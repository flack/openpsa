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
 * The actual handling of the various dbs is done with Doctrine Cache, this
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
     * {@inheritDoc}
     */
    public function invalidate(string $guid, midcom_core_dbaobject $object = null)
    {
        $napobject = $this->get_guid($guid);

        if (!$napobject) {
            // The object itself is not in cache, but it still might have a parent that
            // needs invalidating (f.x. if it is newly-created or was moved from outside the tree)
            $napobject = $this->_load_from_guid($guid, $object);
            if (!$napobject) {
                // We couldn't load the object (because it's deleted f.x.) or it is not in NAP.
                // Either way, there is nothing more we can do here.
                return;
            }
        }

        if ($napobject[MIDCOM_NAV_TYPE] == 'leaf') {
            $cached_node_id = $napobject[MIDCOM_NAV_NODEID];
            // Get parent from DB and compare to catch moves
            if ($parent = $napobject[MIDCOM_NAV_OBJECT]->get_parent()) {
                $parent_entry = $this->get_guid($parent->guid);
                if (   $parent_entry
                    && $parent_entry[MIDCOM_NAV_ID] != $cached_node_id) {
                    $this->backend->deleteItem($parent_entry[MIDCOM_NAV_ID] . '-leaves');
                }
            }
            if (!empty($napobject[MIDCOM_NAV_GUID])) {
                $this->backend->deleteItem($napobject[MIDCOM_NAV_GUID]);
            }
        } else {
            $cached_node_id = $napobject[MIDCOM_NAV_ID];

            //Invalidate subnode cache for the (cached) parent
            $parent_id = $napobject[MIDCOM_NAV_NODEID];
            $parent_entry = $this->get_node($parent_id);

            if (   $parent_entry
                && array_key_exists(MIDCOM_NAV_SUBNODES, $parent_entry)) {
                unset($parent_entry[MIDCOM_NAV_SUBNODES]);
                $this->put_node($parent_id, $parent_entry);
            }

            //Cross-check parent value from object to detect topic moves
            if ($parent = $napobject[MIDCOM_NAV_OBJECT]->get_parent()) {
                $parent_entry_from_object = $this->get_guid($parent->guid);

                if (    !empty($parent_entry_from_object[MIDCOM_NAV_ID])
                     && !empty($parent_entry[MIDCOM_NAV_ID])
                     && $parent_entry_from_object[MIDCOM_NAV_ID] != $parent_entry[MIDCOM_NAV_ID]) {
                    unset($parent_entry_from_object[MIDCOM_NAV_SUBNODES]);
                    $this->put_node($parent_entry_from_object[MIDCOM_NAV_ID], $parent_entry_from_object);
                }
            }
        }

        $leaves_key = "{$cached_node_id}-leaves";

        $this->backend->deleteItem((string) $cached_node_id);
        $this->backend->deleteItem($napobject[MIDCOM_NAV_GUID]);
        $this->backend->deleteItem($leaves_key);
    }

    private function _load_from_guid(string $guid, ?midcom_core_dbaobject $object) : ?array
    {
        try {
            $object ??= midcom::get()->dbfactory->get_object_by_guid($guid);
            $nav = new midcom_helper_nav;
            if ($object instanceof midcom_db_topic) {
                return $nav->get_node($object->id);
            }
            if (   ($node = $nav->find_closest_topic($object))
                && $nodeobject = $nav->get_node($node->id)) {
                return $nav->get_leaf($nodeobject[MIDCOM_NAV_ID] . '-' . $object->id);
            }
        } catch (midcom_error $e) {
            $e->log();
        }
        return null;
    }

    /**
     * Looks up a node in the cache and returns it. Not existent
     * keys are caught in this call as well, so you do not need
     * to call exists first.
     */
    public function get_node(string $key) : ?array
    {
        $lang_id = midcom::get()->i18n->get_current_language();
        return $this->backend->getItem($key)->get()[$lang_id] ?? null;
    }

    /**
     * Looks up a node in the cache and returns it. Not existent
     * keys are caught in this call as well, so you do not need
     * to call exists first.
     */
    public function get_leaves(string $key) : ?array
    {
        $lang_id = midcom::get()->i18n->get_current_language();
        return $this->backend->getItem($key)->get()[$lang_id] ?? null;
    }

    /**
     * Sets a given node key in the cache.
     *
     * @param mixed $data The data to store.
     */
    public function put_node(string $key, $data)
    {
        $item = $this->backend->getItem($key);
        $lang_id = midcom::get()->i18n->get_current_language();
        $result = $item->get() ?: [];
        $result[$lang_id] = $data;
        $this->backend->save($item->set($result));
        // symfony cache doesn't like empty cache keys
        if ($data[MIDCOM_NAV_GUID]) {
            $guid_item = $this->backend->getItem($data[MIDCOM_NAV_GUID]);
            $this->backend->save($guid_item->set($result));
        }
    }

    /**
     * Save a given array by GUID in the cache.
     *
     * @param mixed $data The data to store.
     */
    public function put_guid(string $guid, $data)
    {
        $lang_id = midcom::get()->i18n->get_current_language();
        $item = $this->backend->getItem($guid);
        $result = $item->get() ?: [];
        $result[$lang_id] = $data;
        $this->backend->save($item->set($result));
    }

    /**
     * Get a given array by GUID from the cache.
     */
    public function get_guid(string $guid) : ?array
    {
        $lang_id = midcom::get()->i18n->get_current_language();
        return $this->backend->getItem($guid)->get()[$lang_id] ?? null;
    }

    /**
     * Sets a given leave key in the cache
     *
     * @param mixed $data The data to store.
     */
    public function put_leaves(string $key, $data)
    {
        $lang_id = midcom::get()->i18n->get_current_language();
        $item = $this->backend->getItem($key);
        $result = $item->get() ?: [];
        $result[$lang_id] = $data;
        $this->backend->save($item->set($result));
    }
}
