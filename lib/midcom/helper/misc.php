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
    public static function random_string(int $length, string $characters) : string
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

    public static function urlize(string $input) : string
    {
        $slugify = new Slugify;
        return $slugify->slugify($input);
    }

    /**
     * Turn midcom config files into PHP arrays
     */
    public static function parse_config($data, string $path) : array
    {
        try {
            return eval("return [{$data}\n];");
        } catch (ParseError $e) {
            throw new midcom_error('Failed to parse config data: ' . $e->getMessage() . ' in ' . $path . ' line ' . $e->getLine());
        }
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
     * @return string       The content of the snippet/file.
     */
    public static function get_snippet_content_graceful(string $path)
    {
        static $cached_snippets = [];

        if (!array_key_exists($path, $cached_snippets)) {
            $cached_snippets[$path] = null;
            if (str_starts_with($path, 'file:') || str_starts_with($path, 'conf:')) {
                $filename = self::resolve_path($path);
                if (is_readable($filename)) {
                    $cached_snippets[$path] = file_get_contents($filename);
                }
            } else {
                $snippet = new midgard_snippet();
                if ($snippet->get_by_path($path)) {
                    midcom::get()->cache->content->register($snippet->guid);
                    $cached_snippets[$path] = $snippet->code;
                }
            }
        }

        return $cached_snippets[$path];
    }

    public static function resolve_path(string $path) : string
    {
        if (str_starts_with($path, 'conf:')) {
            return midcom::get()->config->get('midcom_config_basedir') . '/midcom' . substr($path, 5);
        }
        if (str_starts_with($path, 'file:')) {
            $filename = MIDCOM_ROOT . substr($path, 5);
            if (file_exists($filename)) {
                return $filename;
            }
            // try in src
            $filename = preg_replace('/\/lib\/?$/', '/src', MIDCOM_ROOT) . substr($path, 5);
            if (file_exists($filename)) {
                return $filename;
            }
            //If we can't find the file in-tree, we look for out-of-tree components before giving up
            $filename = substr($path, 6);
            if (preg_match('|.+?/.+?/.+?/|', $filename)) {
                $component_name = preg_replace('|(.+?)/(.+?)/(.+?)/.+|', '$1.$2.$3', $filename);
                if (midcom::get()->componentloader->is_installed($component_name)) {
                    $filename = substr($filename, strlen($component_name));
                    return midcom::get()->componentloader->path_to_snippetpath($component_name) . $filename;
                }
            }
        }
        return $path;
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
     */
    public static function load_snippet(string $path) : array
    {
        $resolved_path = self::resolve_path($path);
        if (str_ends_with($resolved_path, '.php')) {
            return include $resolved_path;
        }
        $data = self::get_snippet_content_graceful($path);
        if ($data === null) {
            throw new midcom_error("Could not load the contents of the snippet {$path}: Snippet does not exist.");
        }
        return self::parse_config($data, $path);
    }

    /**
     * Find MIME type image for a document
     *
     * Used in midcom.helper.imagepopup, midgard.admin.asgard and org.openpsa.documents.
     *
     * @return string    Path to the icon
     */
    public static function get_mime_icon(string $mimetype) : string
    {
        $mime_fspath = MIDCOM_STATIC_ROOT . '/stock-icons/mime';
        $mime_urlpath = MIDCOM_STATIC_URL . '/stock-icons/mime';
        $mimetype_filename = str_replace('/', '-', $mimetype);
        if (!is_readable($mime_fspath)) {
            debug_add("Couldn't read directory {$mime_fspath}", MIDCOM_LOG_WARN);
        }

        if ($mimetype_filename == 'application-x-zip-compressed') {
            $filename = "gnome-application-zip.png";
        } else {
            $filename = "gnome-{$mimetype_filename}.png";
        }
        if (is_readable("{$mime_fspath}/{$filename}")) {
            return "{$mime_urlpath}/{$filename}";
        }
        // Default icon if there is none for the MIME type
        return $mime_urlpath . '/gnome-unknown.png';
    }

    /**
     * Pretty print file sizes
     *
     * @param int $size  File size in bytes
     */
    public static function filesize_to_string(int $size) : string
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
     * Returns the first instance of a given component on the site.
     *
     * @return array NAP array of the first component instance found
     */
    public static function find_node_by_component(string $component) : ?array
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
}
