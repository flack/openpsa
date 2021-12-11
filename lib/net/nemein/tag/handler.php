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
class net_nemein_tag_handler
{
    /**
     * Tags given object with the tags in the string
     *
     * Creates missing tags and tag_links, sets tag_link navorder
     * Deletes tag links from object that are not in the list provided
     *
     * @param object $object MidCOM DBA object
     * @param array $tags List of tags and urls, tag is key, url is value
     * @todo Set the link->navorder property
     */
    public static function tag_object($object, array $tags, string $component = null) : bool
    {
        if ($component === null) {
            $component = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_COMPONENT);
        }
        $existing_tags = self::get_object_tags($object);

        // Determine operations
        $add_tags = [];
        $update_tags = [];

        foreach ($tags as $tagname => $url) {
            if (empty($tagname)) {
                unset($tags[$tagname]);
                continue;
            }
            if (!array_key_exists($tagname, $existing_tags)) {
                $add_tags[$tagname] = $url;
            } elseif (!empty($url)) {
                $update_tags[$tagname] = $url;
            }
        }
        $remove_tags = array_diff_key($existing_tags, $tags);

        // Execute
        foreach (array_keys($remove_tags) as $tagname) {
            self::_remove_tag($tagname, $object->guid);
        }
        foreach ($update_tags as $tagname => $url) {
            self::_update_tag($tagname, $url, $object->guid);
        }
        foreach ($add_tags as $tagname => $url) {
            self::_create_tag($tagname, $url, $object, $component);
        }

