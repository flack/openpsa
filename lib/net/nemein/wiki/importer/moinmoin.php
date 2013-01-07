<?php
/**
 * @package net.nemein.wiki
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MoinMoin importer
 *
 * @package net.nemein.wiki
 */
class net_nemein_wiki_importer_moinmoin
{
    private $_l10n = false;
    private $_schemadb = array();
    private $_datamanager = false;
    var $wiki_root = false;
    var $root_topic = false;
    var $testing = true;
    var $add_object_parameters = array();
    var $import_revisions = true;

    public function __construct($schemadb_path = 'file:/net/nemein/wiki/config/schemadb_default.inc')
    {
        $this->_schemadb['default'] = new midcom_helper_datamanager2_schema($schemadb_path, 'default');
        $this->_datamanager = new midcom_helper_datamanager2_datamanager($this->_schemadb);
        $this->_l10n = midcom::get('i18n')->get_l10n('net.nemein.wiki');
    }

    function import_file($title, $revision_path)
    {
        if (!is_readable($revision_path))
        {
            return false;
        }
        if (empty($this->root_topic->id))
        {
            return false;
        }
        if (!$this->_datamanager)
        {
            return false;
        }
        if (!$this->_l10n)
        {
            return false;
        }
        $resolver = new net_nemein_wiki_resolver($this->root_topic->id);
        // Make sure this is clean
        $this->add_parameters = array();
        $content = trim(file_get_contents($revision_path)) . "\n";
        $content = $this->moinmoin2markdown($content, $title);
        $resolved = $resolver->path_to_wikipage($title, true);
        echo "INFO: Importing '{$revision_path}' into '{$title}'<br/>\n";
        if (!empty($resolved['latest_parent']))
        {
            $to_node =& $resolved['latest_parent'];
        }
        else
        {
            $to_node =& $resolved['folder'];
        }
        $created_page = false;
        $generator = midcom::get('serviceloader')->load('midcom_core_service_urlgenerator');
        switch (true)
        {
            case (strstr($resolved['remaining_path'], '/')):
                // One or more namespaces left, find first, create it and recurse
                $paths = explode('/', $resolved['remaining_path']);
                $folder_title = array_shift($paths);
                echo "NOTICE: Creating new wiki topic '{$folder_title}' under #{$to_node[MIDCOM_NAV_ID]}<br/>\n";
                $topic = new midcom_db_topic();
                $topic->up = $to_node[MIDCOM_NAV_ID];
                $topic->extra = $folder_title;
                $topic->title = $folder_title;
                $topic->name = $generator->from_string($folder_title);
                $topic->component = 'net.nemein.wiki';
                if (!$topic->create())
                {
                    throw new midcom_error("could not create topic, error: " . midcom_connection::get_error_string());
                }
                $topic = new midcom_db_topic($topic->id);
                // Set the component
                $topic->parameter('midcom', 'component', 'net.nemein.wiki');
                // See if we have article with same title in immediate parent
                $qb = net_nemein_wiki_wikipage::new_query_builder();
                $qb->add_constraint('title', '=', $folder_title);
                $qb->add_constraint('topic', '=', $topic->up);
                $results = $qb->execute();
                if (   is_array($results)
                    && count($results) == 1)
                {
                    echo "INFO: Found page with same title in parent, moving to be index of this new topic<br/>\n";
                    $article =& $results[0];
                    $article->name = 'index';
                    $article->topic = $topic->id;
                    if (!$article->update())
                    {
                        // Could not move article, do something ?
                        echo "FAILURE: Could not move the page, errstr: ". midcom_connection::get_error_string() . "<br/>\n";
                    }
                }
                else
                {
                    $page = new net_nemein_wiki_wikipage();
                    $page->topic = $topic->id;
                    $page->name = 'index';
                    $page->title = $topic->extra;
                    $page->content = $this->_l10n->get('wiki default page content');
                    $page->author = midcom_connection::get_user();
                    if (!$page->create())
                    {
                        // Could not create index
                        $topic->delete();
                        throw new midcom_error("Could not create index for new topic, errstr: " . midcom_connection::get_error_string());
                    }
                }
                // We have created a new topic, now recurse to create the rest of the path.
                echo "INFO: New topic created with id #{$topic->id}, now recursing the import to process next levels<br/>\n";
                return $this->import_file($title, $revision_path);

            case (is_object($resolved['wikipage'])):
                // Page exists, create new revision
                echo "INFO: Updating wikipage #{$resolved['wikipage']->id}<br/>\n";
                $wikipage =& $resolved['wikipage'];
                break;
            default:
                // No more namespaces left, create the page to latest parent
                echo "INFO: Creating new wikipage '{$resolved['remaining_path']}' in topic #{$to_node[MIDCOM_NAV_ID]} <br/>\n";
                $wikipage = new net_nemein_wiki_wikipage();
                $wikipage->title = $resolved['remaining_path'];
                $wikipage->name = $generator->from_string($resolved['remaining_path']);
                $wikipage->topic = $to_node[MIDCOM_NAV_ID];
                $wikipage->author = midcom_connection::get_user();
                if (!$wikipage->create())
                {
                    echo "FAILURE: could not create wikipage object, error: " . midcom_connection::get_error_string() . "<br/>\n";
                    return false;
                }
                $wikipage = new net_nemein_wiki_wikipage($wikipage->id);
                $created_page =& $wikipage;
                $wikipage->set_parameter('midcom.helper.datamanager2', 'schema_name', 'default');
                break;
        }
        if (!$this->_datamanager->autoset_storage($wikipage))
        {
            // DM2 initialization failure
            echo "FAILURE: Could not initialize DM2<br/>\n";
            if (is_object($created_page))
            {
                // Clean up the just created page
                $created_page->delete();
            }
            return false;
        }
        $this->_datamanager->types['content']->value = $content;
        if (!$this->_datamanager->save())
        {
            // DM2 save failure
            echo "FAILURE: DM2->save() failed, errstr: " . midcom_connection::get_error_string() . "<br/>\n";
            if (is_object($created_page))
            {
                // Clean up the just created page
                $created_page->delete();
            }
            return false;
        }
        // Handle $this->add_object_parameters
        if (!empty($this->add_object_parameters))
        {
            foreach ($this->add_object_parameters as $param)
            {
                $wikipage->set_parameter($param['domain'], $param['name'], $param['value']);
            }
        }
        echo "INFO: file imported OK<br/>\n";
        return true;
    }

