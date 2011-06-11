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
     * Pure-PHP implementation of Midgard1 APIs required by OpenPSA that are not present in Midgard2.
     */
    private static $_filters = array
    (
       'h' => 'html',
       'H' => 'html',
       'p' => 'php',
       'u' => 'rawurlencode',
       'f' => 'nl2br',
       's' => 'unmodified',
    );


    /**
     * Register PHP function as string formatter to the Midgard formatting engine.
     * @see http://www.midgard-project.org/documentation/reference-other-mgd_register_filter/
     */
    public static function register_filter($name, $function)
    {
        self::$_filters["x{$name}"] = $function;
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
            $_MIDCOM->cache->content->register($snippet->guid);
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
     * @see mgd_preparse
     */
    public static function preparse($code)
    {
        // Get style elements
        $code = preg_replace_callback("/<\\(([a-zA-Z0-9 _-]+)\\)>/", array('midcom_helper_misc', 'include_element'), $code);
        // Echo variables
        $code = preg_replace_callback("%&\(([^)]*)\);%i", array('self', 'expand_variable'), $code);
        return $code;
    }

    /**
     * Return a string as formatted by a Midgard formatter
     * @see http://www.midgard-project.org/documentation/reference-other-mgd_format/
     */
    public static function format_variable($content, $name)
    {
        if (!isset(self::$_filters[$name]))
        {
            return $content;
        }

        ob_start();
        switch ($name)
        {
            case 's':
                //display as-is
            case 'h':
            case 'H':
                //According to documentation, these two should do something, but actually they don't...
                echo $content;
                break;
            case 'p':
                eval('?>' . $content);
                break;
            default:
                call_user_func($GLOBALS['midgard_filters'][$name], $content);
                break;
            }
        return ob_get_clean();
    }

    public static function expand_variable($variable)
    {
        $variable_parts = explode(':', $variable[1]);
        $variable = '$' . $variable_parts[0];

        if (strpos($variable, '.') !== false)
        {
            $parts = explode('.', $variable);
            $variable = $parts[0] . '->' . $parts[1];
        }

        if (    isset($variable_parts[1])
             && array_key_exists($variable_parts[1], self::$_filters))
        {
            switch ($variable_parts[1])
            {
                case 's':
                    //display as-is
                case 'h':
                case 'H':
                    //According to documentation, these two should do something, but actually they don't...
                    $command = 'echo ' . $variable;
                    break;
                case 'p':
                    $command = 'eval(\'?>\' . ' . $variable . ')';
                    break;
                default:
                    $function = self::$_filters[$variable_parts[1]];
                    $command = $function . '(' . $variable . ')';
                    break;
            }
        }
        else
        {
            $command = 'echo htmlentities(' . $variable . ', ENT_COMPAT, $_MIDCOM->i18n->get_current_charset())';
        }

        return "<?php $command; ?>";
    }

    /**
     * Include a theme element
     */
    public static function include_element($name)
    {
        static $style = null;

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
                return $GLOBALS['midcom_config']['midcom_site_title'];
            case 'content':
                return '<(content)>';
            default:
                $element_file = OPENPSA2_THEME_ROOT . $GLOBALS['midcom_config']['theme'] . '/style' . midcom_connection::get_url('page_style') . "/{$element}.php";

                if (!file_exists($element_file))
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
                $value = file_get_contents($element_file);
                return preg_replace_callback("/<\\(([a-zA-Z0-9 _-]+)\\)>/", array('midcom_helper_misc', 'include_element'), $value);
        }
    }

    /**
     * Helper function for generating "clean" URL names from titles, etc.
     *
     * @param string $string    String to edit.
     * @param string $replacer    The replacement for invalid characters.
     * @return string            Normalized name.
     */
    public static function generate_urlname_from_string($string, $replacer = "-", $r = 0)
    {
        if ($r > 5)
        {
            debug_add('$r > 5, aborting', MIDCOM_LOG_ERROR);
            return $string;
        }
        if (empty($string))
        {
            debug_add('$string was empty(), aborting', MIDCOM_LOG_ERROR);
            return '';
        }
        // TODO: sanity-check $replacer ?
        $orig_string = $string;
        // Try to transliterate non-latin strings to URL-safe format
        require_once(MIDCOM_ROOT. '/midcom/helper/utf8_to_ascii.php');
        $string = utf8_to_ascii($string, $replacer);
        $string = trim(str_replace('[?]', '', $string));

        // Ultimate fall-back, if we couldn't get anything out of the transliteration we use the UTF-8 character hexes as the name string to have *something*
        if (   empty($string)
               || preg_match("/^{$replacer}+$/", $string))
        {
            $i = 0;
            // make sure this is not mb_strlen (ie mb automatic overloading off)
            $len = strlen($orig_string);
            $string = '';
            while ($i < $len)
            {
                $byte = $orig_string[$i];
                $string .= str_pad(dechex(ord($byte)), '0', STR_PAD_LEFT);
                $i++;
            }
        }

        // Rest of spaces to underscores
        $string = preg_replace('/\s+/', '_', $string);

        // Regular expression for characters to replace (the ^ means an inverted character class, ie characters *not* in this class are replaced)
        $regexp = '/[^a-zA-Z0-9_-]/';
        // Replace the unsafe characters with the given replacer (which is supposed to be safe...)
        $safe = preg_replace($regexp, $replacer, $string);

        // Strip trailing {$replacer}s and underscores from start and end of string
        $safe = preg_replace("/^[{$replacer}_]+|[{$replacer}_]+$/", '', $safe);

        // Clean underscores around $replacer
        $safe = preg_replace("/_{$replacer}|{$replacer}_/", $replacer, $safe);

        // Any other cleanup routines ?

        // We're done here, return $string lowercased
        $safe = strtolower($safe);

        /**
         * Quick and dirty workaround for http://trac.midgard-project.org/ticket/1530 by recursing
         */
        // Recurse until we make no changes to the string
        if ($string === $safe)
        {
            return $string;
        }
        return self::generate_urlname_from_string($safe, $replacer, $r+1);
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
        elseif ($size > 1024)
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
}
?>
