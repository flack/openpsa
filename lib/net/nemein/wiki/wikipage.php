<?php
/**
 * @package net.nemein.wiki
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: wikipage.php 25319 2010-03-18 12:44:12Z indeyets $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Wiki note helper class to be used by other components
 *
 * @package net.nemein.wiki
 */
class net_nemein_wiki_wikipage extends midcom_db_article
{
    /**
     * The topic object, cached for ACL checks
     */
    var $_topic = null;

    /**
     * The default constructor will create an empty object. Optionally, you can pass
     * an object ID or GUID to the object which will then initialize the object with
     * the corresponding DB instance.
     *
     * @param mixed $id A valid object ID or GUID, omit for an empty object.
     */
    function __construct($id = null)
    {
        parent::__construct($id);
    }

    /**
     * Overwrite the query builder getter with a version retrieving the right type.
     * We need a better solution here in DBA core actually, but it will be difficult to
     * do this as we cannot determine the current class in a polymorphic environment without
     * having a this (this call is static).
     *
     * @static
     */
    static function new_query_builder()
    {
        return $_MIDCOM->dbfactory->new_query_builder(__CLASS__);
    }

    function _on_loaded()
    {
        // Backwards compatibility
        if ($this->name == '')
        {
            $this->name = midcom_generate_urlname_from_string($this->title);
            $this->update();
        }
        return true;
    }

    function _on_creating()
    {
        if (   $this->title == ''
            || !$this->topic)
        {
            // We must have wikiword and topic at this stage
            return false;
        }

        // Check for duplicates
        $qb = net_nemein_wiki_wikipage::new_query_builder();
        $qb->add_constraint('topic', '=', $this->topic);
        $qb->add_constraint('title', '=', $this->title);
        $result = $qb->execute();
        if (count($result) > 0)
        {
            midcom_connection::set_error(MGD_ERR_OBJECT_NAME_EXISTS);
            return false;
        }

        // Generate URL-clean name
        if ($this->name != 'index')
        {
            $this->name = midcom_generate_urlname_from_string($this->title);
        }
        return true;
    }

    function _on_updating()
    {
        if ($_MIDCOM->auth->user)
        {
            // Place current user in the page authors list
            $authors = explode('|', substr($this->metadata->authors, 1, -1));
            if (!in_array($_MIDCOM->auth->user->guid, $authors))
            {
                $authors[] = $_MIDCOM->auth->user->guid;
                $this->metadata->authors = '|' . implode('|', $authors) . '|';
            }
        }
        return parent::_on_updating();
    }

    function _on_updated()
    {
        $stat = parent::_on_updated();
        $this->_update_watchers();
        $this->_update_link_cache();
        return $stat;
    }

    /**
     * Caches links in the wiki page into database for faster "what links here" queries
     */
    function _update_link_cache()
    {
        $links_in_content = $this->find_links_in_content();

        $qb = net_nemein_wiki_link_dba::new_query_builder();
        $qb->add_constraint('frompage', '=', $this->id);
        $links_in_db = $qb->execute();

        $links_matched = array();

        // Check links in DB versus links in content to see what needs to be removed
        foreach ($links_in_db as $link)
        {
            if (!array_key_exists($link->topage, $links_in_content))
            {
                // This link is not any more in content, remove
                $link->delete();
                continue;
            }

            $links_matched[$link->topage] = $link;
        }

        // Check links in content versus matched links to see what needs to be added
        foreach ($links_in_content as $wikilink => $label)
        {
            if (array_key_exists($wikilink, $links_matched))
            {
                // This is already in DB, skip
                continue;
            }

            $link = new net_nemein_wiki_link_dba();
            $link->frompage = $this->id;
            $link->topage = $wikilink;
            debug_add("Creating net_nemein_wiki_link_dba: from page #{$link->frompage}, to page: '$link->topage'");
            $link->create();
        }
    }

    function list_watchers()
    {
        $topic = new midcom_db_topic($this->topic);
        // Get list of people watching this page
        $watchers = array();
        $qb = new midgard_query_builder('midgard_parameter');
        $qb->add_constraint('domain', '=', 'net.nemein.wiki:watch');
        $qb->begin_group('OR');
            // List people watching the whole wiki
            $qb->add_constraint('parentguid', '=', $topic->guid);
            // List people watching this particular page
            $qb->add_constraint('parentguid', '=', $this->guid);
        $qb->end_group();
        $watcher_params = $qb->execute();

        foreach ($watcher_params as $parameter)
        {
            if (in_array($parameter->name, $watchers))
            {
                // We found this one already, skip
                continue;
            }

            $watchers[] = $parameter->name;
        }
        return $watchers;
    }

