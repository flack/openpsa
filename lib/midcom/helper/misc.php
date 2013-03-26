<?php
/**
 * @package midcom.helper
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Miscellaneous helper functions
 *
 * @package midcom.helper
 */
class midcom_helper_misc
{
    /**
     * Helper function to turn typical midcom config files into PHP arrays
     *
     * @param string $data The data to parse
     * @throws midcom_error
     * @return array The config in array format
     */
    public static function parse_config($data)
    {
        $result = eval("\$data = array({$data}\n);");
        if ($result === false)
        {
            throw new midcom_error("Failed to parse config data, see above for PHP errors.");
        }
        return $data;
    }

    /**
     * This helper function searches for a snippet either in the Filesystem
     * or in the database and returns its content or code-field, respectively.
     *
     * Prefix the snippet Path with 'file:' for retrieval of a file relative to
     * MIDCOM_ROOT; omit it to get the code field of a Snippet.
     *
     * Any error (files not found) will return null. If you want to trigger an error,
     * look for midcom_helper_misc::get_snippet_content.
     *
     * @param string $path  The URL to the snippet.
     * @return string       The content of the snippet/file.
     */
    public static function get_snippet_content_graceful($path)
    {
        static $cached_snippets = array();
        if (array_key_exists($path, $cached_snippets))
        {
            return $cached_snippets[$path];
        }

        if (substr($path, 0, 5) == 'file:')
        {
            $filename = MIDCOM_ROOT . substr($path, 5);
            if (! file_exists($filename))
            {
                //If we can't find the file in-tree, we look for out-of-tree components before giving up
                $found = false;
                $filename = substr($path, 6);
                if (preg_match('|.+?/.+?/.+?/|', $filename))
                {
                    $component_name = preg_replace('|(.+?)/(.+?)/(.+?)/.+|', '$1.$2.$3', $filename);
                    if (midcom::get('componentloader')->is_installed($component_name))
                    {
                        $filename = substr($filename, strlen($component_name));
                        $filename = midcom::get('componentloader')->path_to_snippetpath($component_name) . $filename;
                        if (file_exists($filename))
                        {
                            $found = true;
                        }
                    }
                }
                if (!$found)
                {
                    $cached_snippets[$path] = null;
                    return null;
                }
            }
            $data = file_get_contents($filename);
        }
        else if (substr($path, 0, 5) == 'conf:')
        {
            $filename = midcom::get('config')->get('midcom_config_basedir') . '/midcom' . substr($path, 5);
            if (! file_exists($filename))
            {
                $cached_snippets[$path] = null;
                return null;
            }
            $data = file_get_contents($filename);
        }
        else
        {
            $snippet = new midgard_snippet();
            try
            {
                $snippet->get_by_path($path);
            }
            catch (Exception $e)
            {
                $cached_snippets[$path] = null;
                return null;
            }
            if (isset(midcom::get('cache')->content))
            {
                midcom::get('cache')->content->register($snippet->guid);
            }
            $data = $snippet->code;
        }
        $cached_snippets[$path] = $data;
        return $data;
    }

    /**
     * This helper function searches for a snippet either in the Filesystem
     * or in the database and returns its content or code-field, respectively.
     *
     * Prefix the snippet Path with 'file:' for retrieval of a file relative to
     * MIDCOM_ROOT; omit it to get the code field of a Snippet.
     *
     * Any error (files not found) will raise a MidCOM Error. If you want a more
     * graceful behavior, look for midcom_helper_misc::get_snippet_content_graceful
     *
     * @param string $path    The URL to the snippet.
     * @return string        The content of the snippet/file.
     */
    public static function get_snippet_content($path)
    {
        $data = self::get_snippet_content_graceful($path);
        if (is_null($data))
        {
            throw new midcom_error("Could not load the contents of the snippet {$path}: Snippet does not exist.");
        }
        return $data;
    }

