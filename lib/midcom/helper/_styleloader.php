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
 * @todo Document Style Inheritance
 *
 * @package midcom.helper
 */
class midcom_helper__styleloader
{
    /**
     * Current style scope
     *
     * @var array
     */
    private $_scope = [];

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
     * @var array
     */
    private $_context = [];

    /**
     * Style element cache
     *
     * @var array
     */
    private $_styles = [];

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
     * Returns the path of the style described by $id.
     *
     * @param int $id    Style id to look up path for
     * @return    string Style path
     */
    public function get_style_path_from_id($id)
    {
        static $path_cache = [];
        if (isset($path_cache[$id])) {
            return $path_cache[$id];
        }
        // Construct the path
        $path_parts = [];
        $original_id = $id;

        try {
            while (($style = new midcom_db_style($id))) {
                $path_parts[] = $style->name;
                $id = $style->up;

                if ($style->up == 0) {
                    // Toplevel style
                    break;
                }
            }
        } catch (midcom_error $e) {
        }

        $path_parts = array_reverse($path_parts);

        $path_cache[$original_id] = '/' . implode('/', $path_parts);

        return $path_cache[$original_id];
    }

    /**
     * Returns the id of the style described by $path.
     *
     * Note: $path already includes the element name, so $path looks like
     * "/rootstyle/style/style/element".
     *
     * @todo complete documentation
     * @param string $path      The path to retrieve
     * @param int $rootstyle    ???
     * @return    int ID of the matching style or false
     */
    public function get_style_id_from_path($path, $rootstyle = 0)
    {
        static $cached = [];

        $cache_key = $rootstyle . '::' . $path;

        if (array_key_exists($cache_key, $cached)) {
            return $cached[$cache_key];
        }

        $path = preg_replace("/^\/(.*)/", "$1", $path); // leading "/"
        $cached[$cache_key] = false;
        $current_style = 0;

        $path_array = array_filter(explode('/', $path));
        if (count($path_array) > 0) {
            $current_style = $rootstyle;
        }

        foreach ($path_array as $path_item) {
            $mc = midgard_style::new_collector('up', $current_style);
            $mc->set_key_property('guid');
            $mc->add_value_property('id');
            $mc->add_constraint('name', '=', $path_item);
            $mc->execute();
            $styles = $mc->list_keys();

            foreach (array_keys($styles) as $style_guid) {
                $current_style = $mc->get_subkey($style_guid, 'id');
                midcom::get()->cache->content->register($style_guid);
            }
        }

        if ($current_style != 0) {
            $cached[$cache_key] = $current_style;
        }

        return $cached[$cache_key];
    }

    /**
     * Returns a style element that matches $name and is in style $id.
     * It also returns an element if it is not in the given style,
     * but in one of its parent styles.
     *
     * @param int $id        The style id to search in.
     * @param string $name    The element to locate.
     * @return string    Value of the found element, or false on failure.
     */
    private function _get_element_in_styletree($id, $name)
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

        foreach ($element_mc->list_keys() as $element_guid => $value) {
            $value = $element_mc->get_subkey($element_guid, 'value');
            midcom::get()->cache->content->register($element_guid);
            $cached[$cache_key] = $value;
            return $value;
        }

        // No such element on this level, check parents
        $style_mc = midgard_style::new_collector('id', $id);
        $style_mc->set_key_property('guid');
        $style_mc->add_value_property('up');
        $style_mc->execute();

        foreach ($style_mc->list_keys() as $style_guid => $value) {
            midcom::get()->cache->content->register($style_guid);

            if ($up = $style_mc->get_subkey($style_guid, 'up')) {
                $value = $this->_get_element_in_styletree($up, $name);
                $cached[$cache_key] = $value;
                return $value;
            }
        }

