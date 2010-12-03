<?php
/**
 * @package net.nehmer.static
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * @package net.nehmer.static
 */
class net_nehmer_static_import_forrest_folder
{
    var $name = '';
    var $title = '';
    var $has_index = false;
    var $component = 'net.nehmer.static';
    var $folders = array();
    var $files = array();
}

/**
 * @package net.nehmer.static
 */
class net_nehmer_static_import_forrest_file
{
    var $name = '';
    var $title = '';
    var $content = '';
}

/**
 * @package net.nehmer.static
 */
class net_nehmer_static_import_forrest
{
    var $site_tags = array();

    function parse_sitexml($path)
    {
        $forrest_data = file_get_contents($path);
        $forrest_parser = xml_parser_create();
        xml_parse_into_struct($forrest_parser, $forrest_data, $forrest_values );

        $site_tags = array();
        $site_tag = array();
        $latest_level = 0;
        foreach ($forrest_values as $element)
        {
            if ($latest_level > $element['level'])
            {
                // We're in new tag, add old one to site tags and clear it
                $old_site_tag = $site_tag;
                $site_tags[] = $old_site_tag;

                $new_site_tag = array();
                foreach ($old_site_tag as $level => $value)
                {
                    if ($level > $element['level'])
                    {
                        // Clear this one
                        continue;
                    }

                    $new_site_tag[$level] = $value;
                }
                $site_tag = $new_site_tag;
                $latest_level = $element['level'];
            }
            else
            {
                $latest_level = $element['level'];

                // Set values
                $site_tag[$element['level']] = array
                (
                    'tag' => strtolower($element['tag'])
                );

                if (array_key_exists('attributes', $element))
                {
                    if (array_key_exists('HREF', $element['attributes']))
                    {
                        $site_tag[$element['level']]['href'] = $element['attributes']['HREF'];
                    }
                }
            }
        }

        foreach ($site_tags as $tag_structure)
        {
            $tag_string = '';
            $tag_href = '';

            foreach ($tag_structure as $level => $values)
            {
                if (array_key_exists('href', $values))
                {
                    $tag_href .= $values['href'];
                }

                if ($level == 1)
                {
                    $tag_string .= "{$values['tag']}:";
                }
                elseif ($level < count($tag_structure))
                {
                    if ($values['tag'] == 'external-refs')
                    {
                        // This is a special case
                        $tag_string = 'ext:';
                    }
                    else
                    {
                        $tag_string .= "{$values['tag']}/";
                    }

                    // Sometimes the elements are called directly too
                    if ($tag_href != '')
                    {
                        $this->site_tags["site:{$values['tag']}"] = $tag_href;
                    }
                }
                else
                {
                    $tag_string .= $values['tag'];

                    // Sometimes the elements are called directly too
                    if ($tag_href != '')
                    {
                        $this->site_tags["site:{$values['tag']}"] = $tag_href;
                    }
                }

                if ($tag_href != '')
                {
                    $this->site_tags[$tag_string] = $tag_href;
                }
            }
        }

        xml_parser_free($forrest_parser);
    }

    function parse_file($path)
    {
        $file = new net_nehmer_static_import_forrest_file();
        $file->name = str_replace('.xml', '', basename($path));

        $forrest_data = file_get_contents($path);

        $encoding = mb_detect_encoding($forrest_data);
        if ($encoding != 'UTF-8')
        {
            $forrest_data = iconv($encoding, 'UTF-8', $forrest_data);
        }

        if (!preg_match_all('%<header>.*?(<title>(.*?)</title>).*?</header>.*?(<body>(.*?)</body>)%msi', $forrest_data, $matches))
        {
            // Title/body not found
            return null;
        }
        else
        {
            $file->title = $matches[2][0];
        }
        $file->content = $matches[4][0];
        $file->content = preg_replace('%\s*<section(.*?)>.*?<title.*?>(.*?)</title>(.*?)</section>%msi', "\n\n<div class=\"section\" \\1>\n    <h2>\\2</h2>\n\\3</div>\n", $file->content);
        $file->content = str_replace('<warning>', '<div class="warning">', $file->content);
        $file->content = str_replace('</warning>', '</div>', $file->content);
        $file->content = str_replace('title>', 'h2>', $file->content);
        if (preg_match_all("/<figure.*?src=([\"'])(.*?)\\1.*?\/>/msi", $file->content, $figure_matches))
        {
            foreach ($figure_matches[0] as $figure_key => $figure_str)
            {
                // rewrite the tag in parts locally
                $figure_str_new = str_replace('<figure', '<img', $figure_str);
                $figure_uri =& $figure_matches[2][$figure_key];
                if (!preg_match('%^(https?://|/)%', $figure_uri))
                {
                    // Figure URI is relative to Forrest path (kind of makes sense, for Forrest), prepend a slash
                    $figure_uri_new = '/forrest-' . $figure_uri;
                    $figure_str_new = str_replace($figure_uri, $figure_uri_new, $figure_str_new);
                }

                // replace the original tag with the rewritten one
                $file->content = str_replace($figure_str, $figure_str_new, $file->content);
            }
        }

        // Handle the site.xml links
        preg_match_all("%<a.*?href=(['\"])((site|ext):.*?)\\1.*?>%", $file->content, $site_links);
        foreach ($site_links[2] as $link)
        {
            //explode, map, replace
            if (array_key_exists($link, $this->site_tags))
            {
                $file->content = str_replace($link, $this->site_tags[$link], $file->content);
            }
            else
            {
                echo "Link {$link} not found from site.xml, not replacing<br />\n";
            }
        }

        return $file;
    }