    function fname2title($name)
    {
        // Simple replacements
        $title = str_replace(array('_'), array(' '), $name);
        // Encoded characters
        if (preg_match_all('/\(([0-9a-f]{2,4})\)/', $title, $encoded_matches))
        {
            $seen = array();
            foreach ($encoded_matches[1] as $key => $hex)
            {
                if (isset($seen[$hex]))
                {
                    continue;
                }
                $seen[$hex] = true;
                switch (strlen($hex))
                {
                    // Single byte
                    case 2:
                        $title = str_replace($encoded_matches[0][$key], chr(hexdec($hex)), $title);
                        break;
                        // Double byte
                    case 4:
                        $hex1 = substr($hex, 0, 2);
                        $hex2 = substr($hex, 2, 2);
                        $title = str_replace($encoded_matches[0][$key], chr(hexdec($hex1)) . chr(hexdec($hex2)), $title);
                        break;
                }
            }
        }
        return $title;
    }

    function cleanup()
    {
        // PONDER: look for pages that share title with namespace topic and move them as the index of the namespace topic ?
        echo "</p><p>All done</p>\n";
        flush();
    }

    function import()
    {
        // Start working
        $dp = opendir($this->wiki_root);
        if (!$dp)
        {
            // TODO: Error reporting
            return;
        }
        while (($page = readdir($dp)) !== false)
        {
            if (preg_match('/\.+/', $page))
            {
                // Skip dotfiles
                continue;
            }
            $page_path = "{$this->wiki_root}/{$page}";
            if (!is_dir($page_path))
            {
                // Only handle directories, random files in the wiki root are not part of the content
                continue;
            }
            if (!file_exists("{$page_path}/current"))
            {
                // Current revision is not set/present, do not try import
                continue;
            }
            echo "INFO: processing dir '{$page_path}'<br/>\n";
            $current_revision = trim(file_get_contents("{$page_path}/current"));
            $dp2 = opendir("{$page_path}/revisions");
            if (!$dp2)
            {
                echo "ERROR: could not open dir '{$page_path}/revisions}'<br/>\n";
                continue;
            }
            $title = $this->fname2title($page);
            if ($this->import_revisions)
            {
                while (($revision_file = readdir($dp2)) !== false)
                {
                    if (preg_match('/\.+/', $page))
                    {
                        // Skip dotfiles
                        continue;
                    }
                    $revision_path = "{$page_path}/revisions/{$revision_file}";
                    if (!is_file($revision_path))
                    {
                        // Only files are considered
                        continue;
                    }
                    if (trim($revision_file) === $current_revision)
                    {
                        // Current revision is imported last
                        continue;
                    }
                    if ($this->import_file($title, $revision_path))
                    {
                        // Successful import of revision, remove the file to avoid double imports
                        unlink($revision_path);
                    }
                    flush();
                }
            }
            closedir($dp2);
            if ($this->import_file($title, "{$page_path}/revisions/{$current_revision}"))
            {
                // successful import of last revision, remove file to avoid double imports
                unlink("{$page_path}/revisions/{$current_revision}");
            }
            flush();
        }
        closedir($dp);
        $this->cleanup();
    }

