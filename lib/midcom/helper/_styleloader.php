<?php
/**
 * @package midcom.helper
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * This class is responsible for all style management. It is instantiated by the MidCOM framework
 * and accessible through the midcom::get()->style object.
 *
 * The method <code>show($style)</code> returns the style element $style for the current
 * component:
 *
 * It checks whether a style path is defined for the current component.
 *
 * - If there is a user defined style path, the element named $style in
 *   this path is returned,
 * - otherwise the element "$style" is taken from the default style of the
 *   current component (/path/to/component/style/$path).
 *
 * (The default fallback is always the default style, e.g. if $style
 * is not in the user defined style path)
 *
 * To enable cross-style referencing and provide the opportunity to access
 * any style element, "show" can be called with a full qualified style
 * path (like "/mystyle/element1", while the current page's style may be set
 * to "/yourstyle").
 *
 * Note: To make sure sub-styles and elements included in styles are handled
 * correctly, use:
 *
 * <code>
 * <?php midcom_show_style ("elementname"); ?>
 * </code>
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
 * Regarding nr. 4:
 * It is possible to add extra file styles if so is needed for example by a portal component.
 * This is done either using the append/prepend component_style functions or by setting it
 * to another directory by calling (append|prepend)_styledir directly.
 *
 * NB: You cannot change this in another style element or in a _show() function in a component.
 *
 * @package midcom.helper
 */
class midcom_helper__styleloader
{
    /**
     * Current topic
     *
     * @var midcom_db_topic
     */
    private $_topic;

    /**
     * Default style path
     *
     * @var string
     */
    private $_snippetdir;

    /**
     * Context stack
     *
     * @var midcom_core_context[]
     */
    private $_context = [];

    /**
     * Default style element cache
     *
     * @var array
     */
    private $_snippets = [];

    /**
     * List of styledirs to handle after componentstyle
     *
     * @var array
     */
    private $_styledirs_append = [];

    /**
     * List of styledirs to handle before componentstyle
     *
     * @var array
     */
    private $_styledirs_prepend = [];

    /**
     * The stack of directories to check for styles.
     */
    private $_styledirs = [];

    /**
     * Data to pass to the style
     *
     * @var array
     */
    public $data;

    /**
     * Returns a style element that matches $name and is in style $id.
     * It also returns an element if it is not in the given style,
     * but in one of its parent styles.
     *
     * @param int $id        The style id to search in.
     * @param string $name    The element to locate.
     * @return string    Value of the found element, or false on failure.
     */
    private function _get_element_in_styletree($id, string $name) : ?string
    {
        static $cached = [];
        $cache_key = $id . '::' . $name;

        if (array_key_exists($cache_key, $cached)) {
            return $cached[$cache_key];
        }

        $element_mc = midgard_element::new_collector('style', $id);
        $element_mc->set_key_property('guid');
        $element_mc->add_value_property('value');
        $element_mc->add_constraint('name', '=', $name);
        $element_mc->execute();

        if ($keys = $element_mc->list_keys()) {
            $element_guid = key($keys);
            $cached[$cache_key] = $element_mc->get_subkey($element_guid, 'value');
            midcom::get()->cache->content->register($element_guid);
            return $cached[$cache_key];
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
            return $this->_get_element_in_styletree($up, $name);
        }

        $cached[$cache_key] = null;
        return $cached[$cache_key];
    }

    /**
     * Looks for a style element matching $path (either in a user defined style
     * or the default style snippetdir) and displays/evaluates it.
     *
     * @param string $path    The style element to show.
     * @return boolean            True on success, false otherwise.
     */
    public function show($path) : bool
    {
        if ($this->_context === []) {
            debug_add("Trying to show '{$path}' but there is no context set", MIDCOM_LOG_INFO);
            return false;
        }

        $style = $this->load($path);

        if ($style === null) {
            if ($path == 'ROOT') {
                // Go to fallback ROOT instead of displaying a blank page
                return $this->show_midcom($path);
            }

            debug_add("The element '{$path}' could not be found.", MIDCOM_LOG_INFO);
            return false;
        }
        $this->render($style, $path);

        return true;
    }

    /**
     * Load style element content
     *
     * @param string $path The element name
     */
    public function load($path) : ?string
    {
        $element = $path;
        // we have full qualified path to element
        if (preg_match("|(.*)/(.*)|", $path, $matches)) {
            $styleid = midcom_db_style::id_from_path($matches[1]);
            $element = $matches[2];
        }

        if ($styleid = $styleid ?? $this->_context[0]->get_custom_key(midcom_db_style::class)) {
            $style = $this->_get_element_in_styletree($styleid, $element);
        }

        if (empty($style)) {
            $style = $this->_get_element_from_snippet($element);
        }
        return $style;
    }

