<?php
/**
 * @package net.nemein.wiki
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Wiki markup parser
 *
 * @package net.nemein.wiki
 */
class net_nemein_wiki_parser
{
    use midcom_baseclasses_components_base;

    private net_nemein_wiki_wikipage $_page;

    public function __construct(net_nemein_wiki_wikipage $page)
    {
        $this->_component = 'net.nemein.wiki';
        $this->_page = $page;
    }

    public function get_html() : string
    {
        $parsedown = new Parsedown();
        return $parsedown->text($this->get_markdown($this->_page->content));
    }

    public function get_markdown(string $input) : string
    {
        return preg_replace_callback($this->_config->get('wikilink_regexp'), $this->replace_wikiwords(...), $input);
    }

    /**
     * Abbreviation support [abbr: Abbreviation - Explanation]
     */
    private function _run_macro_abbr(string $macro_content, string $fulltag, string $after) : string
    {
        if (preg_match("/^(.*?) - (.*)/", $macro_content, $parts)) {
            return "<abbr title=\"{$parts[2]}\">{$parts[1]}</abbr>{$after}";
        }
        // Could not figure it out, return the tag as is
        return $fulltag;
    }

    /**
     * Photo inclusion support [photo: GUID]
     */
    private function _run_macro_photo(string $macro_content, string $fulltag, string $after) : string
    {
        $guid = trim($macro_content);
        if (!mgd_is_guid($guid)) {
            // value is not guid
            return $fulltag;
        }

        try {
            $attachment = new midcom_db_attachment($guid);
        } catch (midcom_error $e) {
            $e->log();
            return "<span class=\"missing_photo\" title=\"{$guid}\">{$fulltag}</span>{$after}";
        }

        return '<img src="' . midcom_db_attachment::get_url($attachment) . '" class="wiki_photo">' . $after;
    }

    /**
     * WikiPedia term search [wiki: search terms]
     *
     * @todo Switch to InterWiki format instead
     */
    private function _run_macro_wiki(string $macro_content, string $fulltag, string $after) : string
    {
        $text = trim($macro_content);
        if (empty($text)) {
            return $fulltag;
        }
        $target = ucfirst(strtolower(preg_replace('/[\s\-,.\']+/', "_", $text)));
        $url = "https://en.wikipedia.org/wiki/{$target}";
        return "<a href=\"{$url}\" class=\"wikipedia\">{$text}</a>{$after}";
    }

    /**
     * A notice macro (will display a classed DIV)
     */
    private function _run_macro_note(string $macro_content, string $fulltag, string $after) : string
    {
        return $this->_run__classed_div('note', $macro_content, $fulltag, $after);
    }

    /**
     * A tip macro (will display a classed DIV)
     */
    private function _run_macro_tip(string $macro_content, string $fulltag, string $after) : string
    {
        return $this->_run__classed_div('tip', $macro_content, $fulltag, $after);
    }

    /**
     * A warning macro (will display a classed DIV)
     */
    private function _run_macro_warning(string $macro_content, string $fulltag, string $after) : string
    {
        return $this->_run__classed_div('warning', $macro_content, $fulltag, $after);
    }

    /**
     * Creates a div with given CSS class(es)
     *
     * Used by the note, tip and warning macros
     */
    private function _run__classed_div(string $css_class, string $macro_content, string $fulltag, string $after) : string
    {
        $text = trim($macro_content);
        return "\n<div class=\"{$css_class}\">\n{$text}\n</div>\n{$after}";
    }

    /**
     * table of contents for the current pages node
     */
    private function _run_macro_nodetoc(string $macro_content, string $fulltag, string $after) : string
    {
        $text = trim($macro_content);
        $qb = net_nemein_wiki_wikipage::new_query_builder();
        $qb->add_constraint('topic', '=', $this->_page->topic);
        $qb->add_constraint('name', '<>', $this->_page->name);
        $qb->add_order('title', 'ASC');

        $ret = '';
        if (!empty($text)) {
            $ret .= "\n<h3 class=\"node-toc-headline\">{$text}</h3>\n";
        }
        $nap = new midcom_helper_nav();
        $node = $nap->get_node($this->_page->topic);
        $ret .= "\n<ul class=\"node-toc\">\n";
        foreach ($qb->execute() as $page) {
            $url = $node[MIDCOM_NAV_ABSOLUTEURL] . "{$page->name}/";
            $ret .= "    <li class=\"page\"><a href=\"{$url}\">{$page->title}</a></li>\n";
        }
        $ret .= "</ul>\n";
        return $ret . $after;
    }

    /**
     * Links to other wiki pages tagged with arbitrary tags
     */
    private function _run_macro_tagged(string $macro_content, string $fulltag, string $after) : string
    {
        $tags_exploded = explode(',', $macro_content);
        $tags = [];
        foreach (array_filter($tags_exploded) as $tagname) {
            $tags[] = net_nemein_tag_handler::resolve_tagname(trim($tagname));
        }
        $classes = [
            net_nemein_wiki_wikipage::class,
            midcom_db_article::class,
            'midgard_article',
        ];
        $pages = net_nemein_tag_handler::get_objects_with_tags($tags, $classes, 'OR');
        if (!is_array($pages)) {
            // Failure in tag library
            return $fulltag;
        }
        $nap = new midcom_helper_nav();
        $ret = "\n<ul class=\"tagged\">\n";

        usort($pages, $this->_code_sort_by_title(...));
        foreach ($pages as $page) {
            $node = $nap->get_node($page->topic);
            if ($node[MIDCOM_NAV_COMPONENT] !== 'net.nemein.wiki') {
                // We only wish to link to wiki pages
                continue;
            }
            $url = $node[MIDCOM_NAV_ABSOLUTEURL] . "{$page->name}/";
            $ret .= "    <li class=\"page\"><a href=\"{$url}\">{$page->title}</a></li>\n";
        }
        $ret .= "</ul>\n";
        return $ret . $after;
    }

