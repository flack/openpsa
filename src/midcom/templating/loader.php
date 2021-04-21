<?php
/**
 * @package midcom.templating
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace midcom\templating;

use midcom;
use midcom_db_topic;
use midcom_core_context;
use midcom_connection;

/**
 * templating loader class
 *
 * Style Inheritance
 *
 * The basic path the styleloader follows to find a style element is:
 * 1. Topic style -> if the current topic has a style set
 * 2. Inherited topic style -> if the topic inherits a style from another topic.
 * 3. Site-wide per-component default style -> if defined in MidCOM configuration key styleengine_default_styles
 * 4. Theme style -> the style of the MidCOM component.
 * 5. The file style. This is usually the elements found in the component's style directory.
 *
 * Regarding no. 5:
 * It is possible to add extra file styles if so is needed for example by a portal component.
 * This is done either using the append/prepend component_style functions of midcom::get()->style or by setting it
 * to another directory by calling (append|prepend)_styledir directly.
 *
 * @package midcom.templating
 */
class loader
{
    /**
     * Default style element cache
     *
     * @var string[]
     */
    protected $cache = [];

    /**
     * The stack of directories to check for styles.
     *
     * @var string[]
     */
    private $directories = [];

    public function set_directories(?midcom_db_topic $topic, array $prepend, array $append)
    {
        $this->directories = $prepend;
        if ($snippetdir = $this->get_component_snippetdir($topic)) {
            $this->directories[] = $snippetdir;
        }
        $this->directories = array_merge($this->directories, $append);
    }

    /**
     * Gets the component styledir associated with the topic's component.
     *
     * @return mixed the path to the component's style directory.
     */
    private function get_component_snippetdir(?midcom_db_topic $topic) : ?string
    {
        if (empty($topic->component)) {
            return null;
        }
        return midcom::get()->componentloader->path_to_snippetpath($topic->component) . "/style";
    }

    /**
     * Try to get element from default style snippet
     */
    public function get_element(string $name, bool $scope_from_path) : ?string
    {
        if (midcom::get()->config->get('theme')) {
            $src = "theme:{$name}";
            if (array_key_exists($src, $this->cache)) {
                return $this->cache[$src];
            }
            if ($content = $this->get_element_from_theme($name)) {
                return $this->add_to_cache($src, $content);
            }
        }

        foreach ($this->directories as $path) {
            $filename = $path . "/{$name}.php";
            if (file_exists($filename)) {
                if (array_key_exists($filename, $this->cache)) {
                    return $this->cache[$filename];
                }
                return $this->add_to_cache($filename, file_get_contents($filename));
            }
        }
        return null;
    }

    /**
     * Get the content of the element by the passed element name.
     * Tries to resolve path according to theme-name & page
     */
    private function get_element_from_theme(string $element_name) : ?string
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

        return null;
    }

    protected function add_to_cache(string $cache_key, string $content) : string
    {
        $this->cache[$cache_key] = $this->resolve_includes($content);
        return $this->cache[$cache_key];
    }

    private function resolve_includes(string $content) : string
    {
        return preg_replace_callback("/<\\(([a-zA-Z0-9 _-]+)\\)>/", function (array $matches) {
            $element = $matches[1];

            switch ($element) {
                case 'title':
                    return midcom::get()->config->get('midcom_site_title');
                case 'content':
                    return '<?php midcom_core_context::get()->show(); ?>';
                default:
                    if ($value = $this->get_element_from_theme($element)) {
                        return $this->resolve_includes($value);
                    }
                    return '';
            }
        }, $content);
    }

    /**
     * Initializes style sources
     */
    public function initialize(midcom_core_context $context, ?string $style)
    {
        if ($style && str_starts_with($style, 'theme:')) {
            $theme_dir = OPENPSA2_THEME_ROOT . midcom::get()->config->get('theme') . '/style';
            $parts = explode('/', str_replace('theme:/', '', $style));

            foreach ($parts as &$part) {
                $theme_dir .= '/' . $part;
                $part = $theme_dir;
            }
            foreach (array_reverse(array_filter($parts, 'is_dir')) as $dirname) {
                midcom::get()->style->prepend_styledir($dirname);
            }
        }
    }
}