    /*** Markdown conversion methods below this line ***/

    function moinmoin2markdown($content, $title = false)
    {
        // Make sure this is clean
        $this->add_object_parameters = array();
        // Make sure we have only unix newlines
        $content = preg_replace("/\n\r|\r\n|\r/", "\n", $content);
        // Store moinmoin ACLs
        if (preg_match('/^#acl\s+(.*)/', $content, $acl_matches))
        {
            $tmparr = array
            (
                'domain' => 'moinmoinwiki_import',
                'name' => 'acl_string',
                'value' => $acl_matches[1],
            );
            $this->add_object_parameters[] = $tmparr;
            $content = preg_replace('/^#acl\s+(.*)/', '', $content);
        }

        /*** Strip MoinMoinWiki macros ***/
        // Handle hard line break macro first
        $content = str_replace('[[BR]]', "<br/>", $content);
        $content = preg_replace("/\[\[(.*?)\]\]/m", "`MoinMoinWiki Macro: \\1`", $content);

        /*** Formatting conversion ***/
        // Handle tables
        $this->_moinmoin2markdown_handle_tables($content);
        // Handle underlined
        $content = preg_replace('/__(\w.+?)__/', "<ul>\\1</ul>", $content);
        // Handle strikethrough
        $content = preg_replace('/--(\w.+?)--/', "<del>\\1</del>", $content);
        // Handle smaller
        $content = preg_replace('/~-(\w.+?)-~/', "<small>\\1</small>", $content);
        // Handle larger
        $content = preg_replace('/~+(\w.+?)+~/', "<big>\\1</big>", $content);
        // Handle subscript
        $content = preg_replace('/,,(\w.+?),,/', "<sub>\\1</sub>", $content);
        // Handle superscript
        $content = preg_replace('/\^(\w.+?)\^/', "<sup>\\1</sup>", $content);
        // Handle strong
        $content = preg_replace("/'''([^']+?)'''/", "**\\1**", $content);
        // Handle em
        $content = preg_replace("/''([^'].+?)''/", "_\\1_", $content);
        // Handle pre block
        if (preg_match_all("/(.){{{(.+?)}}}(.)/s", $content, $pre_matches))
        {
            foreach ($pre_matches[0] as $k => $search)
            {
                $replace = htmlentities($pre_matches[2][$k], ENT_QUOTES, 'UTF-8');
                // It seems htmlentities does not encode square brackets..
                $replace = str_replace(array('[', ']'), array('&#91;', '&#93;'), $replace);
                /* Make a pre block if {{{ is preceded by newline or first character of content is one
                otherwise use inline ` -formatting */
                if (   $pre_matches[1][$k] === "\n"
                    || substr(0, 1, $pre_matches[2][$k]) === "\n")
                {
                    $replace = "{$pre_matches[1][$k]}<pre>{$replace}</pre>{$pre_matches[3][$k]}";
                }
                else
                {
                    $replace = "{$pre_matches[1][$k]}`{$replace}`{$pre_matches[3][$k]}";
                }
                $content = str_replace($search, $replace, $content);
            }
        }
        // Handle headings (h1-h5)
        for ($i=1; $i<=5; $i++)
        {
            $search = "";
            $replace = "";
            $i2 = 0;
            while ($i2 < $i)
            {
                $search .= '=';
                $replace .= '#';
                $i2++;
            }
            $content = preg_replace("/^{$search}\s+(.*?)\s+{$search}\s*$/m", "{$replace} \\1\n", $content);
        }
        // Fix lists (no space after list nominator)
        $content = preg_replace("/^(\s+(\*|-|[0-9]+))([^\s*])/m", "\\1 \\3", $content);

        /*** Links conversion ***/
        $this->_moinmoin2markdown_handle_links($content, $title);

        // Fix escaped CamelCases to just Camelcase
        $content = preg_replace("/(\w)(''''''|``)(\w)/", "\\1\\3", $content);

        return $content;
    }

