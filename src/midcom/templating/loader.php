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
use midgard_style;
use midgard_element;
use midcom_db_style;

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
    private $cache = [];

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

    public function get_element(string $name, ?int $scope = null) : ?string
    {
        if ($scope && $content = $this->get_element_in_styletree($scope, $name)) {
            return $content;
        }
        return $this->get_element_from_snippet($name);
    }

    /**
     * Returns a style element that matches $name and is in style $id.
     * It also returns an element if it is not in the given style,
     * but in one of its parent styles.
     */
    private function get_element_in_styletree(int $id, string $name) : ?string
    {
        $cache_key = $id . '::' . $name;
        if (array_key_exists($cache_key, $this->cache)) {
            return $this->cache[$cache_key];
        }

        $element_mc = midgard_element::new_collector('style', $id);
        $element_mc->set_key_property('guid');
        $element_mc->add_value_property('value');
        $element_mc->add_constraint('name', '=', $name);
        $element_mc->execute();

        if ($keys = $element_mc->list_keys()) {
            $element_guid = key($keys);
            midcom::get()->cache->content->register($element_guid);
            return $this->add_to_cache($cache_key, $element_mc->get_subkey($element_guid, 'value'));
        }

        // No such element on this level, check parents
        $style_mc = midgard_style::new_collector('id', $id);
        $style_mc->set_key_property('guid');
        $style_mc->add_value_property('up');
        $style_mc->add_constraint('up', '>', 0);
        $style_mc->execute();

        if ($keys = $style_mc->list_keys()) {
            $style_guid = key($keys);
            midcom::get()->cache->content->register($style_guid);
            $up = $style_mc->get_subkey($style_guid, 'up');
            return $this->get_element_in_styletree($up, $name);
        }

        $this->cache[$cache_key] = null;
        return $this->cache[$cache_key];
    }

    /**
     * Try to get element from default style snippet
     */
    private function get_element_from_snippet(string $_element) : ?string
    {
        if (midcom::get()->config->get('theme')) {
            $src = "theme:{$_element}";
            if (array_key_exists($src, $this->cache)) {
                return $this->cache[$src];
            }
            if ($content = $this->get_element_from_theme($_element)) {
                return $this->add_to_cache($src, $content);
            }
        }

        foreach ($this->directories as $path) {
            $filename = $path . "/{$_element}.php";
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

    private function add_to_cache(string $cache_key, string $content) : string
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
     * Initializes style sources from topic
     */
    public function initialize_from_topic(midcom_db_topic $topic, midcom_core_context $context)
    {
        $_st = 0;
        // get user defined style for component
        // style inheritance
        // should this be cached somehow?
        $style = $topic->style ?: $context->get_inherited_style();
        if (!$style) {
            $styleengine_default_styles = midcom::get()->config->get('styleengine_default_styles');
            if (isset($styleengine_default_styles[$topic->component])) {
                $style = $styleengine_default_styles[$topic->component];
            }
        }

        if ($style) {
            if ($_st = midcom_db_style::id_from_path($style)) {
                if ($substyle = $context->get_key(MIDCOM_CONTEXT_SUBSTYLE)) {
                    $chain = explode('/', $substyle);
                    foreach ($chain as $stylename) {
                        if ($_subst_id = midcom_db_style::id_from_path($stylename, $_st)) {
                            $_st = $_subst_id;
                        }
                    }
                }
            } elseif (substr($style, 0, 6) === 'theme:') {
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

        $context->set_custom_key(midcom_db_style::class, $_st);
    }
}
