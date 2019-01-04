<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Permalink management service.
 *
 * This service is intended to abstract permalink usage away and replaces the original
 * Permalink system integrated into the NAP system.
 *
 * It is fully decoupled from NAP, so objects, which should be reachable by Permalinks
 * no longer need NAP entries. To make the transition to this service transparent, the
 * system still includes a NAP GUID reverse-lookup, for backwards compatibility.
 *
 * The component interface is used to provide a selective way to resolve content objects
 * to their URLs, with some heuristics to speed up lookups if they can be mapped to a
 * topic.
 *
 * The current Permalink implementation limits granularity to a GUID level -- permalinks
 * map object GUIDs to pages. If you have multiple pages showing the same object, you need
 * to decide which one you wish to have as permalink and provide that URL for resolution.
 * For the forward lookup, it is allowed to have multiple pages set the same permalink.
 *
 * Permalinks are always of the form $midcom_root_page_prefix/midcom-permalink-$guid and will
 * redirect using a Location HTTP header. Since regular content pages are created, the result
 * will be cacheable using the content caching system. This obviously means, that if you
 * modify the permalink lookup rules, you have to invalidate all guids that affected by the
 * changes. MidCOM will assume that the resolution of Permalinks to real URLs is stable over
 * time otherwise. You can also set the no_cache flag during the resolver callback execution
 * if you discover that it is a URL you are responsible for but the result should not be
 * cached. See there for details.
 *
 * @see midcom_services_permalinks_resolver
 * @package midcom.services
 */
class midcom_services_permalinks
{
    /**
     * Resolve any GUID into a fully qualified URL which can be relocated
     * to. Operates in multiple phases:
     *
     * 1. Check, whether the GUID is already known by NAP. In case we have the corresponding
     *    node/leaf loaded, use its linking information directly.
     * 2. Look if we have a topic, in that case, we get the corresponding NAP node and use
     *    it to resolve the permalink. If that object is not retrievable, the lookup
     *    fails.
     * 3. We check whether the object in question has a topic as one of its ancestors. If yes,
     *    that topic and its corresponding component is used to lookup the GUID, which might
     *    fail.
     * 4. As a last resort we have to iterate over all NAP topics to do the resolving.
     *
     * @param string $guid The GUID to resolve.
     * @return string The full HTTP relocation'able URL to the GUID.
     */
    public function resolve_permalink($guid)
    {
        // resolves a guid into a fully qualified url, uses some heuristics for that, mainly replaces
        // the nap permalink resolver, with the difference that it will be based on the
        // components permalink interface code.

        $nav = new midcom_helper_nav();

        // Step 1: Maybe NAP already knows the topic.
        if ($napobj = $nav->resolve_guid($guid)) {
            return $napobj[MIDCOM_NAV_FULLURL];
        }

        try {
            $object = midcom::get()->dbfactory->get_object_by_guid($guid);
        } catch (midcom_error $e) {
            debug_add("Failed to resolve the GUID {$guid}, this is most probably an access denied error.", MIDCOM_LOG_ERROR);
            debug_add('Last MidCOM error string: ' . $e->getMessage());
            return null;
        }

        if (!$object->metadata->is_object_visible_onsite()) {
            return null;
        }

        if ($object instanceof midcom_db_topic) {
            $napobj = $nav->get_node($object->id);
            if (!$napobj) {
                debug_add("Failed to retrieve the NAP object for topic {$object->id}.", MIDCOM_LOG_INFO);
                return null;
            }
            return $napobj[MIDCOM_NAV_FULLURL];
        }

        if ($object instanceof midcom_db_attachment) {
            // Faster linking to attachments
            $parent = $object->get_parent();
            if (   is_a($parent, midcom_db_topic::class)
                && $nav->is_node_in_tree($parent->id, $nav->get_root_node())) {
                $napobj = $nav->get_node($parent->id);
                return $napobj[MIDCOM_NAV_FULLURL] . $object->name;
            }
            return $this->create_attachment_link($object->guid, $object->name);
        }

        // Ok, unfortunately, this is not an immediate topic. We try to traverse
        // upwards in the object chain to find a topic.
        $parent = $object->get_parent();

        while ($parent) {
            if ($parent instanceof midcom_db_topic) {
                // Verify that this topic is within the current sites tree, if it is not,
                // we ignore it.
                if ($nav->is_node_in_tree($parent->id, $nav->get_root_node())) {
                    if ($return_value = $this->_resolve_permalink_in_topic($parent, $object)) {
                        return $return_value;
                    }
                    break;
                }
            }
            $parent = $parent->get_parent();
        }

        // Bad, this means a full scan,
        // We need to try every topic for the GUID.
        $root_topic = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ROOTTOPIC);
        if (!empty($root_topic->id)) {
            $qb = midcom_db_topic::new_query_builder();
            $qb->add_constraint('name', '<>', '');
            $qb->add_constraint('up', 'INTREE', $root_topic->id);
            $topics = $qb->execute();
        } else {
            $topics = [$root_topic];
        }
        foreach ($topics as $topic) {
            if ($result = $this->_resolve_permalink_in_topic($topic, $object)) {
                return $result;
            }
        }

        // We were unable to find the GUID
        return null;
    }

    private function _resolve_permalink_in_topic(midcom_db_topic $topic, midcom_core_dbaobject $object)
    {
        $component = $topic->component;
        if (!midcom::get()->componentloader->is_installed($component)) {
            return null;
        }
        $nav = new midcom_helper_nav();
        if (!empty($topic->id)) {
            $node = $nav->get_node($topic->id);

            if (!$node) {
                debug_add("Failed to load the NAP information of the topic #{$topic->id}, cannot resolve the permalink here.", MIDCOM_LOG_WARN);
                debug_print_r('Passed topic was:', $topic);
                return null;
            }
            $prefix = $node[MIDCOM_NAV_FULLURL];
        } else {
            $prefix = midcom_connection::get_url('prefix');
        }

        $interface = midcom::get()->componentloader->get_interface_class($component);
        if ($interface instanceof midcom_services_permalinks_resolver) {
            $result = $interface->resolve_object_link($topic, $object);
        } else {
            $result = null;
            foreach ($nav->list_leaves($topic->id) as $leafid) {
                $leaf = $nav->get_leaf($leafid);
                if ($leaf[MIDCOM_NAV_GUID] == $object->guid) {
                    $result = $leaf[MIDCOM_NAV_URL];
                    break;
                }
            }
        }
        if ($result === null) {
            return null;
        }

        return "{$prefix}{$result}";
    }

    /**
     * Create Permalink URLs from GUIDs. They always point to the live site (given correct system configuration).
     *
     * @param string $guid The Guid to link to.
     * @return string The full URL of the Permalink.
     */
    public function create_permalink($guid)
    {
        return midcom::get()->config->get('midcom_site_url') . "midcom-permalink-{$guid}";
    }

    /**
     * Create Permalink URLs for attachments.
     * They always point to the live site (given correct system configuration).
     *
     * @param string $guid The GUID to link to.
     * @param string $filename The attachment's filename
     * @return string The full URL of the Permalink.
     */
    public function create_attachment_link($guid, $filename)
    {
        return midcom::get()->config->get('midcom_site_url') . 'midcom-serveattachmentguid-' . $guid . '/' . urlencode($filename);
    }
}