    function list_files($path)
    {
        $files = array();
        $directory = dir($path);

        $folder = new net_nehmer_static_import_forrest_folder();
        $folder->name = basename($path);
        $folder->title = ucfirst(basename($path));

        $index = false;

        if (file_exists("{$path}/site.xml"))
        {
            // This is the "site link mapping file"
            $this->parse_sitexml("{$path}/site.xml");
        }

        while (false !== ($entry = $directory->read()))
        {
            if (substr($entry, 0, 1) == '.')
            {
                // Ignore dotfiles
                continue;
            }

            if (is_dir("{$path}/{$entry}"))
            {
                // Recurse deeper
                $folder->folders[] = $this->list_files("{$path}/{$entry}");
            }
            else
            {
                $path_parts = pathinfo($entry);

                if ($path_parts['basename'] == 'site.xml')
                {
                    continue;
                }

                if ($path_parts['basename'] == 'index.xml')
                {
                    $folder->has_index = true;
                }

                if ($path_parts['extension'] == 'xml')
                {
                    $file = $this->parse_file("{$path}/{$entry}");
                    if (!is_null($file))
                    {
                        $folder->files[] = $file;
                    }
                }
            }
        }

        $directory->close();

        return $folder;
    }

    function import_folder($folder, $parent_id)
    {
        $qb = midcom_db_topic::new_query_builder();
        $qb->add_constraint('up', '=', (int) $parent_id);
        $qb->add_constraint('name', '=', $folder->name);
        $existing = $qb->execute();
        if (   count($existing) > 0
            && $existing[0]->up == $parent_id)
        {
            $topic = $existing[0];
            echo "Using existing topic {$topic->name} (#{$topic->id}) from #{$topic->up}\n";
        }
        else
        {
            $topic = new midcom_db_topic();
            $topic->up = $parent_id;
            $topic->name = $folder->name;
            if (!$topic->create())
            {
                echo "Failed to create folder {$folder->name}: " . midcom_connection::get_error_string() . "\n";
                return false;
            }
            echo "Created folder {$topic->name} (#{$topic->id}) under #{$topic->up}\n";
        }

        $topic->extra = $folder->title;
        $topic->update();

        $topic->parameter('midcom', 'component', $folder->component);

        if ($folder->component == 'net.nehmer.static')
        {
            if (!$folder->has_index)
            {
                $topic->parameter('net.nehmer.static', 'autoindex', 1);
            }
            else
            {
                $topic->parameter('net.nehmer.static', 'autoindex', '');
            }
        }

        foreach ($folder->files as $file)
        {
            $this->import_file($file, $topic->id);
        }

        foreach ($folder->folders as $subfolder)
        {
            $this->import_folder($subfolder, $topic->id);
        }

        return true;
    }

    function import_file($file, $parent_id)
    {
        $qb = midcom_db_article::new_query_builder();
        $qb->add_constraint('topic', '=', $parent_id);
        $qb->add_constraint('name', '=', $file->name);
        $existing = $qb->execute();
        if (   count($existing) > 0
            && $existing[0]->topic == $parent_id)
        {
            $article = $existing[0];
            echo "Using existing article {$article->name} (#{$article->id}) from #{$article->topic}\n";
        }
        else
        {
            $article = new midcom_db_article();
            $article->topic = $parent_id;
            $article->name = $file->name;
            if (!$article->create())
            {
                echo "Failed to create article {$article->name}: " . midcom_connection::get_error_string() . "\n";
                return false;
            }
            echo "Created article {$article->name} (#{$article->id}) under #{$article->topic}\n";
        }

        $article->title = $file->title;
        $article->content = $file->content;
        return $article->update();
    }
}
?>