    /**
     * Here the fun starts (moinmoin linking syntax is very inconsistent)
     */
    private function _moinmoin2markdown_handle_links(&$content, $title = false)
    {
        $global_search = array();
        $global_replace = array();
        // Handle bracketed internal (optionally root namespaced) links (without quotes)
        if (preg_match_all('/(?<!\\\)\[((wiki|\/?[Ss]elf)?:?)(.*?):?(\s.*?)?\]/', $content, $internal_bracket_matches))
        {
            foreach ($internal_bracket_matches[0] as $k => $search)
            {
                if (preg_match('#^.*?://#', $internal_bracket_matches[3][$k]))
                {
                    // Is external link, don't do anything to it
                    continue;
                }
                $linkword =  $this->fname2title($internal_bracket_matches[3][$k]);
                // Construct replacement
                if (!empty($internal_bracket_matches[1][$k]))
                {
                    $root = '/';
                }
                else
                {
                    $root = '';
                }
                if (!empty($internal_bracket_matches[4][$k]))
                {
                    $linktext = trim($internal_bracket_matches[4][$k]);
                    $replace = "[{$root}{$linkword}|{$linktext}]";
                }
                else
                {
                    $replace = "[{$root}{$linkword}]";
                }
                if (!isset($global_search[$search]))
                {
                    $global_search[$search] = $search;
                    $global_replace[$search] = $replace;
                }
            }
        }
        // Handle quoted internal links
        if (preg_match_all("/(?<!\\\)\[([\"'])(:?)(.*?):?\\1(\s.*?)?\]/", $content, $internal_quote_matches))
        {
            foreach ($internal_quote_matches[0] as $k => $search)
            {
                $linkword =  $this->fname2title($internal_quote_matches[3][$k]);
                // Construct replacement
                if (!empty($internal_quote_matches[2][$k]))
                {
                    $root = '/';
                }
                else
                {
                    $root = '';
                }
                if (!empty($internal_quote_matches[4][$k]))
                {
                    $linktext = trim($internal_quote_matches[4][$k]);
                    $replace = "[{$root}{$linkword}|{$linktext}]";
                }
                else
                {
                    $replace = "[{$root}{$linkword}]";
                }
                if (!isset($global_search[$search]))
                {
                    $global_search[$search] = $search;
                    $global_replace[$search] = $replace;
                }
            }
        }
        // Handle something://something -links (with possible alternate text) in brackets (not escaped)
        if (preg_match_all('/(?<!\\\)\[(.*?:\/\/.*?)(\s.*?)?\]/', $content, $external_bracket_matches))
        {
            foreach ($external_bracket_matches[0] as $k => $search)
            {
                $linkword = $external_bracket_matches[1][$k];
                // Construct replacement
                if (!empty($external_bracket_matches[2][$k]))
                {
                    $linktext = trim($external_bracket_matches[2][$k]);
                    $replace = "[{$linktext}]({$linkword})";
                }
                else
                {
                    $replace = "<{$linkword}>";
                }
                if (!isset($global_search[$search]))
                {
                    $global_search[$search] = $search;
                    $global_replace[$search] = $replace;
                }
            }
        }
        // Handle CamelCase
        if (preg_match_all('/(\s|>|^|[\'"`])([A-Z]+[a-z]+?[A-Z]+[a-z]+[^\s-]*?)(\.|\s|<|$|[\'"`])/ms', $content, $camel_matches))
        {
            foreach ($camel_matches[0] as $k => $search)
            {
                if (preg_match('/^`MoinMoinWiki/', $search))
                {
                    // Beginning of "`MoinMoinWiki Macro: xxx`
                    continue;
                }
                $word = $camel_matches[2][$k];
                if (   preg_match('/' . preg_replace('/([()*\[\]\/])/', "\\\\\\1", $word) . '`$/', $search)
                    && strstr($content, "`MoinMoinWiki Macro: {$word}`"))
                {
                    // Ending of "`MoinMoinWiki Macro: xxx`
                    continue;
                }
                if (strtoupper($search) == $search)
                {
                    // Is all caps, skip
                    continue;
                }
                // Construct replacement (always root namespaced)
                if (strstr($title, '/'))
                {
                    // current page is inside a namespace, however CamelCased links are supposed to always be in root namespace, so we add the root namespace
                    $replace = "{$camel_matches[1][$k]}[/{$word}]{$camel_matches[3][$k]}";
                }
                else
                {
                    // Not in any other namespace, so do not clutter the link with the unnecessary slash
                    $replace = "{$camel_matches[1][$k]}[{$word}]{$camel_matches[3][$k]}";
                }
                if (!isset($global_search[$search]))
                {
                    $global_search[$search] = $search;
                    $global_replace[$search] = $replace;
                }
            }
        }
        $content = str_replace($global_search, $global_replace, $content);
        // Handle inline http/https/ftp URLs
        $content = preg_replace('#(\s|>|^|(?<!=)[\'"`])((https?|ftp)://.*?)(\s|<|\.?$|\.\s|[\'"`])#m', "\\1<\\2>\\4", $content);
        // Fix links with colons right after them
        $content = preg_replace('#(\[.*?\]):#', "\\1", $content);
    }

