<?php
/**
 * @package midcom.helper
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Cocur\Slugify\Slugify;

/**
 * Miscellaneous helper functions
 *
 * @package midcom.helper
 */
class midcom_helper_misc
{
    /**
     * @param integer $length
     * @param string $characters
     * @throws InvalidArgumentException
     */
    public static function random_string($length, $characters) : string
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
     * @param string $input
     */
    public static function urlize($input) : string
    {
        $slugify = new Slugify;
        return $slugify->slugify($input);
    }

    /**
     * Turn midcom config files into PHP arrays
     *
     * @param string $data The data to parse
     * @throws midcom_error
     */
    public static function parse_config($data) : array
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

        if (!array_key_exists($path, $cached_snippets)) {
            if (substr($path, 0, 5) == 'file:') {
                $cached_snippets[$path] = self::load_from_file($path);
            } elseif (substr($path, 0, 5) == 'conf:') {
                $cached_snippets[$path] = self::load(midcom::get()->config->get('midcom_config_basedir') . '/midcom' . substr($path, 5));
            } else {
                $cached_snippets[$path] = self::load_from_snippet($path);
            }
        }

        return $cached_snippets[$path];
    }

    private static function load_from_snippet(string $path)
    {
        $snippet = new midgard_snippet();
        if (!$snippet->get_by_path($path)) {
            return null;
        }
        if (isset(midcom::get()->cache->content)) {
            midcom::get()->cache->content->register($snippet->guid);
        }
        return $snippet->code;
    }

    private static function load_from_file(string $path)
    {
        $filename = MIDCOM_ROOT . substr($path, 5);
        if (!file_exists($filename)) {
            // try in src
            $filename = preg_replace('/\/lib\/?$/', '/src', MIDCOM_ROOT) . substr($path, 5);
            if (!file_exists($filename)) {
                //If we can't find the file in-tree, we look for out-of-tree components before giving up
                $filename = substr($path, 6);
                if (preg_match('|.+?/.+?/.+?/|', $filename)) {
                    $component_name = preg_replace('|(.+?)/(.+?)/(.+?)/.+|', '$1.$2.$3', $filename);
                    if (midcom::get()->componentloader->is_installed($component_name)) {
                        $filename = substr($filename, strlen($component_name));
                        $filename = midcom::get()->componentloader->path_to_snippetpath($component_name) . $filename;
                    }
                }
            }
        }
        return self::load($filename);
    }

    private static function load(string $filename)
    {
        if (!file_exists($filename)) {
            return null;
        }
        return file_get_contents($filename);
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
     */
    public static function get_snippet_content($path) : string
    {
        $data = self::get_snippet_content_graceful($path);
        if ($data === null) {
            throw new midcom_error("Could not load the contents of the snippet {$path}: Snippet does not exist.");
        }
        return $data;
    }

    /**
     * This is a bit of a hack to allow &(); tags
     *
     * @param string $code The unprocessed code
     */
    public static function preparse($code) : string
    {
        // Get style elements
        $code = preg_replace_callback("/<\\(([a-zA-Z0-9 _-]+)\\)>/", [midcom_helper_misc::class, 'include_element'], $code);
        // Echo variables
        return preg_replace_callback("%&\(([^)]*)\);%i", [midcom_helper_formatter::class, 'convert_to_php'], $code);
    }

    /**
     * Include a theme element
     */
    public static function include_element($name) : string
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
    public static function get_mime_icon($mimetype) : string
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
     */
    public static function filesize_to_string($size) : string
    {
        if ($size >= 1048576) {
            // More than a meg
            return sprintf("%01.1f", $size / 1048576) . " MB";
        }
        if ($size >= 1024) {
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
     * @return array NAP array of the first component instance found
     */
    public static function find_node_by_component($component)
    {
        static $cache = [];

        if (!array_key_exists($component, $cache)) {
            $cache[$component] = null;

            $nap = new midcom_helper_nav;
            $node_id = $nap->get_root_node();
            $root_node = $nap->get_node($node_id);

            if ($root_node[MIDCOM_NAV_COMPONENT] == $component) {
                $cache[$component] = $root_node;
            } else {
                $qb = midcom_db_topic::new_query_builder();
                $qb->add_constraint('component', '=', $component);
                $qb->add_constraint('name', '<>', '');
                $qb->add_constraint('up', 'INTREE', $node_id);
                $qb->set_limit(1);
                $topics = $qb->execute();

                if (count($topics) === 1) {
                    $cache[$component] = $nap->get_node($topics[0]->id);
                }
            }
        }

        return $cache[$component];
    }

    /**
     * Get the content of the element by the passed element name.
     * Tries to resolve path according to theme-name & page
     *
     * @param string $element_name
     */
    public static function get_element_content($element_name)
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
                $candidates[] = '/' . $substyle . '/' . $element_name;
            }
            if ($page) {
                $candidates[] = $page . '/' . $element_name;
            }
            $candidates[] = '/' . $element_name;

            foreach ($candidates as $candidate) {
                $filename = OPENPSA2_THEME_ROOT . $theme_path . '/style' . $candidate . '.php';
                if (file_exists($filename)) {
                    return file_get_contents($filename);
                }
            }

            //remove last theme part
            array_pop($path_array);
        }

        return false;
    }
}