    /**
     * Renders the style element with current request data
     *
     * @param string $style The style element content
     * @param string $path the element's name
     * @throws midcom_error
     */
    private function render(string $style, string $path)
    {
        if (midcom::get()->config->get('wrap_style_show_with_name')) {
            $style = "\n<!-- Start of style '{$path}' -->\n" . $style;
            $style .= "\n<!-- End of style '{$path}' -->\n";
        }

        // This is a bit of a hack to allow &(); tags
        $preparsed = midcom_helper_misc::preparse($style);
        if (midcom_core_context::get()->has_custom_key('request_data')) {
            $data =& midcom_core_context::get()->get_custom_key('request_data');
        }

        if (eval('?>' . $preparsed) === false) {
            // Note that src detection will be semi-reliable, as it depends on all errors being
            // found before caching kicks in.
            throw new midcom_error("Failed to parse style element '{$path}', see above for PHP errors.");
        }
    }

    /**
     * Looks for a midcom core style element matching $path and displays/evaluates it.
     * This offers a bit reduced functionality and will only look in the DB root style,
     * the theme directory and midcom's style directory, because it has to work even when
     * midcom is not yet fully initialized
     *
     * @param string $path    The style element to show.
     * @return boolean            True on success, false otherwise.
     */
    public function show_midcom($path) : bool
    {
        $_element = $path;
        $_style = null;

        $context = midcom_core_context::get();

        try {
            $root_topic = $context->get_key(MIDCOM_CONTEXT_ROOTTOPIC);
            if (   $root_topic->style
                && $db_style = midcom_db_style::id_from_path($root_topic->style)) {
                $_style = $this->_get_element_in_styletree($db_style, $_element);
            }
        } catch (midcom_error_forbidden $e) {
            $e->log();
        }

        if ($_style === null) {
            if (isset($this->_styledirs[$context->id])) {
                $styledirs_backup = $this->_styledirs;
            }
            $this->_snippetdir = MIDCOM_ROOT . '/midcom/style';
            $this->_styledirs[$context->id][0] = $this->_snippetdir;

            $_style = $this->_get_element_from_snippet($_element);

            if (isset($styledirs_backup)) {
                $this->_styledirs = $styledirs_backup;
            }
        }

        if ($_style !== null) {
            $this->render($_style, $path);
            return true;
        }
        debug_add("The element '{$path}' could not be found.", MIDCOM_LOG_INFO);
        return false;
    }

    /**
     * Try to get element from default style snippet
     */
    private function _get_element_from_snippet(string $_element) : ?string
    {
        $src = "{$this->_snippetdir}/{$_element}";
        if (array_key_exists($src, $this->_snippets)) {
            return $this->_snippets[$src];
        }
        if (   midcom::get()->config->get('theme')
            && $content = midcom_helper_misc::get_element_content($_element)) {
            $this->_snippets[$src] = $content;
            return $content;
        }

        $current_context = midcom_core_context::get()->id;
        foreach ($this->_styledirs[$current_context] as $path) {
            $filename = $path . "/{$_element}.php";
            if (file_exists($filename)) {
                if (!array_key_exists($filename, $this->_snippets)) {
                    $this->_snippets[$filename] = file_get_contents($filename);
                }
                return $this->_snippets[$filename];
            }
        }
        return null;
    }

    /**
     * Gets the component styledir associated with the topic's component.
     *
     * @return mixed the path to the component's style directory.
     */
    private function _get_component_snippetdir() : ?string
    {
        if (empty($this->_topic->component)) {
            return null;
        }
        return midcom::get()->componentloader->path_to_snippetpath($this->_topic->component) . "/style";
    }

    /**
     * Adds an extra style directory to check for style elements at
     * the end of the styledir queue.
     *
     * @throws midcom_error exception if directory does not exist.
     */
    public function append_styledir(string $dirname)
    {
        if (!file_exists($dirname)) {
            throw new midcom_error("Style directory $dirname does not exist!");
        }
        $this->_styledirs_append[midcom_core_context::get()->id][] = $dirname;
    }

    /**
     * Function prepend styledir
     */
    public function prepend_styledir(string $dirname)
    {
        if (!file_exists($dirname)) {
            throw new midcom_error("Style directory {$dirname} does not exist.");
        }
        $this->_styledirs_prepend[midcom_core_context::get()->id][] = $dirname;
    }

    /**
     * Append the styledir of a component to the queue of styledirs.
     */
    public function append_component_styledir(string $component)
    {
        $loader = midcom::get()->componentloader;
        $path = $loader->path_to_snippetpath($component) . "/style";
        $this->append_styledir($path);
    }