    private function _moinmoin2markdown_handle_tables(&$content)
    {
        // Look for tables
        $tables = array();
        $table_open = false;
        $content .= "\n"; // Make sure we have ending newline
        $rows = explode("\n", $content);
        foreach ($rows as $row)
        {
            $row .= "\n";
            switch (true)
            {
                case (   preg_match('/^\s*\|\|.*?\|\|\s*$/', $row)
                      && !$table_open):
                    $current_table = $row;
                    $table_open = true;
                    break;
                case (   preg_match('/^\s*\|\|.*?\|\|\s*$/', $row)
                      && $table_open):
                    $current_table .= $row;
                    break;
                case (   preg_match('/^\s*$/', $row)
                     && $table_open):
                    $table_open = false;
                    $tables[] = trim($current_table);
                    unset($current_table);
                    break;
            }
        }
        // Replace tables
        foreach ($tables as $moinmoincode)
        {
            $rendered = $this->_moinmoin_table2markdown($moinmoincode);
            $content = str_replace($moinmoincode, $rendered, $content);
        }
    }

    private function _moinmoin_table2markdown_parserow($code)
    {
        $return = array
        (
            'contents' => array(),
            'parameters' => array(),
        );
        // Strip leading and trailing pipes
        $code = preg_replace('/^\s*\||\|\s*$/', '', $code);
        $len = strlen($code);
        $i = 0;
        $cell_open = false;
        $angle_open = false;
        while ($i < $len)
        {
            $char = substr($code, $i, 1);
            switch(true)
            {
                case (   $char === '|'
                      && !$cell_open):
                    $cell_open = true;
                    $cell_content = '';
                    break;
                case (   $char === '|'
                       && $cell_open
                       && !$angle_open):
                    $cell_open = false;
                    $params = array();
                    // BGColor
                    if (preg_match('/<#(.*?)>/', $cell_content, $bgcolor_matches))
                    {
                        $params['bgcolor'] = '#' . $bgcolor_matches[1];
                        $cell_content = str_replace($bgcolor_matches[0], '', $cell_content);
                    }
                    // Width
                    if (preg_match('/<([0-9]+%?)>/', $cell_content, $width_matches))
                    {
                        $params['width'] = $width_matches[1];
                        $cell_content = str_replace($width_matches[0], '', $cell_content);
                    }
                    // Rowspan
                    if (preg_match('/<\|([0-9]+)>/', $cell_content, $rowspan_matches))
                    {
                        $params['rowspan'] = $rowspan_matches[1];
                        $cell_content = str_replace($rowspan_matches[0], '', $cell_content);
                    }
                    // Colspan
                    if (preg_match('/<-([0-9]+)>/', $cell_content, $colspan_matches))
                    {
                        $params['colspan'] = $colspan_matches[1];
                        $cell_content = str_replace($colspan_matches[0], '', $cell_content);
                    }
                    // Left/right/center align
                    if (strstr($cell_content, '<(>'))
                    {
                        $params['align'] = 'left';
                        $cell_content = str_replace('<(>', '', $cell_content);
                    }
                    if (strstr($cell_content, '<)>'))
                    {
                        $params['align'] = 'right';
                        $cell_content = str_replace('<)>', '', $cell_content);
                    }
                    if (strstr($cell_content, '<:>'))
                    {
                         $params['align'] = 'center';
                         $cell_content = str_replace('<:>', '', $cell_content);
                    }
                    // Arbitrary parameter
                    if (preg_match("/<(.*?)=([\"'])?(.*?)\\2?>/", $cell_content, $arbitrary_matches))
                    {
                        $params[$arbitrary_matches[1]] = $arbitrary_matches[3];
                        $cell_content = str_replace($arbitrary_matches[0], '', $cell_content);
                    }
                    // Encode everything else inside <>
                    if (preg_match("/<.*?>/", $cell_content, $encode_matches))
                    {
                        $replace = htmlentities($encode_matches[0]);
                        $cell_content = str_replace($encode_matches[0], $replace, $cell_content);
                    }
                    // (at least) external links need to be anchored via HTML
                    $this->_moinmoin2markdown_handle_links($cell_content);
                    $cell_content = preg_replace('/\[(.*?)\]\((.*?)\)/', "<a href=\"\\2\">\\1</a>", $cell_content);

                    $return['parameters'][] = $params;
                    $return['contents'][] = trim($cell_content);
                    unset($cell_content);
                    break;
                /* MoinMoinWiki table syntax allows all kinds of weird things be controlled by brackets,
                they may also contain pipe which we use normally as delimiter */
                case (   $char === '<'
                      && $cell_open):
                    $cell_content .= $char;
                    $angle_open = true;
                    break;
                case (   $char === '>'
                      && $angle_open):
                    $cell_content .= $char;
                    $angle_open = false;
                    break;
                default:
                    $cell_content .= $char;
                    break;
            }
            $i++;
        }
        reset($return);
        return $return;
    }