    /**
     * Code to sort array by values (which is object) property 'title'
     *
     * Used by $this->_run_macro_tagged()
     */
    private function _code_sort_by_title($a, $b) : int
    {
        return strnatcmp($a->title, $b->title);
    }

    /**
     * Replace wiki syntax in the document with HTML for display purposes
     */
    private function replace_wikiwords(array $match) : string
    {
        // Refactored using code from the WordPress SimpleLink plugin
        // http://warpedvisions.org/projects/simplelink
        // Since then refactored again...
        $fulltext = $match[1];
        $after = $match[2] ?: '';

        // See what kind of tag we have hit
        switch (true) {
            // Ignore markdown tags
            case (preg_match("/[\(:\[]/", $after)):
                // TODO: should by str match (array) instead
                return $match[0];

            // Escaped tag [!!text]
            case (preg_match("/^!!(.*)/", $fulltext, $parts)):
                return "[{$parts[1]}]{$after}";

            // MediaWiki-style link [wikipage|label]
            case (preg_match("/^(.*?)\|(.*?)$/i", $fulltext, $parts)):
                return $this->render_link($parts[1], $parts[2]) . $after;

            // Macro [something: <data>] (for example [abbr: BOFH - Bastard Operator From Hell] or [photo: <GUID>])
            case (   preg_match('/^(.*?): (.*)/', $fulltext, $macro_parts)
                  && method_exists($this, "_run_macro_{$macro_parts[1]}")):
                $method = "_run_macro_{$macro_parts[1]}";
                return $this->$method($macro_parts[2], $match[0], $after);

            // MediaWiki-style link [wikipage] (no text)
            // TODO: it is possible that this wasn't originally intended to be default, but the if/elseif tree was complex and this ended up being resolved as the last else
            default:
                return $this->render_link($fulltext, $fulltext) . $after;
        }
    }

    public function render_link(string $wikilink, ?string $text = null) : string
    {
        $text ??= $wikilink;
        // Don't choke on links with anchors
        $parts = explode('#', $wikilink, 2);
        $page_anchor = null;
        if (count($parts) == 2) {
            $wikilink = $parts[0];
            $page_anchor = "#{$parts[1]}";
        }
        $resolver = new net_nemein_wiki_resolver($this->_page->topic);
        $wikipage_match = $resolver->path_to_wikipage($wikilink);
        if ($wikipage_match['wikipage'] === null) {
            // No page matched, link to creation
            $folder = $wikipage_match['folder'] ?? $wikipage_match['latest_parent'];

            if (   isset($folder[MIDCOM_NAV_OBJECT])
                && $folder[MIDCOM_NAV_OBJECT]->can_do('midgard:create')) {
                $workflow = $this->get_workflow('datamanager');
                return "<a href=\"{$folder[MIDCOM_NAV_ABSOLUTEURL]}create/?wikiword={$wikipage_match['remaining_path']}\" " . $workflow->render_attributes() . " class=\"wiki_missing\" title=\"" . $this->_l10n->get('click to create') . "\">{$text}</a>";
            }
            return "<span class=\"wiki_missing_nouser\" title=\"" . $this->_l10n->get('login to create') . "\">{$text}</span>";
        }

        $url_name = $resolver->generate_page_url($wikipage_match['wikipage']);

        $type = $wikipage_match['wikipage']->get_parameter('midcom.helper.datamanager2', 'schema_name');
        $type .= ($wikipage_match['wikipage']->can_do('midgard:read')) ? '' : ' access-denied';

        return "<a href=\"{$url_name}{$page_anchor}\" class=\"wikipage {$type}\" title=\"{$wikilink}\">{$text}</a>";
    }

    public function find_links_in_content() : array
    {
        // Seek wiki page links inside page content
        $matches = [];
        $links = [];
        preg_match_all($this->_config->get('wikilink_regexp'), $this->_page->content, $matches);
        foreach ($matches[1] as $match_key => $match) {
            $fulltext = $match;
            $after = $matches[2][$match_key] ?: '';

            // See what kind of tag we have hit
            // NOTE: This logic must be kept consistent with $this->replace_wikiwords()
            // Ignore markdown tags and escaped tag [!!text]
            if (!preg_match("/[\(:\[]/", $after) && !preg_match("/^!!(.*)/", $fulltext)) {
                // MediaWiki-style link [wikipage|label]
                if (preg_match("/^(.*?)\|(.*?)$/i", $fulltext, $parts)) {
                    $links[$parts[1]] = $parts[2];
                }
                // Ignore macros [something: <data>] (for example [abbr: BOFH - Bastard Operator From Hell] or [photo: <GUID>])
                elseif (   !preg_match('/^(.*?): (.*)/', $fulltext, $macro_parts)
                        || !method_exists($this, "_run_macro_{$macro_parts[1]}")) {
                    // MediaWiki-style link [wikipage] (no text)
                    $links[$fulltext] = $fulltext;
                }
            }
        }

        return $links;
    }
}