        return true;
    }

    private static function _create_tag(string $tagname, string $url, $object, string $component)
    {
        debug_add("Adding tag \"{$tagname}\" for object {$object->guid}");
        $tagstring = self::resolve_tagname($tagname);
        $tag = net_nemein_tag_tag_dba::get_by_tag($tagstring);
        if (!$tag) {
            $tag = new net_nemein_tag_tag_dba();
            $tag->tag = $tagstring;
            $tag->url = $url;
            if (!$tag->create()) {
                debug_add("Failed to create tag \"{$tagstring}\": " . midcom_connection::get_error_string(), MIDCOM_LOG_WARN);

                return;
            }
        }
        $link = new net_nemein_tag_link_dba();
        $link->tag = $tag->id;
        $link->context = self::resolve_context($tagname);
        $link->value = self::resolve_value($tagname);
        $link->fromGuid = $object->guid;
        $link->fromClass = get_class($object);
        $link->fromComponent = $component;

        // Carry the original object's publication date to the tag as well
        $link->metadata->published = $object->metadata->published;

        if (!$link->create()) {
            debug_add("Failed to create tag_link \"{$tagname}\" for object {$object->guid}: " . midcom_connection::get_error_string(), MIDCOM_LOG_WARN);
        }
    }

    private static function _update_tag(string $tagname, string $url, string $object_guid)
    {
        debug_add("Updating tag {$tagname} for object {$object_guid} to URL {$url}");
        $tagstring = self::resolve_tagname($tagname);
        $tag = net_nemein_tag_tag_dba::get_by_tag($tagstring);
        if (!$tag) {
            debug_add("Failed to update tag \"{$tagname}\" for object {$object_guid} (could not get tag object for tag {$tagstring}): " . midcom_connection::get_error_string(), MIDCOM_LOG_WARN);
            return;
        }
        $tag->url = $url;
        if (!$tag->update()) {
            debug_add("Failed to update tag \"{$tagname}\" for object {$object_guid}: " . midcom_connection::get_error_string(), MIDCOM_LOG_WARN);
        }
    }

    private static function _remove_tag(string $tagname, string $object_guid)
    {
        debug_add("Removing tag {$tagname} from object {$object_guid}");
        $tagstring = self::resolve_tagname($tagname);
        // Ponder make method in net_nemein_tag_link_dba ??
        $qb = net_nemein_tag_link_dba::new_query_builder();
        $qb->add_constraint('tag.tag', '=', $tagstring);
        $qb->add_constraint('context', '=', self::resolve_context($tagname));
        $qb->add_constraint('value', '=', self::resolve_value($tagname));
        $qb->add_constraint('fromGuid', '=', $object_guid);

        foreach ($qb->execute() as $link) {
            if (!$link->delete()) {
                debug_add("Failed to delete tag_link \"{$tagname}\" for object {$object_guid}: " . midcom_connection::get_error_string(), MIDCOM_LOG_WARN);
            }
        }
    }

    /**
     * Resolve actual tag from user-inputted tags that may have contexts or values in them
     */
    public static function resolve_tagname(string $tagname) : string
    {
        // first get the context out
        if (str_contains($tagname, ':')) {
            $tagname = explode(':', $tagname, 2)[1];
        }
        // then get rid of value
        if (str_contains($tagname, '=')) {
            $tagname = explode('=', $tagname, 2)[0];
        }
        return trim($tagname);
    }

    /**
     * Resolve value from user-inputted tags that may have machine tag values
     */
    public static function resolve_value(string $tagname) : string
    {
        // first get the context out
        if (str_contains($tagname, ':')) {
            $tagname = explode(':', $tagname, 2)[1];
        }
        // then see if we have value
        if (str_contains($tagname, '=')) {
            return trim(explode('=', $tagname, 2)[1]);
        }
        return '';
    }

    /**
     * Resolve context from user-inputted tags that may contain tag and context
     */
    public static function resolve_context(string $tagname) : string
    {
        if (str_contains($tagname, ':')) {
            $context = explode(':', $tagname, 2)[0];
            return trim($context);
        }
        return '';
    }

    /**
     * Copy tasks of one object to another object
     */
    public function copy_tags(object $from, object $to) : bool
    {
        $tags = self::get_object_tags($from);
        return self::tag_object($to, $tags);
    }

    /**
     * Gets list of tags linked to the object
     *
     * Tag names are modified to include a possible context in format
     * context:tag
     *
     * @return array list of tags and urls, tag is key, url is value
     */
    public static function get_object_tags(object $object) : array
    {
        return self::get_tags_by_guid($object->guid);
    }

    public static function get_tags_by_guid(string $guid) : array
    {
        $tags = [];
        foreach (self::get_data_for_guid($guid) as $result) {
            $tagname = self::tag_link2tagname($result['tag'], $result['value'], $result['context']);
            $tags[$tagname] = $result['url'];
        }
        return $tags;
    }

    public static function tag_link2tagname(string $tag, $value = null, $context = null) : string
    {
        $tagname = $tag;
        if (!empty($context)) {
            $tagname = $context . ':' . $tagname;
        }
        if (!empty($value)) {
            $tagname .= '=' . $value;
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
    public static function get_tags_by_class(string $class, $user = null) : array
    {
        $tags = [];
        $tags_by_id = [];

        $mc = net_nemein_tag_link_dba::new_collector('fromClass', $class);

        if ($user !== null) {
            // TODO: User metadata.authors?
            $mc->add_constraint('metadata.creator', '=', $user->guid);
        }

        foreach ($mc->get_values('tag') as $tag_id) {
            if (!isset($tags_by_id[$tag_id])) {
                $tag_mc = net_nemein_tag_tag_dba::new_collector('id', $tag_id);
                $tag_mc->add_constraint('metadata.navnoentry', '=', 0);
                $tag_names = $tag_mc->get_values('tag');
                if (empty($tag_names)) {
                    // No such tag in DB
                    continue;
                }
                $tags_by_id[$tag_id] = array_pop($tag_names);
            }

            $tagname = $tags_by_id[$tag_id];
            if (!isset($tags[$tagname])) {
                $tags[$tagname] = 0;
            }

            $tags[$tagname]++;
        }
        return $tags;
    }

    private static function get_data_for_guid(string $guid, array $constraints = []) : array
    {
        $data = [];
        $link_mc = net_nemein_tag_link::new_collector('fromGuid', $guid);
        $link_mc->set_key_property('tag');
        $link_mc->add_value_property('value');
        $link_mc->add_value_property('context');
        foreach ($constraints as $constraint) {
            $link_mc->add_constraint(...$constraint);
        }
        $link_mc->execute();

        $mc = net_nemein_tag_tag_dba::new_collector();
        $mc->add_constraint('id', 'IN', array_keys($link_mc->list_keys()));
        $mc->add_order('tag');
        $results = $mc->get_rows(['tag', 'url', 'id']);

        foreach ($results as $result) {
            $result['context'] = $link_mc->get_subkey($result['id'], 'context');
            $result['value'] = $link_mc->get_subkey($result['id'], 'value');
            $data[] = $result;
        }
        return $data;
    }

    /**
     * Gets list of tags linked to the object arranged by context
     *
     * @return array list of contexts containing arrays of tags and urls, tag is key, url is value
     */
    public static function get_object_tags_by_contexts($object) : array
    {
        $tags = [];
        foreach (self::get_data_for_guid($object->guid) as $result) {
            $context = $result['context'] ?: 0;

            if (!array_key_exists($context, $tags)) {
                $tags[$context] = [];
            }

            $tagname = self::tag_link2tagname($result['tag'], $result['value'], $context);

            $tags[$context][$tagname] = $result['url'];
        }
        return $tags;
    }

    /**
     * Reads machine tag string from content and returns it, the string is removed from content on the fly
     *
     * @return string string of tags, empty for no tags
     */
    public static function separate_machine_tags_in_content(string &$content) : string
    {
        $regex = '/^(.*)(tags:)\s+?(.*?)(\.?\s*)?$/si';
        if (!preg_match($regex, $content, $tag_matches)) {
            return '';
        }
        debug_print_r('tag_matches: ', $tag_matches);
        // safety
        if (!empty($tag_matches[1])) {
            $content = rtrim($tag_matches[1]);
        }
        return trim($tag_matches[3]);
    }

    /**
     * Gets list of machine tags linked to the object with a context
     *
     * @return array of matching tags and values, tag is key, value is value
     */
    public static function get_object_machine_tags_in_context($object, $context) : array
    {
        $tags = [];
        $constraints = [['context', '=', $context], ['value', '<>', '']];

        foreach (self::get_data_for_guid($object->guid, $constraints) as $result) {
            $tags[$result['id']] = $result['value'];
        }
        return $tags;
    }

    /**
     * Lists all known tags
     *
     * @return array list of tags and urls, tag is key, url is value
     */
    public static function get_tags() : array
    {
        $tags = [];
        $qb = net_nemein_tag_tag_dba::new_query_builder();
        $qb->add_constraint('metadata.navnoentry', '=', 0);

        foreach ($qb->execute() as $tag) {
            $tags[$tag->tag] = $tag->url;
        }
        return $tags;
    }

    /**
     * Gets all objects of given classes with given tags
     */
    public static function get_objects_with_tags(array $tags, array $classes, string $match = 'OR', string $order = 'ASC') : ?array
    {
        $match = str_replace(['ANY', 'ALL'], ['OR', 'AND'], strtoupper($match));
        if (!in_array($match, ['AND', 'OR'], true)) {
            // Invalid match rule
            return null;
        }

        $qb = net_nemein_tag_link_dba::new_query_builder();
        $qb->add_constraint('fromClass', 'IN', $classes);
        $qb->add_constraint('tag.tag', 'IN', $tags);

        $qb->add_order('metadata.created', $order);

        $link_object_map = [];
        foreach ($qb->execute() as $link) {
            if (!array_key_exists($link->fromGuid, $link_object_map)) {
                $link_object_map[$link->fromGuid] = [];
            }

            try {
                $tag = net_nemein_tag_tag_dba::get_cached($link->tag);
                $link_object_map[$link->fromGuid][$tag->tag] = $link;
            } catch (midcom_error $e) {
                $e->log();
            }
        }

        // For AND matches, make sure we have all the required tags.
        if ($match == 'AND') {
            // Filter links that do not contain all of the required tags on each object
            foreach ($link_object_map as $guid => $map) {
                foreach ($tags as $tag) {
                    if (empty($map[$tag])) {
                        unset($link_object_map[$guid]);
                        break;
                    }
                }
            }
        }
        $return = [];

        // Get the actual objects (casted to midcom DBA)
        foreach ($link_object_map as $map) {
            $link = array_pop($map);
            $tmpclass = $link->fromClass;
            try {
                $tmpobject = new $tmpclass($link->fromGuid);
                if (!midcom::get()->dbclassloader->is_midcom_db_object($tmpobject)) {
                    $tmpobject = midcom::get()->dbfactory->convert_midgard_to_midcom($tmpobject);
                }
                $return[] = $tmpobject;
            } catch (midcom_error $e) {
                $e->log();
            }
        }
        return $return;
    }

    /**
     * Parses a string into tag_array usable with tag_object
     *
     * @see net_nemein_tag_handler::tag_object()
     * @param string $from_string String to parse tags from
     */
    public static function string2tag_array(string $from_string) : array
    {
        // Clean all whitespace sequences to single space
        $tags_string = preg_replace('/\s+/', ' ', $from_string);
        // Parse the tags string byte by byte
        $tags = [];
        $current_tag = '';
        $quote_open = false;
        $strlen = strlen($tags_string);
        for ($i = 0; $i < $strlen; $i++) {
            $char = $tags_string[$i];
            if ($char == ' ' && !$quote_open) {
                $tags[] = $current_tag;
                $current_tag = '';
                continue;
            }
            if ($char === $quote_open) {
                $quote_open = false;
                continue;
            }
            if (in_array($char, ['"', "'"], true)) {
                $quote_open = $char;
                continue;
            }
            $current_tag .= $char;
        }
        if (!empty($current_tag)) {
            $tags[] = $current_tag;
        }
        $tags = array_filter(array_map('trim', $tags));
        return array_fill_keys($tags, '');
    }

    /**
     * Creates string representation of the tag array
     */
    public static function tag_array2string(array $tags) : string
    {
        $tags = array_keys($tags);
        foreach ($tags as &$tag) {
            if (str_contains($tag, ' ')) {
                // This tag contains whitespace, surround with quotes
                $tag = "\"{$tag}\"";
            }
        }
        return implode(' ', $tags);
    }

    /**
     * Move all objects connected to a tag to another
     */
    public static function merge_tags(string $from, string $to) : bool
    {
        $from_tag = net_nemein_tag_tag_dba::get_by_tag($from);
        if (!$from_tag) {
            return false;
        }
        $to_tag = net_nemein_tag_tag_dba::get_by_tag($to);
        if (!$to_tag) {
            // Create new one
            $to_tag = new net_nemein_tag_tag_dba();
            $to_tag->tag = $to;
            if (!$to_tag->create()) {
                return false;
            }
        }

        $qb = net_nemein_tag_link_dba::new_query_builder();
        $qb->add_constraint('tag', '=', $from_tag->id);
        foreach ($qb->execute() as $tag_link) {
            $tag_link->tag = $to_tag->id;
            $tag_link->update();
        }

        return $from_tag->delete();
    }
}