    function _update_watchers()
    {
        $watchers = $this->list_watchers();
        if (empty($watchers))
        {
            return;
        }

        $diff = $this->_get_diff();
        if (empty($diff))
        {
            // No sense to email empty diffs
            return;
        }

        $_MIDCOM->load_library('org.openpsa.notifications');

        // Construct the message
        $message = array();
        $user_string = $_MIDCOM->i18n->get_string('anonymous', 'net.nemein.wiki');
        if ($_MIDCOM->auth->user)
        {
            $user = $_MIDCOM->auth->user->get_storage();
            $user_string = $user->name;
        }
        // Title for long notifications
        $message['title'] = sprintf($_MIDCOM->i18n->get_string('page %s has been updated by %s', 'net.nemein.wiki'), $this->title, $user_string);
        // Content for long notifications
        $message['content']  = "{$message['title']}\n\n";

        // TODO: Get RCS diff here
        $message['content'] .= $_MIDCOM->i18n->get_string('page modifications', 'net.nemein.wiki') . ":\n";
        $message['content'] .= "\n{$diff}\n\n";

        $message['content'] .= $_MIDCOM->i18n->get_string('link to page', 'net.nemein.wiki') . ":\n";
        $message['content'] .= $_MIDCOM->permalinks->create_permalink($this->guid);

        // Content for short notifications
        $topic = new midcom_db_topic($this->topic);
        $message['abstract'] = sprintf($_MIDCOM->i18n->get_string('page %s has been updated by %s in wiki %s', 'net.nemein.wiki'), $this->title, $user_string, $topic->extra);

        debug_push_class(__CLASS__, __FUNCTION__);
        debug_add("Processing list of Wiki subscribers", MIDCOM_LOG_DEBUG);
        debug_pop();

        // Send the message out
        foreach ($watchers as $recipient)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Notifying {$recipient}...", MIDCOM_LOG_DEBUG);
            debug_pop();
            org_openpsa_notifications::notify('net.nemein.wiki:page_updated', $recipient, $message);
        }
    }

    function _get_diff($field = 'content')
    {
        $_MIDCOM->load_library('midcom.helper.xml');

        if (!class_exists('Text_Diff'))
        {
            @include_once 'Text/Diff.php';
            @include_once 'Text/Diff/Renderer.php';
            @include_once 'Text/Diff/Renderer/unified.php';
            @include_once 'Text/Diff/Renderer/inline.php';
        }

        if (!class_exists('Text_Diff'))
        {
            return '';
        }

        // Load the RCS handler
        $rcs = $_MIDCOM->get_service('rcs');
        $rcs_handler = $rcs->load_handler($this);
        if (!$rcs_handler)
        {
            return null;
        }

        // Find out what versions to diff
        $history = $rcs_handler->list_history_numeric();
        if (count($history) < 2)
        {
            return '';
        }
        $this_version = $history[0];
        $prev_version = $history[1];

        $diff_fields = $rcs_handler->get_diff($prev_version, $this_version, 'unified');

        if (!array_key_exists('diff', $diff_fields[$field]))
        {
            // No differences
            return '';
        }

        return $diff_fields[$field]['diff'];
    }

    /**
     * Abbreviation support [abbr: Abbreviation - Explanation]
     */
    function _replace_wikiwords_macro_abbr($macro_content, $fulltag, $after)
    {
        if (preg_match("/^(.*?) \- (.*)/", $macro_content, $parts))
        {
            return "<abbr title=\"{$parts[2]}\">{$parts[1]}</abbr>{$after}";
        }
        // Could not figure it out, return the tag as is
        return $fulltag;
    }

    /**
     * Photo inclusion support [photo: GUID]
     */
    function _replace_wikiwords_macro_photo($macro_content, $fulltag, $after)
    {
        $guid = trim($macro_content);
        if (!mgd_is_guid($guid))
        {
            // value is not guid
            return $fulltag;
        }

        $_MIDCOM->componentloader->load_graceful('org.routamc.photostream');
        if (!class_exists('org_routamc_photostream_photo_dba'))
        {
            // TODO: do something to explain that we can't load o.r.photos...
            return $fulltag;
        }

        $photo = new org_routamc_photostream_photo_dba($guid);
        if (   !$photo
            || !$photo->guid)
        {
            // Fallback for the conversion script
            // FIXME: Remove this when the conversion script is able to fix the link tags
            $qb = org_routamc_photostream_photo_dba::new_query_builder();

            $qb = midcom_db_parameter::new_query_builder();
            $qb->add_constraint('domain', '=', 'org.routamc.photostream');
            $qb->add_constraint('name', '=', 'net.siriux.photos:guid');
            $qb->add_constraint('value', '=', $guid);
            $photo_params = $qb->execute();
            if (count($photo_params) == 0)
            {
                return "<span class=\"missing_photo\" title=\"{$guid}\">{$fulltag}</span>{$after}";
            }
            else
            {
                $photo = new org_routamc_photostream_photo_dba($photo_params[0]->parentguid);
                if (   !$photo
                    || !$photo->guid)
                {
                    return "<span class=\"missing_photo_legacy\" title=\"{$guid}\">{$fulltag}</span>{$after}";
                }
            }
        }

        // Get the correct photo NAP object based on the GUID
        $nap = new midcom_helper_nav();
        $node = $nap->get_node($photo->node);
        if ($node[MIDCOM_NAV_COMPONENT] != 'org.routamc.photostream')
        {
            return "<span class=\"missing_photo\" title=\"{$guid}\">{$fulltag}</span>{$after}";
        }

        // Start buffering
        $oldcontext = $_MIDCOM->get_current_context();
        ob_start();
        // Load the photo
        $_MIDCOM->dynamic_load("{$node[MIDCOM_NAV_RELATIVEURL]}photo/raw/{$photo->guid}");
        // FIXME: The newlines are to avoid some CSS breakage. Problem is that Markdown adds block-level tags around this first
        $content = "\n\n" . str_replace('h1', 'h3', ob_get_contents()) . "\n\n";
        ob_end_clean();

        // Return from the DLd context into the correct context
        // FIXME: Why doesn't dynamic_load do this by itself?
        $_MIDCOM->style->enter_context($oldcontext);

        return "{$content}{$after}";
    }

    /**
     * WikiPedia term search [wiki: search terms]
     *
     * @todo Switch to InterWiki format instead
     */
    function _replace_wikiwords_macro_wiki($macro_content, $fulltag, $after)
    {
        $text = trim($macro_content);
        if (empty($text))
        {
            return $fulltag;
        }
        $target = ucfirst(strtolower(preg_replace('/[\s-,.\']+/', "_", $text)));
        $url = "http://en.wikipedia.org/wiki/{$target}";
        return "<a href=\"{$url}\" class=\"wikipedia\">{$text}</a>{$after}";
    }

    /**
     * A notice macro (will display a classed DIV)
     */
    function _replace_wikiwords_macro_note($macro_content, $fulltag, $after)
    {
        return $this->_replace_wikiwords__classed_div('note', $macro_content, $fulltag, $after);
    }

    /**
     * A tip macro (will display a classed DIV)
     */
    function _replace_wikiwords_macro_tip($macro_content, $fulltag, $after)
    {
        return $this->_replace_wikiwords__classed_div('tip', $macro_content, $fulltag, $after);
    }

    /**
     * A warning macro (will display a classed DIV)
     */
    function _replace_wikiwords_macro_warning($macro_content, $fulltag, $after)
    {
        return $this->_replace_wikiwords__classed_div('warning', $macro_content, $fulltag, $after);
    }

    /**
     * Creates a div with given CSS class(es)
     *
     * Used by the note, tip and warning macros
     */
    function _replace_wikiwords__classed_div($css_class, $macro_content, $fulltag, $after)
    {
        $text = trim($macro_content);
        return "\n<div class=\"{$css_class}\">\n{$text}\n</div>\n{$after}";
    }

    /**
     * table of contents for the current pages node
     */
    function _replace_wikiwords_macro_nodetoc($macro_content, $fulltag, $after)
    {
        $text = trim($macro_content);
        $qb = net_nemein_wiki_wikipage::new_query_builder();
        $qb->add_constraint('topic', '=', $this->topic);
        $qb->add_constraint('name', '<>', $this->name);
        $qb->add_order('title', 'ASC');
        $pages = $qb->execute();
        if (!is_array($pages))
        {
            // QB error
            return false;
        }
        $nap = new midcom_helper_nav();
        $node = $nap->get_node($this->topic);
        $ret = "\n<ul class=\"node-toc\">\n";
        if (!empty($text))
        {
            $ret .= "    \n<lh>{$text}</lh>\n";
        }
        foreach ($pages as $page)
        {
            $url = $node[MIDCOM_NAV_FULLURL] . "{$page->name}/";
            $ret .= "    <li class=\"page\"><a href=\"{$url}\">{$page->title}</a></li>\n";
        }
        $ret .= "</ul>\n";
        return $ret . $after;
    }

    /**
     * Links to other wiki pages tagged with arbitrary tags
     */
    function _replace_wikiwords_macro_tagged($macro_content, $fulltag, $after)
    {
        if (!$_MIDCOM->load_library('net.nemein.tag'))
        {
            // TODO: do something to explain that we can't load n.n.tag...
            return $fulltag;
        }
        $tags_exploded = explode(',', $macro_content);
        $tags = array();
        foreach($tags_exploded as $tagname)
        {
            if (empty($tagname))
            {
                continue;
            }
            $tag = net_nemein_tag_handler::resolve_tagname(trim($tagname));
            $tags[$tag] = $tag;
        }
        $classes = array
        (
            'net_nemein_wiki_wikipage',
            'midcom_db_article',
            'midgard_article',
        );
        $pages = net_nemein_tag_handler::get_objects_with_tags($tags, $classes, 'OR');
        if (!is_array($pages))
        {
            // Failure in tag library
            return $fulltag;
        }
        $nap = new midcom_helper_nav();
        static $node_cache = array();
        $ret = "\n<ul class=\"tagged\">\n";
        /* HACK: usort can't use even static methods so we create an "anonymous" function from code received via method */
        usort($pages, create_function('$a,$b', $this->_code_for_sort_by_title()));
        foreach ($pages as $page)
        {
            if (!isset($node_cache[$page->topic]))
            {
                $node_cache[$page->topic] = $nap->get_node($page->topic);
            }
            $node =& $node_cache[$page->topic];
            if ($node[MIDCOM_NAV_COMPONENT] !== 'net.nemein.wiki')
            {
                // We only wish to link to wiki pages
                continue;
            }
            $url = $node[MIDCOM_NAV_FULLURL] . "{$page->name}/";
            $ret .= "    <li class=\"page\"><a href=\"{$url}\">{$page->title}</a></li>\n";
        }
        $ret .= "</ul>\n";
        return $ret . $after;
    }

    /**
     * Code to sort array by values (which is object) property 'title'
     *
     * Used by $this->_replace_wikiwords_macro_tagged()
     */
    function _code_for_sort_by_title()
    {
        return <<<EOF
        \$ap = \$a->title;
        \$bp = \$b->title;
        return strnatcmp(\$ap, \$bp);
EOF;
    }

    /**
     * Method for replacing wiki syntax in the document with HTML for display purposes
     */
    function replace_wikiwords($match)
    {
        // Refactored using code from the WordPress SimpleLink plugin
        // http://warpedvisions.org/projects/simplelink
        // Since then refactored again...
        $fulltext = $match[1];
        $after = $match[2] or '';
        $wikilink = null;
        $url = null;
        $class = null;

        // See what kind of tag we have hit
        switch (true)
        {
            // Ignore markdown tags
            case (preg_match("/[\(:\[]/", $after)):
                    // TODO: should by str match (array) instead
                    return $match[0];
                break;
            // Escaped tag [!!text]
            case (preg_match("/^\!\!(.*)/", $fulltext, $parts)):
                    return "[{$parts[1]}]{$after}";
                break;
            // MediaWiki-style link [wikipage|label]
            case (preg_match("/^(.*?)\|(.*?)$/i", $fulltext, $parts)):
                    $text = $parts[2];
                    $wikilink = $parts[1];
                break;
            // Macro [something: <data>] (for example [abbr: BOFH - Bastard Operator From Hell] or [photo: <GUID>])
            case (   preg_match('/^(.*?): (.*)/', $fulltext, $macro_parts)
                  && ($method = "_replace_wikiwords_macro_{$macro_parts[1]}")
                  && method_exists($this, $method)):
                    return $this->$method($macro_parts[2], $match[0], $after);
                break;
            // MediaWiki-style link [wikipage] (no text)
            // TODO: it is possible that this wasn't originally intended to be default, but the if/elseif tree was complex and this ended up being resolved as the last else
            default:
                    $text = $fulltext;
                    $wikilink = $fulltext;
                break;
        }

        if ($wikilink)
        {
            // Don't choke on links with anchors
            $parts = explode('#', $wikilink, 2);
            $page_anchor = null;
            if (count($parts) == 2)
            {
                $wikilink = $parts[0];
                $page_anchor = "#{$parts[1]}";
            }
            $wikipage_match = $this->path_to_wikipage($wikilink);
            if (is_null($wikipage_match['wikipage']))
            {
                // No page matched, link to creation
                $folder = $wikipage_match['folder'];
                if (is_null($wikipage_match['folder']))
                {
                    $folder = $wikipage_match['latest_parent'];
                }

                if (   isset($folder[MIDCOM_NAV_OBJECT])
                    && $folder[MIDCOM_NAV_OBJECT]->can_do('midgard:create'))
                {
                    $wikilink = rawurlencode($wikilink);
                    return "<a href=\"{$folder[MIDCOM_NAV_FULLURL]}create/?wikiword={$wikipage_match['remaining_path']}\" class=\"wiki_missing\" title=\"" . $_MIDCOM->i18n->get_string('click to create', 'net.nemein.wiki') . "\">{$text}</a>{$after}";
                }
                else
                {
                    return "<span class=\"wiki_missing_nouser\" title=\"" . $_MIDCOM->i18n->get_string('login to create', 'net.nemein.wiki') . "\">{$text}</span>{$after}";
                }
            }

            $url_name = $this->generate_page_url($wikipage_match['wikipage']);

            $type = $wikipage_match['wikipage']->parameter('midcom.helper.datamanager2', 'schema_name');

            return "<a href=\"{$url_name}{$page_anchor}\"{$class} class=\"wikipage {$type}\" title=\"{$wikilink}\">{$text}</a>{$after}";
        }
        // We have no idea of what to do so just return the text
        return $fulltext;
    }

    function find_links_in_content()
    {
        // Seek wiki page links inside page content
        // TODO: Simplify
        $matches = array();
        $links = array();
        preg_match_all('/\[(.*?)\](.|)/', $this->content, $matches);
        foreach ($matches[1] as $match_key => $match)
        {
            $fulltext = $match;
            $after = $matches[2][$match_key] or '';
            // See what kind of tag we have hit
            switch (true)
            {
                // NOTE: This logic must be kept consistent with $this->replace_wikiwords()
                // Ignore markdown tags
                case (preg_match("/[\(:\[]/", $after)):
                        // TODO: should by str match (array) instead
                        continue 2;
                    break;
                // Igone escaped tag [!!text]
                case (preg_match("/^\!\!(.*)/", $fulltext, $parts)):
                        continue 2;
                    break;
                // MediaWiki-style link [wikipage|label]
                case (preg_match("/^(.*?)\|(.*?)$/i", $fulltext, $parts)):
                        $text = $parts[2];
                        $wikilink = $parts[1];
                    break;
                // Ignore macros [something: <data>] (for example [abbr: BOFH - Bastard Operator From Hell] or [photo: <GUID>])
                case (   preg_match('/^(.*?): (.*)/', $fulltext, $macro_parts)
                      && ($method = "_replace_wikiwords_macro_{$macro_parts[1]}")
                      && method_exists($this, $method)):
                        continue 2;
                    break;
                // MediaWiki-style link [wikipage] (no text)
                // TODO: it is possible that this wasn't originally intended to be default, but the if/elseif tree was complex and this ended up being resolved as the last else
                default:
                        $text = $fulltext;
                        $wikilink = $fulltext;
                    break;
            }
            // MediaWiki-style link [wikipage] (no text)
            $links[$wikilink] = $text;
        }

        return $links;
    }

    function generate_page_url($wikipage)
    {
        $nap = new midcom_helper_nav();
        $node = $nap->get_node($wikipage->topic);
        if (!$node)
        {
            return false;
        }

        if ($wikipage->name == 'index')
        {
            return $node[MIDCOM_NAV_FULLURL];
        }

        return "{$node[MIDCOM_NAV_FULLURL]}{$wikipage->name}/";
    }

    function _list_wiki_nodes($node, $prefix = '')
    {
        static $nap = null;
        if (is_null($nap))
        {
            $nap = new midcom_helper_nav();
        }

        $nodes = array();

        if ($prefix == '')
        {
            // This is the root node
            $node_identifier = '';
            $nodes['/'] = $node;
        }
        else
        {
            $node_identifier = "{$prefix}{$node[MIDCOM_NAV_NAME]}";
            $nodes[$node_identifier] = $node;
        }

        $subnodes = $nap->list_nodes($node[MIDCOM_NAV_ID]);
        foreach ($subnodes as $node_id)
        {
            $subnode = $nap->get_node($node_id);
            if ($subnode[MIDCOM_NAV_COMPONENT] != 'net.nemein.wiki')
            {
                // This is not a wiki folder, skip
                continue;
            }

            $subnode_children = $this->_list_wiki_nodes($subnode, "{$node_identifier}/");

            $nodes = array_merge($nodes, $subnode_children);
        }

        return $nodes;
    }

    /**
     * Traverse hierarchy of wiki folders or "name spaces" to figure out
     * if a page exists
     *
     * @return array containing midcom_db_topic and net_nemein_wiki_wikipage objects if found
     */
    function path_to_wikipage($path, $force_resolve_folder_tree = false, $force_as_root = false)
    {
        $matches = array
        (
            'wikipage' => null,
            'folder' => null,
            'latest_parent' => null,
            'remaining_path' => $path,
        );

        /* We store the Wiki folder hierarchy in a static array
           that is populated only once, and even then only the
           first time we encounter a namespaced wikilink */
        static $folder_tree = array();
        if (   strstr($path, '/')
            && (   count($folder_tree) == 0
                || $force_resolve_folder_tree))
        {
            $nap = new midcom_helper_nav();

            // Traverse the NAP tree upwards until we get the root-most wiki folder
            $folder = $nap->get_node($this->topic);

            $root_folder = $folder;
            $max = 100;
            while (   $folder[MIDCOM_NAV_COMPONENT] == 'net.nemein.wiki'
                   && (($parent = $nap->get_node_uplink($folder[MIDCOM_NAV_ID])) != -1)
                   && $max > 0)
            {
                $root_folder = $folder;
                if ($force_as_root)
                {
                    break;
                }
                $folder = $nap->get_node($parent);
                $max--;
            }

            $folder_tree = $this->_list_wiki_nodes($root_folder);
        }

        if (strstr($path, '/'))
        {
            // Namespaced handling

            if (substr($path, 0, 1) != '/')
            {
                // This is a relative path, expand to full path
                foreach ($folder_tree as $prefix => $folder)
                {
                    if ($folder[MIDCOM_NAV_ID] == $this->topic)
                    {
                        $path = "{$prefix}{$path}";
                        break;
                    }
                }
            }

            if (array_key_exists($path, $folder_tree))
            {
                // This is a direct link to a folder, return the index article
                $matches['folder'] = $folder_tree[$path];

                $qb = net_nemein_wiki_wikipage::new_query_builder();
                $qb->add_constraint('topic', '=', $folder_tree[$path][MIDCOM_NAV_ID]);
                $qb->add_constraint('name', '=', 'index');
                $wikipages = $qb->execute();
                if (count($wikipages) == 0)
                {
                    $matches['remaining_path'] = $folder_tree[$path][MIDCOM_NAV_NAME];
                    return $matches;
                }

                $matches['wikipage'] = $wikipages[0];
                return $matches;
            }

            // Resolve topic from path
            $directory = dirname($path);
            if (!array_key_exists($directory, $folder_tree))
            {
                // Wiki folder is missing, go to create

                // Walk path downwards to locate latest parent
                $localpath = $path;
                $matches['latest_parent'] = $folder_tree['/'];
                while (   $localpath
                       && $localpath != '/')
                {
                    $localpath = dirname($localpath);

                    if (array_key_exists($localpath, $folder_tree))
                    {
                        $matches['latest_parent'] = $folder_tree[$localpath];
                        $matches['remaining_path'] = substr($path, strlen($localpath));

                        if (substr($matches['remaining_path'], 0, 1) == '/')
                        {
                            $matches['remaining_path'] = substr($matches['remaining_path'], 1);
                        }
                        break;
                    }
                }

                return $matches;
            }

            $folder = $folder_tree[$directory];
            $matches['remaining_path'] = substr($path, strlen($directory) + 1);
        }
        else
        {
            // The linked page is in same namespace
            $nap = new midcom_helper_nav();
            $folder = $nap->get_node($this->topic);
        }

        if (empty($folder))
        {
            return null;
        }

        // Check if the wikipage exists
        $qb = net_nemein_wiki_wikipage::new_query_builder();
        $qb->add_constraint('title', '=', basename($path));
        $qb->add_constraint('topic', '=', $folder[MIDCOM_NAV_ID]);
        $wikipages = $qb->execute();
        if (count($wikipages) == 0)
        {
            // No page found, go to create
            $matches['folder'] = $folder;
            return $matches;
        }

        $matches['wikipage'] = $wikipages[0];
        return $matches;
    }
}
?>