    private function _moinmoin_table2markdown($moinmoincode)
    {
        $rows = explode("\n", $moinmoincode);
        // Find column sizes and table data
        $table_size = array();
        $table_data = array();
        $table_parameters = array();
        $renderer = 'markdown';
        $columns_count = 0;
        foreach ($rows as $k => $row)
        {
            $table_data[$k] = array();
            $table_parameters[$k] = array();
            $cells = $this->_moinmoin_table2markdown_parserow($row);
            foreach ($cells['contents'] as $k2 => $cellcontent)
            {
                if ($k2 > $columns_count)
                {
                    $columns_count = $k2;
                }
                $len = strlen($cellcontent);
                if (   !isset($table_size[$k2])
                    || $len > $table_size[$k2])
                {
                    $table_size[$k2] = $len;
                }
                $table_data[$k][$k2] = $cellcontent;
                $table_parameters[$k][$k2] = $cells['parameters'][$k2];
                if (!empty($table_parameters[$k][$k2]))
                {
                    // If we have parameters for cells we must use HTML rendering for the table
                    $renderer = 'html';
                }
            }
        }
        $columns = array();
        for ($i=0; $i <= $columns_count; $i++)
        {
            $columns[$i] = $i;
        }
        switch ($renderer)
        {
            case 'html':
                return $this->_moinmoin_table2markdown_html($table_data, $table_size, $columns, $table_parameters);
            case 'markdown':
            default:
                return $this->_moinmoin_table2markdown_markdown($table_data, $table_size, $columns);
        }
    }