    /**
     * Prepend the styledir of a component
     */
    public function prepend_component_styledir(string $component)
    {
        $loader = midcom::get()->componentloader;
        $path = $loader->path_to_snippetpath($component) . "/style";
        $this->prepend_styledir($path);
    }

    /**
     * Appends a substyle after the currently selected component style.
     *
     * Enables a depth of more than one style during substyle selection.
     */
    public function append_substyle(string $newsub)
    {
        // Make sure try to use only the first argument if we get space separated list, fixes #1788
        if (strpos($newsub, ' ') !== false) {
            $newsub = preg_replace('/^(.+?) .+/', '$1', $newsub);
        }

        $context = midcom_core_context::get();
        $current_style = $context->get_key(MIDCOM_CONTEXT_SUBSTYLE);

        if (!empty($current_style)) {
            $newsub = $current_style . '/' . $newsub;
        }

        $context->set_key(MIDCOM_CONTEXT_SUBSTYLE, $newsub);
    }

    /**
     * Prepends a substyle before the currently selected component style.
     *
     * Enables a depth of more than one style during substyle selection.
     *
     * @param string $newsub The substyle to prepend.
     */
    function prepend_substyle($newsub)
    {
        $context = midcom_core_context::get();
        $current_style = $context->get_key(MIDCOM_CONTEXT_SUBSTYLE);

        if (!empty($current_style)) {
            $newsub .= "/" . $current_style;
        }
        debug_add("Updating Component Context Substyle from $current_style to $newsub");

        $context->set_key(MIDCOM_CONTEXT_SUBSTYLE, $newsub);
    }

    /**
     * Switches the context (see dynamic load).
     *
     * Private variables are adjusted, and the prepend and append styles are merged with the componentstyle.
     * You cannot change the style stack after that (unless you call enter_context again of course).
     *
     * @param midcom_core_context $context The context to enter
     */
    public function enter_context(midcom_core_context $context)
    {
        // set new context and topic
        array_unshift($this->_context, $context); // push into context stack

        $this->_topic = $context->get_key(MIDCOM_CONTEXT_CONTENTTOPIC);

        // Prepare styledir stacks
        if (!isset($this->_styledirs_prepend[$context->id])) {
            $this->_styledirs_prepend[$context->id] = [];
        }
        if (!isset($this->_styledirs_append[$context->id])) {
            $this->_styledirs_append[$context->id] = [];
        }

        if ($this->_topic) {
            $this->initialize_from_topic($context);
        }
        $this->_snippetdir = $this->_get_component_snippetdir();

        $this->_styledirs[$context->id] = array_merge(
            $this->_styledirs_prepend[$context->id],
            [$this->_snippetdir],
            $this->_styledirs_append[$context->id]
        );
    }

    /**
     * Initializes style sources from topic
     */
    private function initialize_from_topic(midcom_core_context $context)
    {
        $_st = 0;
        // get user defined style for component
        // style inheritance
        // should this be cached somehow?
        if ($style = $this->_topic->style ?: $context->get_inherited_style()) {
            if (substr($style, 0, 6) === 'theme:') {
                $theme_dir = OPENPSA2_THEME_ROOT . midcom::get()->config->get('theme') . '/style';
                $parts = explode('/', str_replace('theme:/', '', $style));

                foreach ($parts as &$part) {
                    $theme_dir .= '/' . $part;
                    $part = $theme_dir;
                }
                foreach (array_reverse(array_filter($parts, 'is_dir')) as $dirname) {
                    $this->prepend_styledir($dirname);
                }
            } else {
                $_st = midcom_db_style::id_from_path($style);
            }
        } else {
            // Get style from sitewide per-component defaults.
            $styleengine_default_styles = midcom::get()->config->get('styleengine_default_styles');
            if (isset($styleengine_default_styles[$this->_topic->component])) {
                $_st = midcom_db_style::id_from_path($styleengine_default_styles[$this->_topic->component]);
            }
        }

        if ($_st) {
            $substyle = $context->get_key(MIDCOM_CONTEXT_SUBSTYLE);

            if (is_string($substyle)) {
                $chain = explode('/', $substyle);
                foreach ($chain as $stylename) {
                    if ($_subst_id = midcom_db_style::id_from_path($stylename, $_st)) {
                        $_st = $_subst_id;
                    }
                }
            }
        }
        $context->set_custom_key(midcom_db_style::class, $_st);
    }

    /**
     * Switches the context (see dynamic load). Private variables $_context, $_topic
     * and $_snippetdir are adjusted.
     *
     * @todo check documentation
     */
    public function leave_context()
    {
        array_shift($this->_context);

        $previous_context = $this->_context[0] ?? midcom_core_context::get();
        $this->_topic = $previous_context->get_key(MIDCOM_CONTEXT_CONTENTTOPIC);

        $this->_snippetdir = $this->_get_component_snippetdir();
    }
}