        $cached[$cache_key] = false;
        return $cached[$cache_key];
    }

    /**
     * Looks for a style element matching $path (either in a user defined style
     * or the default style snippetdir) and displays/evaluates it.
     *
     * @param string $path    The style element to show.
     * @return boolean            True on success, false otherwise.
     */
    public function show($path)
    {
        if ($this->_context === []) {
            debug_add("Trying to show '{$path}' but there is no context set", MIDCOM_LOG_INFO);
            return false;
        }

        $style = $this->load($path);

        if ($style === false) {
            debug_add("The element '{$path}' could not be found.", MIDCOM_LOG_INFO);
            return false;
        }
        $this->render($this->parse($style, $path), $path);

        return true;
    }

    /**
     * Load style element content
     *
     * @param string $path The element name
     * @return false|string
     */
    public function load($path)
    {
        $element = $path;
        // we have full qualified path to element
        if (preg_match("|(.*)/(.*)|", $path, $matches)) {
            $stylepath = $matches[1];
            $element = $matches[2];
        }

        if (   isset($stylepath)
            && $styleid = $this->get_style_id_from_path($stylepath)) {
            array_unshift($this->_scope, $styleid);
        }

        $style = $this->_find_element_in_scope($element);

        if (!empty($styleid)) {
            array_shift($this->_scope);
        }

        if (!$style) {
            $style = $this->_get_element_from_snippet($element);
        }
        return $style;
    }

    /**
     * This is a bit of a hack to allow &(); tags
     *
     * @param string $style The style element content
     * @param string $path The element name
     * @return string The parsed element
     */
    public function parse($style, $path)
    {
        if (midcom::get()->config->get('wrap_style_show_with_name')) {
            $style = "\n<!-- Start of style '{$path}' -->\n" . $style;
            $style .= "\n<!-- End of style '{$path}' -->\n";
        }

        return midcom_helper_misc::preparse($style);
    }

    /**
     * Renders the style element with current request data
     *
     * @param string $preparsed The element's content as executable code
     * @param string $path the element's name
     * @param array $data Request date, if you don't want to use the global data
     * @throws midcom_error
     */
    public function render($preparsed, $path, array $data = [])
    {
        if (   empty($data)
            && midcom_core_context::get()->has_custom_key('request_data')) {
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
    public function show_midcom($path)
    {
        $_element = $path;
        $_style = false;

        $this->_snippetdir = MIDCOM_ROOT . '/midcom/style';
        $context = midcom_core_context::get();
        if (isset($this->_styledirs[$context->id])) {
            $styledirs_backup = $this->_styledirs;
        }

        $this->_styledirs[$context->id][0] = $this->_snippetdir;

        try {
            $root_topic = $context->get_key(MIDCOM_CONTEXT_ROOTTOPIC);
            if (   $root_topic->style
                && $db_style = $this->get_style_id_from_path($root_topic->style)) {
                $_style = $this->_get_element_in_styletree($db_style, $_element);
            }
        } catch (midcom_error_forbidden $e) {
            $e->log();
        }

        if ($_style === false) {
            $_style = $this->_get_element_from_snippet($_element);
        }

        if (isset($styledirs_backup)) {
            $this->_styledirs = $styledirs_backup;
        }

        if ($_style !== false) {
            $this->render($this->parse($_style, $path), $path);
            return true;
        }
        debug_add("The element '{$path}' could not be found.", MIDCOM_LOG_INFO);
        return false;
    }

    /**
     * Try to find element in current / given scope
     */
    private function _find_element_in_scope($_element)
    {
        if (count($this->_scope) > 0) {
            $src = "{$this->_scope[0]}/{$_element}";

            if (array_key_exists($src, $this->_styles)) {
                return $this->_styles[$src];
            }
            if (   $this->_scope[0] != ''
                && $result = $this->_get_element_in_styletree($this->_scope[0], $_element)) {
                $this->_styles[$src] = $result;
                return $this->_styles[$src];
            }
        }
        return false;
    }

    /**
     * Try to get element from default style snippet
     */
    private function _get_element_from_snippet($_element)
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
            $filename = $path .  "/{$_element}.php";
            if (file_exists($filename)) {
                $this->_snippets[$filename] = file_get_contents($filename);
                return $this->_snippets[$filename];
            }
        }
        return false;
    }

    /**
     * Gets the component style.
     *
     * @todo Document
     *
     * @param midcom_db_topic $topic    Current topic
     * @return int Database ID if the style to use in current view or false
     */
    private function _get_component_style(midcom_db_topic $topic)
    {
        $_st = false;
        // get user defined style for component
        // style inheritance
        // should this be cached somehow?
        if ($topic->style) {
            $_st = $this->get_style_id_from_path($topic->style);
        } elseif ($inherited = midcom_core_context::get()->parser->get_inherited_style()) {
            // get user defined style inherited from topic tree
            $_st = $this->get_style_id_from_path($inherited);
        } else {
            // Get style from sitewide per-component defaults.
            $styleengine_default_styles = midcom::get()->config->get('styleengine_default_styles');
            if (isset($styleengine_default_styles[$topic->component])) {
                $_st = $this->get_style_id_from_path($styleengine_default_styles[$topic->component]);
            }
        }

        if ($_st) {
            $substyle = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_SUBSTYLE);

            if (is_string($substyle)) {
                $chain = explode('/', $substyle);
                foreach ($chain as $stylename) {
                    if ($_subst_id = $this->get_style_id_from_path($stylename, $_st)) {
                        $_st = $_subst_id;
                    }
                }
            }
        } else {
            $style = $topic->style ?: midcom_core_context::get()->parser->get_inherited_style();
            if (   is_string($style)
                && strpos($style, 'theme:') === 0) {
                $theme_dir = OPENPSA2_THEME_ROOT . midcom::get()->config->get('theme') . '/style';
                $parts = explode('/', str_replace('theme:/', '', $style));

                foreach ($parts as &$part) {
                    $theme_dir = $theme_dir . '/' . $part;
                    $part = $theme_dir;
                }
                foreach (array_reverse(array_filter($parts, 'is_dir')) as $dirname) {
                    $this->prepend_styledir($dirname);
                }
            }
        }
        return $_st;
    }

    /**
     * Gets the component styledir associated with the topic's component.
     *
     * @return mixed the path to the component's style directory.
     */
    private function _get_component_snippetdir()
    {
        // get component's snippetdir (for default styles)
        $loader = midcom::get()->componentloader;
        if (empty($this->_topic->guid)) {
            return null;
        }
        if (!empty($loader->manifests[$this->_topic->component]->extends)) {
            $this->append_component_styledir($loader->manifests[$this->_topic->component]->extends);
        }

        return $loader->path_to_snippetpath($this->_topic->component) . "/style";
    }

    /**
     * Adds an extra style directory to check for style elements at
     * the end of the styledir queue.
     *
     * @param dirname path of style directory within midcom.
     * @throws midcom exception if directory does not exist.
     */
    function append_styledir($dirname)
    {
        if (!file_exists($dirname)) {
            throw new midcom_error("Style directory $dirname does not exist!");
        }
        $this->_styledirs_append[midcom_core_context::get()->id][] = $dirname;
    }

    /**
     * Function prepend styledir
     *
     * @param string $dirname path of styledirectory within midcom.
     * @return boolean true if directory appended
     * @throws midcom_error if directory does not exist.
     */
    function prepend_styledir($dirname)
    {
        if (!file_exists($dirname)) {
            throw new midcom_error("Style directory {$dirname} does not exist.");
        }
        $this->_styledirs_prepend[midcom_core_context::get()->id][] = $dirname;
        return true;
    }

    /**
     * Append the styledir of a component to the queue of styledirs.
     *
     * @param string componentname
     * @throws midcom exception if directory does not exist.
     */
    function append_component_styledir($component)
    {
        $loader = midcom::get()->componentloader;
        $path = $loader->path_to_snippetpath($component) . "/style";
        $this->append_styledir($path);
    }

    /**
     * Prepend the styledir of a component
     *
     * @param string $component component name
     */
    public function prepend_component_styledir($component)
    {
        $loader = midcom::get()->componentloader;
        $path = $loader->path_to_snippetpath($component) . "/style";
        $this->prepend_styledir($path);
    }

    /**
     * Appends a substyle after the currently selected component style.
     *
     * Enables a depth of more than one style during substyle selection.
     *
     * @param string $newsub The substyle to append.
     */
    public function append_substyle($newsub)
    {
        // Make sure try to use only the first argument if we get space separated list, fixes #1788
        if (strpos($newsub, ' ') !== false) {
            $newsub = preg_replace('/^(.+?) .+/', '$1', $newsub);
        }

        $context = midcom_core_context::get();
        $current_style = $context->get_key(MIDCOM_CONTEXT_SUBSTYLE);

        if (strlen($current_style) > 0) {
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

        if (strlen($current_style) > 0) {
            $newsub .= "/" . $current_style;
        }
        debug_add("Updating Component Context Substyle from $current_style to $newsub");

        $context->set_key(MIDCOM_CONTEXT_SUBSTYLE, $newsub);
    }

    /**
     * Merge the prepend and append styles with the componentstyle. This happens when the
     * enter_context function is called.
     * You cannot change the style call stack after that (unless you call enter_context again of course).
     *
     * @param string component style
     */
    private function _merge_styledirs($component_style)
    {
        $current_context = midcom_core_context::get()->id;
        /* first the prepend styles */
        $this->_styledirs[$current_context] = $this->_styledirs_prepend[$current_context];
        /* then the contextstyle */
        $this->_styledirs[$current_context][count($this->_styledirs[$current_context])] = $component_style;

        $this->_styledirs[$current_context] =  array_merge($this->_styledirs[$current_context], $this->_styledirs_append[$current_context]);
    }

    /**
     * Switches the context (see dynamic load). Private variables $_context, $_topic
     * and $_snippetdir are adjusted.
     *
     * @todo check documentation
     * @param int $context    The context to enter
     */
    public function enter_context($context)
    {
        // set new context and topic
        array_unshift($this->_context, $context); // push into context stack

        $this->_topic = midcom_core_context::get($context)->get_key(MIDCOM_CONTEXT_CONTENTTOPIC);

        // Prepare styledir stacks
        if (!isset($this->_styledirs[$context])) {
            $this->_styledirs[$context] = [];
        }
        if (!isset($this->_styledirs_prepend[$context])) {
            $this->_styledirs_prepend[$context] = [];
        }
        if (!isset($this->_styledirs_append[$context])) {
            $this->_styledirs_append[$context] = [];
        }

        if (   $this->_topic
            && $_st = $this->_get_component_style($this->_topic)) {
            array_unshift($this->_scope, $_st);
        }

        $this->_snippetdir = $this->_get_component_snippetdir();

        $this->_merge_styledirs($this->_snippetdir);
    }

    /**
     * Switches the context (see dynamic load). Private variables $_context, $_topic
     * and $_snippetdir are adjusted.
     *
     * @todo check documentation
     */
    public function leave_context()
    {
        if (   $this->_topic
            && $this->_get_component_style($this->_topic)) {
            array_shift($this->_scope);
        }
        array_shift($this->_context);

        $previous_context = (empty($this->_context)) ? 0 : $this->_context[0];
        $this->_topic = midcom_core_context::get($previous_context)->get_key(MIDCOM_CONTEXT_CONTENTTOPIC);

        $this->_snippetdir = $this->_get_component_snippetdir();
    }

    /**
     * Include all text/css attachments of current style to MidCOM headers
     */
    function add_database_head_elements()
    {
        static $called = false;
        if ($called) {
            return;
        }
        $style = $this->get_style();
        $mc = midcom_db_attachment::new_collector('parentguid', $style->guid);
        $mc->add_constraint('mimetype', '=', 'text/css');
        $attachments = $mc->get_values('name');

        foreach ($attachments as $guid => $filename) {
            // TODO: Support media types
            midcom::get()->head->add_stylesheet(midcom_connection::get_url('self') . "midcom-serveattachmentguid-{$guid}/{$filename}");
        }

        $called = true;
    }
}