    private function _moinmoin_table2markdown_html(&$table_data, &$table_size, $columns_array, &$table_parameters)
    {
        $rendered = "<table>\n    <thead>\n        <tr>\n";
        $pad = '            ';
        foreach ($columns_array as $column => $num)
        {
            if (isset($table_data[0][$num]))
            {
                $content = $table_data[0][$num];
            }
            else
            {
                $table_parameters[0][$column] = array();
                $content = '&nbsp;';
            }
            $rendered .= "{$pad}<th";
            foreach ($table_parameters[0][$column] as $key => $value)
            {
                $rendered .= " {$key}='{$value}'";
            }
            $rendered .= ">{$content}</th>\n";
        }
        $rendered .= "        <tr>\n    </thead>\n    <tbody>\n";
        unset ($table_data[0]);
        // Render rest of rows
        foreach ($table_data as $row => $cells)
        {
            $rendered .= "        <tr>\n";
            foreach ($columns_array as $column => $num)
            {
                if (isset($cells[$num]))
                {
                    $content = $cells[$num];
                }
                else
                {
                    $table_parameters[$row][$column] = array();
                    $content = '&nbsp;';
                }
                $rendered .= "{$pad}<td";
                foreach ($table_parameters[$row][$column] as $key => $value)
                {
                    $rendered .= " {$key}='{$value}'";
                }
                $rendered .= ">{$content}</td>\n";
            }
            $rendered .= "        </tr>\n";
        }
        $rendered .= "    <tbody>\n</table>\n";
        return $rendered;
    }

    private function _moinmoin_table2markdown_markdown(&$table_data, &$table_size, $columns_array)
    {
        $rendered = '';
        // First row is heading
        $heading_row1 = '';
        $heading_row2 = '';
        $columns = count($columns_array)-1;
        foreach ($columns_array as $column => $num)
        {
            if (isset($table_data[0][$num]))
            {
                $content = $table_data[0][$num];
            }
            else
            {
                $content = '&lt;empty&gt;';
            }
            $w = $table_size[$column];
            $heading_row1 .= str_pad($content, $w, ' ', STR_PAD_RIGHT);
            $heading_row2 .= str_pad('', $w, '-', STR_PAD_RIGHT);
            if ($column < $columns)
            {
                $heading_row1 .= ' | ';
                $heading_row2 .= ' | ';
            }
        }
        $rendered = "{$heading_row1}\n{$heading_row2}\n";
        unset ($table_data[0]);
        // Render rest of rows
        foreach ($table_data as $cells)
        {
            foreach ($columns_array as $column => $num)
            {
                if (isset($cells[$num]))
                {
                    $content = $cells[$num];
                }
                else
                {
                    $content = '&lt;empty&gt;';
                }
                $w = $table_size[$column];
                $rendered .= str_pad($content, $w, ' ', STR_PAD_RIGHT);
                if ($column < $columns)
                {
                    $rendered .= ' | ';
                }
            }
            $rendered .= "\n";
        }
        return $rendered;
    }
}
?>