    /**
     * PHP-level implementation of the Midgard Preparser language
     * construct mgd_include_snippet. Same semantics, but probably a little bit
     * slower.
     *
     * This function is there as a backup in case you are not running within the
     * Midgard Parser; it will run the snippet code through preparse manually.
     *
     * @param string $path    The path of the snippet that should be included.
     * @return boolean Returns false if the snippet could not be loaded or true, if it was evaluated successfully.
     */
    public static function include_snippet_php($path)
    {
        $code = self::get_snippet_content_graceful($path);
        if (empty($code))
        {
            debug_add("Could not find snippet {$path}: ", MIDCOM_LOG_ERROR);
            return false;
        }
        debug_add("Evaluating snippet {$path}.");
        eval ('?>' . self::preparse($code));
        return true;
    }

    /**
     * Preparse a string to handle element inclusion and variable
     *
     * @param string $code The unprocessed code
     * @return string The processed code
     */
    public static function preparse($code)
    {
        // Get style elements
        $code = preg_replace_callback("/<\\(([a-zA-Z0-9 _-]+)\\)>/", array('midcom_helper_misc', 'include_element'), $code);
        // Echo variables
        $code = preg_replace_callback("%&\(([^)]*)\);%i", array('midcom_helper_formatter', 'convert_to_php'), $code);
        return $code;
    }

    /**
     * Include a theme element
     */
    public static function include_element($name)
    {
        if (is_array($name))
        {
            $element = $name[1];
        }
        else
        {
            $element = $name;
        }

        switch ($element)
        {
            case 'title':
                return midcom::get('config')->get('midcom_site_title');
            case 'content':
                return '<(content)>';
            default:
                $value = self::get_element_content($element);

                if (empty($value))
                {
                    if ($element == 'ROOT')
                    {
                        /* If we don't have a ROOT element, go to content directly. style-init or style-finish
                         * can load the page style (under mgd1, this might also be done with the
                                 * midgard style engine as well)
                        */
                        return '<(content)>';
                    }
                    return '';
                }
                return preg_replace_callback("/<\\(([a-zA-Z0-9 _-]+)\\)>/", array('midcom_helper_misc', 'include_element'), $value);
        }
    }

    /**
     * Helper function for finding MIME type image for a document
     *
     * Used in midcom.admin.styleeditor, midcom.helper.imagepopup, midgard.admin.asgard and org.openpsa.documents.
     *
     * @param string $mimetype  Document MIME type
     * @return string    Path to the icon
     */
    public static function get_mime_icon($mimetype, $fallback = '')
    {
        $mime_fspath = MIDCOM_STATIC_ROOT . '/stock-icons/mime';
        $mime_urlpath = MIDCOM_STATIC_URL . '/stock-icons/mime';
        $mimetype_filename = str_replace('/', '-', $mimetype);
        if (!is_readable($mime_fspath))
        {
            debug_add("Couldn't read directory {$mime_fspath}", MIDCOM_LOG_WARN);
        }

        $check_files = Array();
        switch ($mimetype_filename)
        {
            case 'application-x-zip-compressed':
                $check_files[] = "gnome-application-zip.png";
                break;
            default:
                $check_files[] = "{$mimetype_filename}.png";
                $check_files[] = "gnome-{$mimetype_filename}.png";
                break;
        }

        // Default icon if there is none for the MIME type
        $check_files[] = 'gnome-unknown.png';
        //TODO: handle other than PNG files ?

        //Return first match
        foreach ($check_files as $filename)
        {
            if (is_readable("{$mime_fspath}/{$filename}"))
            {
                return "{$mime_urlpath}/{$filename}";
            }
        }

        return $fallback;
    }

    /**
     * Helper function, determines the mime-type of the specified file.
     *
     * The call uses the "file" utility which must be present for this type to work.
     *
     * @param string $filename The file to scan
     * @return string The autodetected mime-type
     */
    public static function get_mimetype($filename)
    {
        return exec(midcom::get('config')->get('utility_file') . " -ib {$filename} 2>/dev/null");
    }

