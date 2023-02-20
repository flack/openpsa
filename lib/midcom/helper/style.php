<?php
/**
 * @package midcom.helper
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\templating\loader;

/**
 * This class is responsible for all style management. It is
 * accessible through the midcom::get()->style object.
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
 * @package midcom.helper
 */
class midcom_helper_style
{
    /**
     * Current topic
     * 
     * @var midcom_db_topic
     */
    private $_topic;

    /**
     * Context stack
     *
     * @var midcom_core_context[]
     */
    private $_context = [];

    /**
     * List of styledirs to handle after componentstyle
     */
    private array $_styledirs_append = [];

    /**
     * List of styledirs to handle before componentstyle
     */
    private array $_styledirs_prepend = [];

    private loader $loader;

    /**
     * Data to pass to the style
     */
    public array $data;

    public function __construct(loader $loader)
    {
        $this->loader = $loader;
    }

    /**
     * Looks for a style element matching $path (either in a user defined style
     * or the default style snippetdir) and displays/evaluates it.
     */
    public function show(string $path) : bool
    {
        if ($this->_context === []) {
            debug_add("Trying to show '{$path}' but there is no context set", MIDCOM_LOG_INFO);
            return false;
        }

        $style = $this->loader->get_element($path, true);

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

        // Resolve variables
        $preparsed = midcom_helper_formatter::compile($style);

        if (midcom_core_context::get()->has_custom_key('request_data')) {
            $data =& midcom_core_context::get()->get_custom_key('request_data');
        }

        try {
            eval('?>' . $preparsed);
        } catch (ParseError $e) {
            throw new midcom_error("Failed to parse style element '{$path}': " . $e->getMessage() . ' in line ' . $e->getLine());
        }
    }

    /**
     * Looks for a midcom core style element matching $path and displays/evaluates it.
     * This offers a bit reduced functionality and will only look in the DB root style,
     * the theme directory and midcom's style directory, because it has to work even when
     * midcom is not yet fully initialized
     */
    public function show_midcom(string $path) : bool
    {
        $loader = clone $this->loader;
        $loader->set_directories(null, [MIDCOM_ROOT . '/midcom/style'], []);

        $_style = $loader->get_element($path, false);

        if ($_style !== null) {
            $this->render($_style, $path);
            return true;
        }
        debug_add("The element '{$path}' could not be found.", MIDCOM_LOG_INFO);
        return false;
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
        if (str_contains($newsub, ' ')) {
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
     */
    public function enter_context(midcom_core_context $context)
    {
        // set new context and topic
        array_unshift($this->_context, $context); // push into context stack

        if ($this->_topic = $context->get_key(MIDCOM_CONTEXT_CONTENTTOPIC)) {
            $style = $this->_topic->style ?: $context->get_inherited_style();
            if (!$style) {
                $styleengine_default_styles = midcom::get()->config->get('styleengine_default_styles');
                if (isset($styleengine_default_styles[$this->_topic->component])) {
                    $style = $styleengine_default_styles[$this->_topic->component];
                }
            }
            $this->loader->initialize($context, $style);
        }

        $this->loader->set_directories(
            $this->_topic,
            $this->_styledirs_prepend[$context->id] ?? [],
            $this->_styledirs_append[$context->id] ?? []
        );
    }

    /**
     * Switches the context (see dynamic load). Private variables $_context, $_topic
     * and $loader are adjusted.
     */
    public function leave_context()
    {
        array_shift($this->_context);
        $previous_context = $this->_context[0] ?? midcom_core_context::get();

        $this->_topic = $previous_context->get_key(MIDCOM_CONTEXT_CONTENTTOPIC);
        $this->loader->set_directories(
            $this->_topic,
            $this->_styledirs_prepend[$previous_context->id] ?? [],
            $this->_styledirs_append[$previous_context->id] ?? []
        );
    }
}
