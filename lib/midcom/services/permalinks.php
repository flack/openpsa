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
 * @see midcom_baseclasses_components_interface::resolve_permalink()
 * @see midcom_baseclasses_components_interface::_on_resolve_permalink()
 * @package midcom.services
 */
class midcom_services_permalinks
{
    /**
     * This function resolves any GUID into a fully qualified URL which can be relocated
     * to. It operates in multiple phases:
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
    function resolve_permalink($guid)
    {
        // resolves a guid into a fully qualified url, uses some heuristics for that, mainly replaces
        // the nap permalink resolver, with the difference that it will be based on the
        // components permalink interface code.

        $nav = new midcom_helper_nav();

        // Step 1: Maybe NAP already knows the topic.
        $napobj = $nav->resolve_guid($guid);
        if ($napobj)
        {
            return $napobj[MIDCOM_NAV_FULLURL];
        }

        $object = $_MIDCOM->dbfactory->get_object_by_guid($guid);

        if (   !$object
            || !isset($object->guid)
            || empty($object->guid))
        {
            debug_add("Failed to resolve the GUID {$guid}, this is most probably an access denied error.", MIDCOM_LOG_ERROR);
            debug_add('Last MidCOM error string: ' . midcom_connection::get_error_string());
            return null;
        }

        $metadata = midcom_helper_metadata::retrieve($object);
        if (! $metadata->is_object_visible_onsite())
        {
            return null;
        }

        if (is_a($object, 'midcom_db_topic'))
        {
            $napobj = $nav->get_node($object->id);
            if (! $napobj)
            {
                debug_add("Failed to retrieve the NAP object for topic {$object->id}.", MIDCOM_LOG_INFO);
                return null;
            }
            return $napobj[MIDCOM_NAV_FULLURL];
        }

        if (is_a($object, 'midcom_db_attachment'))
        {
            // Faster linking to attachments
            $parent = $object->get_parent();
            if (   is_a($parent, 'midcom_db_topic')
                && $nav->is_node_in_tree($parent->id, $nav->get_root_node()))
            {
                $napobj = $nav->get_node($parent->id);
                return $napobj[MIDCOM_NAV_FULLURL] . $object->name;
            }
            else
            {
                return "{$GLOBALS['midcom_config']['midcom_site_url']}midcom-serveattachmentguid-{$object->guid}/{$object->name}";
            }
        }

        // Ok, unfortunately, this is not an immediate topic. We try to traverse
        // upwards in the object chain to find a topic.
        $topic = null;

        $parent = $object->get_parent();

        while ($parent)
        {
            if (is_a($parent, 'midcom_db_topic'))
            {
                // Verify that this topic is within the current sites tree, if it is not,
                // we ignore it. This might happen on symlink topics with static & co
                // which point to the outside f.x.
                if ($nav->is_node_in_tree($parent->id, $nav->get_root_node()))
                {
                    $topic = $parent;
                    break;
                }
            }
            $parent = $parent->get_parent();
        }

        if ($topic !== null)
        {
            $return_value = $this->_resolve_permalink_in_topic($topic, $guid);
            if ($return_value != null)
            {
                return $return_value;
            }
        }

        // Bad, this means a full scan,
        // We need to try every topic for the GUID.
        $topic_qb = midcom_db_topic::new_query_builder();
        $topic_qb->add_constraint('name', '<>', '');
        $topic_qb->add_constraint('up', 'INTREE', $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ROOTTOPIC)->id);
        $topics = $topic_qb->execute();
        foreach ($topics as $topic)
        {
            $result = $this->_resolve_permalink_in_topic($topic, $guid);
            if ($result !== null)
            {
                return $result;
            }
        }

        // We were unable to find the GUID
        return null;
    }

    function _resolve_permalink_in_topic($topic, $guid)
    {
        // get the interface class
        // if we have a next-generation-one, use it to look up the required information
        // otherwise settle with a NAP scan
        // in any way, return in the same way as resolve_permalink itself.

        $component = $topic->component;
        if (!$_MIDCOM->componentloader->is_installed($component))
        {
            return null;
        }
        $interface = $_MIDCOM->componentloader->get_interface_class($component);
        if ($interface === null)
        {
            debug_add("Failed to load the interface class for the component {$component} of the topic #{$topic->id}, cannot attempt to resolve the permalink here.",
                MIDCOM_LOG_WARN);
            debug_print_r('Passed topic was:', $topic);
            return null;
        }

        $result = $interface->resolve_permalink ($topic, $guid);
        if ($result === null)
        {
            return null;
        }

        $nav = new midcom_helper_nav();
        $node = $nav->get_node($topic->id);

        if (! $node)
        {
            debug_add("Failed to load the NAP information of the topic #{$topic->id}, cannot attempt to resolve the permalink here.",
                MIDCOM_LOG_WARN);
            debug_print_r('Passed topic was:', $topic);
            return null;
        }

        return "{$node[MIDCOM_NAV_FULLURL]}{$result}";
    }

    /**
     * This small helper should be used to create Permalink URLs from GUIDs. It always
     * points to the live site (given correct system configuration).
     *
     * @param string $guid The Guid to link to.
     * @return string The absolute URL of the Permalink.
     */
    function create_permalink($guid)
    {
        return "{$GLOBALS['midcom_config']['midcom_site_url']}midcom-permalink-{$guid}";
    }
}
?>