    /**
     * Helper function for pretty printing file sizes
     * Original from http://www.theukwebdesigncompany.com/articles/php-file-manager.php
     *
     * @param int $size  File size in bytes
     * @return string    Prettified file size
     */
    public static function filesize_to_string($size)
    {
        if ($size > 1048576)
        {
            // More than a meg
            return $return_size = sprintf("%01.2f", $size / 1048576) . " Mb";
        }
        else if ($size > 1024)
        {
            // More than a kilo
            return $return_size = sprintf("%01.2f", $size / 1024) . " Kb";
        }
        else
        {
            return $return_size = $size." Bytes";
        }
    }

    /**
     * Fixes newline etc encoding issues in serialized data
     *
     * @param string $data The data to fix.
     * @return string $data with serializations fixed.
     */
    public static function fix_serialization($data = null)
    {
        //Skip on empty data
        if (empty($data))
        {
            return $data;
        }

        $preg='/s:([0-9]+):"(.*?)";/ms';
        preg_match_all($preg, $data, $matches);
        $cache = array();

        foreach ($matches[0] as $k => $origFullStr)
        {
            $origLen = $matches[1][$k];
            $origStr = $matches[2][$k];
            $newLen = strlen($origStr);
            if ($newLen != $origLen)
            {
                $newFullStr = "s:$newLen:\"$origStr\";";
                //For performance we cache information on which strings have already been replaced
                if (!array_key_exists($origFullStr, $cache))
                {
                    $data = str_replace($origFullStr, $newFullStr, $data);
                    $cache[$origFullStr] = true;
                }
            }
        }

        return $data;
    }

    /**
     * This helper function returns the first instance of a given component on
     * the MidCOM site.
     *
     * @return array NAP array of the first component instance found
     */
    public static function find_node_by_component($component, $node_id = null, $nap = null)
    {
        static $cache = array();

        $cache_node = $node_id;
        if (is_null($cache_node))
        {
            $cache_node = 0;
        }

        if (!isset($cache[$cache_node]))
        {
            $cache[$cache_node] = array();
        }

        if (array_key_exists($component, $cache[$cache_node]))
        {
            return $cache[$cache_node][$component];
        }

        if (is_null($nap))
        {
            $nap = new midcom_helper_nav();
        }

        if (is_null($node_id))
        {
            $node_id = $nap->get_root_node();

            $root_node = $nap->get_node($node_id);
            if ($root_node[MIDCOM_NAV_COMPONENT] == $component)
            {
                $cache[$cache_node][$component] = $root_node;
                return $root_node;
            }
        }

        // Otherwise, go with QB
        $qb = midcom_db_topic::new_query_builder();
        $qb->add_constraint('component', '=', $component);
        $qb->add_constraint('name', '<>', '');
        $qb->add_constraint('up', 'INTREE', $node_id);
        $qb->set_limit(1);
        $topics = $qb->execute();

        if (count($topics) == 0)
        {
            $cache[$cache_node][$component] = null;
            return null;
        }

        $node = $nap->get_node($topics[0]->id);
        $cache[$cache_node][$component] = $node;

        return $node;
    }
    /**
     * Get the content of the element by the passed element name.
     * Tries to resolve path according to theme-name & page
     *
     * @param string $element_name
     * @param string $theme_root
     */
    public static function get_element_content($element_name , $theme_root = OPENPSA2_THEME_ROOT)
    {
        $theme = midcom::get('config')->get('theme');
        $path_array = explode('/' , $theme);
        $theme_array = array_reverse($path_array);
        //
        //get the page if there is one
        $page = midcom_connection::get('self');
    
        $content = false;
    
        //check if we have elements for the sub-styles
        foreach ($theme_array as $sub_style)
        {
            $theme_path = '';
            foreach ($path_array as $path_part)
            {
                $theme_path .= '/' . $path_part;
            }
            //check possible theme and page element
            $filename = $theme_root . $theme_path .  "/style/{$element_name}.php";
            $filename_page = $theme_root . $theme_path .  "/style{$page}{$element_name}.php";
    
            if (file_exists($filename_page))
            {
                $content = file_get_contents($filename_page);
                return $content;
            }
            elseif (file_exists($filename))
            {
                $content = file_get_contents($filename);
                return $content;
            }
            //remove last theme part
            array_pop($path_array);
        }
        return $content;
    }
}
?>
