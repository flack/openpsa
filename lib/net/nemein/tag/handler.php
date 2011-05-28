<?php
/**
 * @package net.nemein.tag
 * @author Henri Bergius, http://bergie.iki.fi
 * @copyright Nemein Oy, http://www.nemein.com
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Tag handling library
 *
 * @package net.nemein.tag
 */
class net_nemein_tag_handler extends midcom_baseclasses_components_purecode
{
    /**
     * Tags given object with the tags in the string
     *
     * Creates missing tags and tag_links, sets tag_link navorder
     * Deletes tag links from object that are not in the list provided
     *
     * @param object &$object MidCOM DBA object
     * @param array $tags List of tags and urls, tag is key, url is value
     * @return boolean indicating success/failure
     * @todo Set the link->navorder property
     */
    public static function tag_object(&$object, $tags, $component = null)
    {
        if (is_null($component))
        {
            $component = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_COMPONENT);
        }
        $existing_tags = net_nemein_tag_handler::get_object_tags($object);
        if (!is_array($existing_tags))
        {
            // Major failure when getting existing tags
            debug_add('get_object_tags() reported critical failure, aborting', MIDCOM_LOG_ERROR);
            return false;
        }
        // Determine operations
        $add_tags = array();
        $update_tags = array();
        $remove_tags = array();
        foreach ($tags as $tagname => $url)
        {
            if (empty($tagname))
            {
                unset($tags[$tagname]);
                continue;
            }
            if (!array_key_exists($tagname, $existing_tags))
            {
                $add_tags[$tagname] = $url;
            }
            else if (!empty($url))
            {
                $update_tags[$tagname] = $url;
            }
        }
        foreach ($existing_tags as $tagname => $url)
        {
            if (!array_key_exists($tagname, $tags))
            {
                $remove_tags[$tagname] = true;
            }
        }

        // Excute
        foreach ($remove_tags as $tagname => $bool)
        {
            self::_remove_tag($tagname, $object->guid);
        }
        foreach ($update_tags as $tagname => $url)
        {
            self::_update_tag($tagname, $url, $object->guid);
        }
        foreach ($add_tags as $tagname => $url)
        {
            self::_create_tag($tagname, $url, $object, $component);
        }

