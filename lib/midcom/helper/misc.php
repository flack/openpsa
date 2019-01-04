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
     *
     * @param integer $length
     * @param string $characters
     * @throws InvalidArgumentException
     * @return string
     */
    public static function random_string($length, $characters)
    {
        if ($length < 1) {
            throw new InvalidArgumentException('invalid length');
        }
        $size = strlen($characters) - 1;
        if ($size < 1) {
            throw new InvalidArgumentException('invalid characters');
        }
        $return = '';
        for ($i = 0; $i < $length; $i++) {
            $return .= $characters[random_int(0, $size)];
        }
        return $return;
    }

    /**
     * Turn midcom config files into PHP arrays
     *
     * @param string $data The data to parse
     * @throws midcom_error
     * @return array The config in array format
     */
    public static function parse_config($data)
    {
        $data = eval("return [{$data}\n];");
        if ($data === false) {
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
        static $cached_snippets = [];
        if (array_key_exists($path, $cached_snippets)) {
            return $cached_snippets[$path];
        }

        if (substr($path, 0, 5) == 'file:') {
            $filename = MIDCOM_ROOT . substr($path, 5);
            if (!file_exists($filename)) {
                // try in src
                $filename = preg_replace('/\/lib\/?$/', '/src', MIDCOM_ROOT) . substr($path, 5);
            }
            if (!file_exists($filename)) {
                //If we can't find the file in-tree, we look for out-of-tree components before giving up
                $found = false;
                $filename = substr($path, 6);
                if (preg_match('|.+?/.+?/.+?/|', $filename)) {
                    $component_name = preg_replace('|(.+?)/(.+?)/(.+?)/.+|', '$1.$2.$3', $filename);
                    if (midcom::get()->componentloader->is_installed($component_name)) {
                        $filename = substr($filename, strlen($component_name));
                        $filename = midcom::get()->componentloader->path_to_snippetpath($component_name) . $filename;
                        if (file_exists($filename)) {
                            $found = true;
                        }
                    }
                }
                if (!$found) {
                    $cached_snippets[$path] = null;
                    return null;
                }
            }
            $data = file_get_contents($filename);
        } elseif (substr($path, 0, 5) == 'conf:') {
            $filename = midcom::get()->config->get('midcom_config_basedir') . '/midcom' . substr($path, 5);
            if (!file_exists($filename)) {
                $cached_snippets[$path] = null;
                return null;
            }
            $data = file_get_contents($filename);
        } else {
            $snippet = new midgard_snippet();
            try {
                $stat = $snippet->get_by_path($path);
            } catch (Exception $e) {
                debug_add($e->getMessage(), MIDCOM_LOG_ERROR);
            } finally {
                if (empty($stat)) {
                    $cached_snippets[$path] = null;
                    return null;
                }
            }
            if (isset(midcom::get()->cache->content)) {
                midcom::get()->cache->content->register($snippet->guid);
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
        if (is_null($data)) {
            throw new midcom_error("Could not load the contents of the snippet {$path}: Snippet does not exist.");
        }
        return $data;
    }

    /**
     * Preparse and include snippet
     *
     * @param string $path    The path of the snippet that should be included.
     * @return boolean Returns false if the snippet could not be loaded or true, if it was evaluated successfully.
     */
    public static function include_snippet_php($path)
    {
        $code = self::get_snippet_content_graceful($path);
        if (empty($code)) {
            debug_add("Could not find snippet {$path}: ", MIDCOM_LOG_ERROR);
            return false;
        }
        debug_add("Evaluating snippet {$path}.");
        eval('?>' . self::preparse($code));
        return true;
    }

    /**
     * This is a bit of a hack to allow &(); tags
     *
     * @param string $code The unprocessed code
     * @return string The processed code
     */
    public static function preparse($code)
    {
        // Get style elements
        $code = preg_replace_callback("/<\\(([a-zA-Z0-9 _-]+)\\)>/", [midcom_helper_misc::class, 'include_element'], $code);
        // Echo variables
        return preg_replace_callback("%&\(([^)]*)\);%i", [midcom_helper_formatter::class, 'convert_to_php'], $code);
    }

    /**
     * Include a theme element
     */
    public static function include_element($name)
    {
        if (is_array($name)) {
            $element = $name[1];
        } else {
            $element = $name;
        }

        switch ($element) {
            case 'title':
                return midcom::get()->config->get('midcom_site_title');
            case 'content':
                return '<?php midcom_core_context::get()->show(); ?>';
            default:
                $value = self::get_element_content($element);

                if (empty($value)) {
                    if ($element == 'ROOT') {
                        /* If we don't have a ROOT element, go to content directly. style-init or style-finish
                         * can load the page style
                         */
                        return '<?php midcom_core_context::get()->show(); ?>';
                    }
                    return '';
                }
                return preg_replace_callback("/<\\(([a-zA-Z0-9 _-]+)\\)>/", [midcom_helper_misc::class, 'include_element'], $value);
        }
    }

    /**
     * Find MIME type image for a document
     *
     * Used in midcom.helper.imagepopup, midgard.admin.asgard and org.openpsa.documents.
     *
     * @param string $mimetype  Document MIME type
     * @return string    Path to the icon
     */
    public static function get_mime_icon($mimetype)
    {
        $mime_fspath = MIDCOM_STATIC_ROOT . '/stock-icons/mime';
        $mime_urlpath = MIDCOM_STATIC_URL . '/stock-icons/mime';
        $mimetype_filename = str_replace('/', '-', $mimetype);
        if (!is_readable($mime_fspath)) {
            debug_add("Couldn't read directory {$mime_fspath}", MIDCOM_LOG_WARN);
        }

        $check_files = [];
        switch ($mimetype_filename) {
            case 'application-x-zip-compressed':
                $check_files[] = "gnome-application-zip.png";
                break;
            default:
                $check_files[] = "{$mimetype_filename}.png";
                $check_files[] = "gnome-{$mimetype_filename}.png";
                break;
        }

        // Return first match
        foreach ($check_files as $filename) {
            if (is_readable("{$mime_fspath}/{$filename}")) {
                return "{$mime_urlpath}/{$filename}";
            }
        }
        // Default icon if there is none for the MIME type
        return $mime_urlpath . '/gnome-unknown.png';
    }

    /**
     * Pretty print file sizes
     *
     * @param int $size  File size in bytes
     * @return string    Prettified file size
     */
    public static function filesize_to_string($size)
    {
        if ($size >= 1048576) {
            // More than a meg
            return sprintf("%01.1f", $size / 1048576) . " MB";
        } elseif ($size >= 1024) {
            // More than a kilo
            return sprintf("%01.1f", $size / 1024) . " KB";
        }
        return $size . " Bytes";
    }

    /**
     * Fix newline etc encoding issues in serialized data
     *
     * @param string $data The data to fix.
     * @return string $data with serializations fixed.
     */
    public static function fix_serialization($data)
    {
        //Skip on empty data
        if (empty($data)) {
            return $data;
        }

        $preg='/s:([0-9]+):"(.*?)";/ms';
        preg_match_all($preg, $data, $matches);
        $cache = [];

        foreach ($matches[0] as $k => $origFullStr) {
            $origLen = $matches[1][$k];
            $origStr = $matches[2][$k];
            $newLen = strlen($origStr);
            if ($newLen != $origLen) {
                $newFullStr = "s:$newLen:\"$origStr\";";
                //For performance we cache information on which strings have already been replaced
                if (!array_key_exists($origFullStr, $cache)) {
                    $data = str_replace($origFullStr, $newFullStr, $data);
                    $cache[$origFullStr] = true;
                }
            }
        }

        return $data;
    }

    /**
     * Returns the first instance of a given component on the site.
     *
     * @param string $component The component name
     * @param integer $node_id Node ID of parent topic
     * @param midcom_helper_nav $nap
     * @return array NAP array of the first component instance found
     */
    public static function find_node_by_component($component, $node_id = null, midcom_helper_nav $nap = null)
    {
        static $cache = [];

        $cache_node = $node_id;
        if (is_null($cache_node)) {
            $cache_node = 0;
        }
        $cache_key = $cache_node . '::' . $component;

        if (array_key_exists($cache_key, $cache)) {
            return $cache[$cache_key];
        }

        if (null === $nap) {
            $nap = new midcom_helper_nav;
        }

        if (is_null($node_id)) {
            $node_id = $nap->get_root_node();

            $root_node = $nap->get_node($node_id);
            if ($root_node[MIDCOM_NAV_COMPONENT] == $component) {
                $cache[$cache_key] = $root_node;
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

        if (count($topics) == 0) {
            $cache[$cache_key] = null;
            return null;
        }

        $node = $nap->get_node($topics[0]->id);
        $cache[$cache_key] = $node;

        return $node;
    }

    /**
     * Get the content of the element by the passed element name.
     * Tries to resolve path according to theme-name & page
     *
     * @param string $element_name
     * @param string $theme_root
     */
    public static function get_element_content($element_name, $theme_root = OPENPSA2_THEME_ROOT)
    {
        $theme = midcom::get()->config->get('theme');
        $path_array = explode('/', $theme);

        //get the page if there is one
        $page = midcom_connection::get_url('page_style');
        $substyle = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_SUBSTYLE);
        //check if we have elements for the sub-styles
        while (!empty($path_array)) {
            $theme_path = implode('/', $path_array);
            $candidates = [];
            if ($substyle) {
                $candidates[] =  $theme_root . $theme_path .  "/style/{$substyle}/{$element_name}.php";
            }
            if ($page) {
                $candidates[] =  $theme_root . $theme_path .  "/style{$page}/{$element_name}.php";
            }

            $candidates[] = $theme_root . $theme_path .  "/style/{$element_name}.php";

            foreach (array_filter($candidates, 'file_exists') as $candidate) {
                return file_get_contents($candidate);
            }

            //remove last theme part
            array_pop($path_array);
        }

        return false;
    }

    /**
     * Iterate through possible page directories in style-tree and check if the page exists (as a folder).
     *
     * @param string $page_name
     * @param string $theme_root
     */
    public static function check_page_exists($page_name, $theme_root = OPENPSA2_THEME_ROOT)
    {
        $path_array = explode('/', midcom::get()->config->get('theme'));

        while (!empty($path_array)) {
            $theme_path = implode('/', $path_array);
            if (is_dir($theme_root . $theme_path . '/style/' . $page_name)) {
                return true;
            }
            array_pop($path_array);
        }
        return false;
    }
}