        return true;
    }

    private static function _create_tag($tagname, $url, $object, $component)
    {
        debug_add("Adding tag \"{$tagname}\" for object {$object->guid}");
        $tagstring = self::resolve_tagname($tagname);
        $context = self::resolve_context($tagname);
        $value = self::resolve_value($tagname);
        $tag = net_nemein_tag_tag_dba::get_by_tag($tagstring);
        if (   !is_object($tag)
            || !$tag->guid)
        {
            $tag =  new net_nemein_tag_tag_dba();
            $tag->tag = $tagstring;
            $tag->url = $url;
            if (!$tag->create())
            {
                debug_add("Failed to create tag \"{$tagstring}\": " . midcom_connection::get_error_string(), MIDCOM_LOG_WARN);
                return;
            }
        }
        $link =  new net_nemein_tag_link_dba();
        $link->tag = $tag->id;
        $link->context = $context;
        $link->value = $value;
        $link->fromGuid = $object->guid;
        $link->fromClass = get_class($object);
        $link->fromComponent = $component;

        // Carry the original object's publication date to the tag as well
        $link->metadata->published = $object->metadata->published;

        if (!$link->create())
        {
            debug_add("Failed to create tag_link \"{$tagname}\" for object {$object->guid}: " . midcom_connection::get_error_string(), MIDCOM_LOG_WARN);
        }
    }

    private static function _update_tag($tagname, $url, $object_guid)
    {
        debug_add("Updating tag {$tagname} for object {$object_guid} to URL {$url}");
        $tagstring = self::resolve_tagname($tagname);
        $tag = net_nemein_tag_tag_dba::get_by_tag($tagstring);
        if (!is_object($tag))
        {
            debug_add("Failed to update tag \"{$tagname}\" for object {$object_guid} (could not get tag object for tag {$tagstring}): " . midcom_connection::get_error_string(), MIDCOM_LOG_WARN);
            return;
        }
        $tag->url = $url;
        if (!$tag->update())
        {
            debug_add("Failed to update tag \"{$tagname}\" for object {$object_guid}: " . midcom_connection::get_error_string(), MIDCOM_LOG_WARN);
        }
    }

    private static function _remove_tag($tagname, $object_guid)
    {
        debug_add("Removing tag {$tagname} from object {$object_guid}");
        $tagstring = self::resolve_tagname($tagname);
        $context = self::resolve_context($tagname);
        $value = self::resolve_value($tagname);
        // Ponder make method in net_nemein_tag_link_dba ??
        $qb = net_nemein_tag_link_dba::new_query_builder();
        $qb->add_constraint('tag.tag', '=', $tagstring);
        $qb->add_constraint('context', '=', $context);
        $qb->add_constraint('value', '=', $value);
        $qb->add_constraint('fromGuid', '=', $object_guid);
        $links = $qb->execute();
        if (!is_array($links))
        {
            debug_add("Failed to fetch tag link(s) for tag \"{$tagstring}\" for object {$object_guid}: " . midcom_connection::get_error_string(), MIDCOM_LOG_WARN);
            return;
        }
        foreach ($links as $link)
        {
            if (!$link->delete())
            {
                debug_add("Failed to delete tag_link \"{$tagname}\" for object {$object_guid}: " . midcom_connection::get_error_string(), MIDCOM_LOG_WARN);
            }
        }
    }

    /**
     * Resolve actual tag from user-inputted tags that may have contexts or values in them
     *
     * @param string $tagname User-inputted tag that may contain a context or value
     * @return string Tag without context or value
     */
    public static function resolve_tagname($tagname)
    {
        // first get the context out
        if (strpos($tagname, ':'))
        {
            list ($context, $tag) = explode(':', $tagname, 2);
            $tagname = $tag;
        }
        // then get rid of value
        if (strpos($tagname, '='))
        {
            list ($tag, $value) = explode('=', $tagname, 2);
            $tagname = $tag;
        }
        return trim($tagname);
    }

    /**
     * Resolve value from user-inputted tags that may have machine tag values
     *
     * @param string $tagname User-inputted tag that may contain a value
     * @return string Value without tag or context
     */
    public static function resolve_value($tagname)
    {
        // first get the context out
        if (strpos($tagname, ':'))
        {
            list ($context, $tag) = explode(':', $tagname, 2);
            $tagname = $tag;
        }
        // then see if we have value
        if (strpos($tagname, '='))
        {
            list ($tag, $value) = explode('=', $tagname, 2);
            return trim($value);
        }
        return '';
    }

    /**
     * Resolve context from user-inputted tags that may contain tag and context
     *
     * @param string $tagname User-inputted tag that may contain a context
     * @return string Context without tag or empty if no context is found
     */
    public static function resolve_context($tagname)
    {
        if (strpos($tagname, ':'))
        {
            list ($context, $tag) = explode(':', $tagname, 2);
            return trim($context);
        }
        return '';
    }

    /**
     * Copy tasks of one object to another object
     */
    public function copy_tags($from, $to, $component = null)
    {
        if (   !is_object($from)
            || !is_object($to))
        {
            return false;
        }

        $tags = self::get_object_tags($from);
        return self::tag_object($to, $tags, $component);
    }

    /**
     * Gets list of tags linked to the object
     *
     * Tag names are modified to include a possible context in format
     * context:tag
     *
     * @return array list of tags and urls, tag is key, url is value (or false on failure)
     */
    public static function get_object_tags(&$object)
    {
        return self::get_tags_by_guid($object->guid);
    }

    public static function get_tags_by_guid($guid)
    {
        $tags = array();
        $link_mc = net_nemein_tag_link::new_collector('fromGuid', $guid);
        $link_mc->set_key_property('tag');
        $link_mc->add_value_property('value');
        $link_mc->add_value_property('context');
        $link_mc->add_order('tag.tag');
        $link_mc->execute();
        $links = $link_mc->list_keys();

        if (!$links)
        {
            return $tags;
        }

        $mc = net_nemein_tag_tag_dba::new_collector('metadata.deleted', false);
        $mc->add_value_property('tag');
        $mc->add_value_property('url');
        $mc->add_value_property('id');
        $mc->add_constraint('id', 'IN', array_keys($links));
        $mc->execute();
        $tag_guids = $mc->list_keys();

        if (!$tag_guids)
        {
            return $tags;
        }

        foreach ($tag_guids as $tag_guid => $value)
        {
            $tag = $mc->get_subkey($tag_guid, 'tag');
            $url = $mc->get_subkey($tag_guid, 'url');
            $context = $link_mc->get_subkey($mc->get_subkey($tag_guid, 'id'), 'context');
            $value = $link_mc->get_subkey($mc->get_subkey($tag_guid, 'id'), 'value');

            $tagname = self::tag_link2tagname($tag, $value, $context);
            $tags[$tagname] = $url;
        }
        return $tags;
    }

    public static function tag_link2tagname($tag, $value = null, $context = null)
    {
        switch (true)
        {
            /* Tag with context and value and we want contexts */
            case (   !empty($value)
                  && strlen($value) > 0
                  && !empty($context)
                  && strlen($context) > 0):
                $tagname = "{$context}:{$tag}={$value}";
                break;
            /* Tag with value (or value and context but we don't want contexts) */
            case (   !empty($value)
                  && strlen($value) > 0):
                $tagname = "{$tag}={$value}";
                break;
            /* Tag with context (no value) and we want contexts */
            case (   !empty($context)
                  && strlen($context) > 0):
                $tagname = "{$context}:{$tag}";
                break;
            /* Default case, just the tag */
            default:
                $tagname = $tag;
        }
        return $tagname;
    }


    /**
     * Gets list of tags linked to objects of a particular class
     *
     * Tag names are modified to include a possible context in format
     * context:tag
     *
     * @return array list of tags and counts, tag is key, count is value
     */
    public static function get_tags_by_class($class, $user = null)
    {
        $tags = array();
        $tags_by_id = array();

        $mc = net_nemein_tag_link_dba::new_collector('fromClass', $class);

        if (!is_null($user))
        {
            // TODO: User metadata.authors?
            $mc->add_constraint('metadata.creator', '=', $user->guid);
        }

        $links = $mc->get_values('tag');
        if (count($links) == 0)
        {
            return $tags;
        }

        foreach ($links as $tag_id)
        {
            if (!isset($tags_by_id[$tag_id]))
            {
                $tag_mc = net_nemein_tag_tag_dba::new_collector('id', $tag_id);
                $tag_mc->add_constraint('metadata.navnoentry', '=', 0);
                $tag_names = $tag_mc->get_values('tag');
                if (count($tag_names) == 0)
                {
                    // No such tag in DB
                    continue;
                }

                foreach ($tag_names as $tag_name)
                {
                    $tags_by_id[$tag_id] = $tag_name;
                }
            }

            $tagname = $tags_by_id[$tag_id];
            if (!isset($tags[$tagname]))
            {
                $tags[$tagname] = 0;
            }

            $tags[$tagname]++;
        }
        return $tags;
    }

    /**
     * Gets list of tags linked to the object arranged by context
     *
     * @return array list of contexts containing arrays of tags and urls, tag is key, url is value
     */
    public static function get_object_tags_by_contexts(&$object)
    {
        $tags = array();
        $link_mc = net_nemein_tag_link::new_collector('fromGuid', $object->guid);
        $link_mc->set_key_property('tag');
        $link_mc->add_value_property('value');
        $link_mc->add_value_property('context');
        $link_mc->add_order('context');
        $link_mc->add_order('tag.tag');
        $link_mc->execute();
        $links = $link_mc->list_keys();

        if (!$links)
        {
            return $tags;
        }

        $mc = net_nemein_tag_tag_dba::new_collector('metadata.deleted', false);
        $mc->add_value_property('tag');
        $mc->add_value_property('url');
        $mc->add_value_property('id');
        $mc->add_constraint('id', 'IN', array_keys($links));
        $mc->execute();
        $tag_guids = $mc->list_keys();

        if (!$tag_guids)
        {
            return $tags;
        }

        foreach ($tag_guids as $tag_guid => $value)
        {
            $context = $link_mc->get_subkey($tag_guid, 'context');
            if (empty($context))
            {
                $context = 0;
            }

            if (!array_key_exists($context, $tags))
            {
                $tags[$context] = array();
            }

            $tag = $mc->get_subkey($tag_guid, 'tag');
            $url = $mc->get_subkey($tag_guid, 'url');
            $value = $link_mc->get_subkey($mc->get_subkey($tag_guid, 'id'), 'value');

            $tagname = self::tag_link2tagname($tag, $value, $context);
            $tags[$context][$tagname] = $url;
        }
        return $tags;
    }

    /**
     * Reads machine tag string from content and returns it, the string is removed from content on the fly
     *
     * @param string &$content reference to content
     * @return string string of tags, empty for no tags
     */
    public static function separate_machine_tags_in_content(&$content)
    {
        $regex = '/^(.*)(tags:)\s+?(.*?)(\.?\s*)?$/si';
        if (!preg_match($regex, $content, $tag_matches))
        {
            return '';
        }
        debug_print_r('tag_matches: ', $tag_matches);
        // safety
        if (!empty($tag_matches[1]))
        {
            $content = rtrim($tag_matches[1]);
        }
        return trim($tag_matches[3]);
    }

    /**
     * Gets list of machine tags linked to the object with a context
     *
     * @return array of matching tags and values, tag is key, value is value
     */
    public static function get_object_machine_tags_in_context(&$object, $context)
    {
        $tags = array();
        $qb = net_nemein_tag_link_dba::new_query_builder();
        $qb->add_constraint('fromGuid', '=', $object->guid);
        $qb->add_constraint('context', '=', $context);
        $qb->add_constraint('value', '<>', '');
        $links = $qb->execute();
        if (!is_array($links))
        {
            return false;
        }
        foreach ($links as $link)
        {
            try
            {
                $tag = new net_nemein_tag_tag_dba($link->tag);
                $key = $tag->tag;
                $value = $link->value;

                $tags[$key] = $value;
            }
            catch (midcom_error $e)
            {
                $e->log();
            }
        }
        return $tags;
    }

    /**
     * Lists all known tags
     *
     * @return array list of tags and urls, tag is key, url is value
     */
    public static function get_tags()
    {
        $tags = array();
        $qb = net_nemein_tag_tag_dba::new_query_builder();
        $qb->add_constraint('metadata.navnoentry', '=', 0);
        $db_tags = $qb->execute();
        if (!is_array($db_tags))
        {
            return false;
        }
        foreach ($db_tags as $tag)
        {
            $tags[$tag->tag] = $tag->url;
        }
        return $tags;
    }

    /**
     * Gets all objects of given classes with given tags
     *
     * @param array of tags to search for
     * @param array of classes to search in (NOTE: you must have loaded the files that defined these classes beforehand)
     * @param string AND or OR, depending if you require all of the given tags on any of them, defaults to 'OR'
     * @return array of objects or false on critical failure
     */
    public static function get_objects_with_tags($tags, $classes, $match = 'OR')
    {
        switch (strtoupper($match))
        {
            case 'ANY':
            case 'OR':
                $match = 'OR';
                break;
            case 'ALL':
            case 'AND':
                $match = 'AND';
                break;
            default:
                // Invalid match rule
                return false;
                break;
        }
        $qb = net_nemein_tag_link_dba::new_query_builder();
        $qb->begin_group('OR');
        foreach ($classes as $class)
        {
            if (!class_exists($class))
            {
                // Invalid class
                return false;
            }
            $qb->add_constraint('fromClass', '=', $class);
        }
        $qb->end_group();
        $qb->begin_group('OR');
        foreach ($tags as $tag)
        {
            $qb->add_constraint('tag.tag', '=', $tag);
        }
        $qb->end_group();
        $qb->add_order('fromGuid', 'ASC');
        $qb->add_order('tag.tag', 'ASC');

        $links = $qb->execute();
        if (!is_array($links))
        {
            // Fatal QB error
            return false;
        }
        $link_object_map = array();
        $tag_cache = array();
        foreach ($links as $link)
        {
            if (!array_key_exists($link->fromGuid, $link_object_map))
            {
                $link_object_map[$link->fromGuid] = array
                (
                    'object' => false,
                    'links'  => array(),
                );
            }
            $map =& $link_object_map[$link->fromGuid];

            if (!array_key_exists($link->tag, $tag_cache))
            {
                try
                {
                    $tag_cache[$link->tag] = new net_nemein_tag_tag_dba($link->tag);
                }
                catch (midcom_error $e)
                {
                    $e->log();
                    continue;
                }
            }
            $tag =& $tag_cache[$link->tag];
            // PHP5-TODO: must be copy by value
            $map['links'][$tag->tag] = $link;
        }
        // Clear this reference or it will cause pain later
        unset($map);

        // For AND matches, make sure we have all the required tags.
        if ($match == 'AND')
        {
            // Filter links that do not contain all of the required tags on each object
            foreach ($link_object_map as $guid => $map)
            {
                $link_map = $link_object_map[$guid]['links'];
                foreach ($tags as $tag)
                {
                    if (   !array_key_exists($tag, $link_map)
                        || !is_object($link_map[$tag]))
                    {
                        unset($link_object_map[$guid]);
                    }
                }
            }
        }
        $return = array();

        // Get the actual objects (casted to midcom DBA if possible)
        foreach ($link_object_map as $map)
        {
            if (!$map['object'])
            {
                $link = array_pop($map['links']);
                $tmpclass = $link->fromClass;
                // Rewrite midgard_ level classes to DBA classes
                $tmpclass = preg_replace('/^midgard_/', 'midcom_db_', $tmpclass);
                if (!class_exists($tmpclass))
                {
                    // We don't have a class available, very weird indeed (rewriting may cause this but midcom has wrappers for all first class DB objects)
                    continue;
                }
                $tmpobject = new $tmpclass($link->fromGuid);
                if (!$tmpobject->guid)
                {
                    continue;
                }
                // PHP5-TODO: Must be copy-by-value
                $map['object'] = $tmpobject;
            }
            $return[] = $map['object'];
        }
        return $return;
    }

    /**
     * Parses a string into tag_array usable with tag_object
     *
     * @see net_nemein_tag_handler::tag_object()
     * @param string $from_string String to parse tags from
     * @return array Array of correct format
     */
    public static function string2tag_array($from_string)
    {
        $tag_array = array();
        // Clean all whitespace sequences to single space
        $tags_string = preg_replace('/\s+/', ' ', $from_string);
        // Parse the tags string byte by byte
        $tags = array();
        $current_tag = '';
        $quote_open = false;
        for ($i = 0; $i < (strlen($tags_string)+1); $i++)
        {
            $char = substr($tags_string, $i, 1);
            if (   (   $char == ' '
                    && !$quote_open)
                || $i == strlen($tags_string))
            {
                $tags[] = $current_tag;
                $current_tag = '';
                continue;
            }
            if ($char === $quote_open)
            {
                $quote_open = false;
                continue;
            }
            if (   $char === '"'
                || $char === "'")
            {
                $quote_open = $char;
                continue;
            }
            $current_tag .= $char;
        }
        foreach ($tags as $tag)
        {
            // Just to be sure there is not extra whitespace in beginning or end of tag
            $tag = trim($tag);
            if (empty($tag))
            {
                continue;
            }
            $tag_array[$tag] = '';
        }
        return $tag_array;
    }

    /**
     * Creates string representation of the tag array
     *
     * @param array $tags
     * @return string representation
     */
    public static function tag_array2string($tags)
    {
        $ret = '';
        foreach ($tags as $tag => $url)
        {
            if (strpos($tag, ' '))
            {
                // This tag contains whitespace, surround with quotes
                $tag = "\"{$tag}\"";
            }

            // Simply place the tags into a string
            $ret .= "{$tag} ";
        }
        return trim($ret);
    }

    /**
     * Move all objects connected to a tag to another
     *
     * @param string $from Tag to move from
     * @param string $to Tag to move to
     * @param boolean $delete Whether to delete the from tag
     * @return boolean indicating success
     */
    public static function merge_tags($from, $to, $delete = true)
    {
        $from_tag = net_nemein_tag_tag_dba::get_by_tag($from);
        if (   !$from_tag
            || !$from_tag->guid)
        {
            return false;
        }
        $to_tag = net_nemein_tag_tag_dba::get_by_tag($to);
        if (   !$to_tag
            || !$to_tag->guid)
        {
            // Create new one
            $to_tag = new net_nemein_tag_tag_dba();
            $to_tag->tag = $to;
            if (!$to_tag->create())
            {
                return false;
            }
        }

        $qb = net_nemein_tag_link_dba::new_query_builder();
        $qb->add_constraint('tag', '=', $from_tag->id);
        $tag_links = $qb->execute();
        foreach ($tag_links as $tag_link)
        {
            $tag_link->tag = $to_tag->id;
            $tag_link->update();
        }

        if ($delete)
        {
            $from_tag->delete();
        }

        return true;
    }
}
?>