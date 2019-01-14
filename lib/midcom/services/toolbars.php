<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * On-site toolbar service.
 *
 * This service manages the toolbars used for the on-site administration system.
 * For each context, it provides the following set of toolbars:
 *
 * 1. The <i>Node</i> toolbar is applicable to the current
 *    node, which is usually a topic. MidCOM places the topic management operations
 *    into this toolbar, where applicable.
 *
 * 2. The <i>View</i> toolbar is applicable to the specific view ("url"). Usually
 *    this maps to a single displayed object (see also the bind_to_object() member
 *    function). MidCOM places the object-specific management operations (like
 *    Metadata controls) into this toolbar, if it is bound to an object. Otherwise,
 *    this toolbar is not touched by MidCOM.
 *
 * It is important to understand that the default toolbars made available through this
 * service are completely specific to a given request context. If you have a dynamic_load
 * running on a given site, it will have its own set of toolbars for each instance.
 *
 * In addition, components may retrieve a third kind of toolbars, which are not under
 * the general control of MidCOM, the <i>Object</i> toolbars. They apply to a single
 * database object (like a bound <i>View</i> toolbar). The usage of this kind of
 * toolbars is completely component-specific.
 *
 * <b>Implementation notes</b>
 *
 * It has yet to prove if the toolbar system is needed for a dynamic_load environments.
 * The main reason for this is that dl'ed stuff is often quite tight in space and thus cannot
 * display any toolbars in a sane way. Usually, the administrative tasks will be bound to the
 * main request.
 *
 * This could be different for portal applications, which display several components on the
 * welcome page, each with its own management options.
 *
 * <b>Configuration</b>
 * See midcom_config for configuration options.
 *
 * @package midcom.services
 */
class midcom_services_toolbars
{
    /**
     * The toolbars currently available.
     *
     * This array is indexed by context id; each value consists of a flat array
     * of two toolbars, the first object being the Node toolbar, the second
     * View toolbar. The toolbars are created on-demand.
     *
     * @var array
     */
    private $_toolbars = [];

    /**
     * midcom.services.toolbars has two modes, it can either display one centralized toolbar
     * for authenticated users, or the node and view toolbars separately for others. This
     * property controls whether centralized mode is enabled.
     *
     * @var boolean
     */
    private $_enable_centralized = false;

    /**
     * Whether we're in centralized mode, i.e. centralized toolbar has been shown
     *
     * @var boolean
     */
    private $_centralized_mode = false;

    /**
     * Simple constructor
     */
    public function __construct()
    {
        static $initialized = false;
        if ($initialized) {
            // This is auth service looping because it instantiates classes for magic privileges!
            return;
        }

        $initialized = true;
        if (   !midcom::get()->auth->user
            || !midcom::get()->config->get('toolbars_enable_centralized')
            || !midcom::get()->auth->can_user_do('midcom:centralized_toolbar', null, __CLASS__)) {
            return;
        }

        if (midcom::get()->auth->can_user_do('midcom:ajax', null, $this)) {
            midcom::get()->head->enable_jquery_ui(['mouse', 'draggable']);

            midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/midcom.services.toolbars/jquery.midcom_services_toolbars.js');

            midcom::get()->head->add_stylesheet(MIDCOM_STATIC_URL . '/midcom.services.toolbars/fancy.css', 'screen');

            $script = "jQuery('body div.midcom_services_toolbars_fancy').midcom_services_toolbar();";
            midcom::get()->head->add_jquery_state_script($script);
        } else {
            $path = midcom::get()->config->get('toolbars_simple_css_path', MIDCOM_STATIC_URL . "/midcom.services.toolbars/simple.css");
            midcom::get()->head->add_stylesheet($path, 'screen');
        }
        midcom::get()->head->add_stylesheet(MIDCOM_STATIC_URL . '/stock-icons/font-awesome-4.7.0/css/font-awesome.min.css');

        // We've included CSS and JS, path is clear for centralized mode
        $this->_enable_centralized = true;
    }

    public function get_class_magic_default_privileges()
    {
        return [
            'EVERYONE' => [],
            'ANONYMOUS' => [],
            'USERS' => []
        ];
    }

    /**
     * Returns the host toolbar of the specified context. The toolbars
     * will be created if this is the first request.
     *
     * @param int $context_id The context to retrieve the node toolbar for, this
     *     defaults to the current context.
     * @return midcom_helper_toolbar_host
     */
    function get_host_toolbar($context_id = null)
    {
        return $this->_get_toolbar($context_id, MIDCOM_TOOLBAR_HOST);
    }

    /**
     * Returns the node toolbar of the specified context. The toolbars
     * will be created if this is the first request.
     *
     * @param int $context_id The context to retrieve the node toolbar for, this
     *     defaults to the current context.
     * @return midcom_helper_toolbar_node
     */
    public function get_node_toolbar($context_id = null)
    {
        return $this->_get_toolbar($context_id, MIDCOM_TOOLBAR_NODE);
    }

    /**
     * Returns the view toolbar of the specified context. The toolbars
     * will be created if this is the first request.
     *
     * @param int $context_id The context to retrieve the view toolbar for, this
     *     defaults to the current context.
     * @return midcom_helper_toolbar_view
     */
    public function get_view_toolbar($context_id = null)
    {
        return $this->_get_toolbar($context_id, MIDCOM_TOOLBAR_VIEW);
    }

    /**
     * Returns the help toolbar of the specified context. The toolbars
     * will be created if this is the first request.
     *
     * @param int $context_id The context to retrieve the help toolbar for, this
     *     defaults to the current context.
     * @return midcom_helper_toolbar_help
     */
    public function get_help_toolbar($context_id = null)
    {
        return $this->_get_toolbar($context_id, MIDCOM_TOOLBAR_HELP);
    }

    /**
     *
     * @param integer $context_id
     * @param string $identifier
     * @return midcom_helper_toolbar
     */
    private function _get_toolbar($context_id, $identifier)
    {
        if ($context_id === null) {
            $context_id = midcom_core_context::get()->id;
        }

        if (!array_key_exists($context_id, $this->_toolbars)) {
            $this->_create_toolbars($context_id);
        }

        return $this->_toolbars[$context_id][$identifier];
    }

    /**
     * Creates the node and view toolbars for a given context ID.
     *
     * @param int $context_id The context ID for which the toolbars should be created.
     */
    private function _create_toolbars($context_id)
    {
        $component = midcom_core_context::get($context_id)->get_key(MIDCOM_CONTEXT_COMPONENT);
        $topic = midcom_core_context::get($context_id)->get_key(MIDCOM_CONTEXT_CONTENTTOPIC);

        $this->_toolbars[$context_id][MIDCOM_TOOLBAR_HELP] = new midcom_helper_toolbar_help($component);
        $this->_toolbars[$context_id][MIDCOM_TOOLBAR_HOST] = new midcom_helper_toolbar_host;
        $this->_toolbars[$context_id][MIDCOM_TOOLBAR_NODE] = new midcom_helper_toolbar_node($topic);
        $this->_toolbars[$context_id][MIDCOM_TOOLBAR_VIEW] = new midcom_helper_toolbar_view;
    }

    /**
     * Add a toolbar
     *
     * @param string $identifier
     * @param midcom_helper_toolbar $toolbar
     * @param int $context_id The context to retrieve the help toolbar for, this
     *     defaults to the current context.
     */
    function add_toolbar($identifier, midcom_helper_toolbar $toolbar, $context_id = null)
    {
        if ($context_id === null) {
            $context_id = midcom_core_context::get()->id;
        }

        $this->_toolbars[$context_id][$identifier] = $toolbar;
    }

    /**
     * Binds the a toolbar to a DBA object. This will append a number of globally available
     * toolbar options. For example, expect Metadata- and Version Control-related options
     * to be added.
     *
     * This call is available through convenience functions throughout the framework: The
     * toolbar main class has a mapping for it (midcom_helper_toolbar::bind_to($object))
     * and object toolbars created by this service will automatically be bound to the
     * specified object.
     *
     * Repeated bind calls are intercepted, you can only bind a toolbar to a single object.
     *
     * @see midcom_helper_toolbar::bind_to()
     * @see create_object_toolbar()
     * @param midcom_helper_toolbar $toolbar
     */
    public function bind_toolbar_to_object(midcom_helper_toolbar $toolbar, $object)
    {
        if (!midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX)) {
            debug_add("Toolbar for object {$object->guid} was called before topic prefix was available, skipping global items.", MIDCOM_LOG_WARN);
            return;
        }
        if (array_key_exists('midcom_services_toolbars_bound_to_object', $toolbar->customdata)) {
            // We already processed this toolbar, skipping further adds.
            return;
        }
        $toolbar->customdata['midcom_services_toolbars_bound_to_object'] = true;

        $reflector = new midcom_helper_reflector($object);
        $toolbar->set_label($reflector->get_class_label());

        $toolbar->bind_object($object);
    }

    /**
     * Renders the specified toolbar for the indicated context.
     *
     * If the toolbar is undefined, an empty string is returned.
     *
     * @param int $toolbar_identifier The toolbar identifier constant (one of
     *     MIDCOM_TOOLBAR_NODE or MIDCOM_TOOLBAR_VIEW etc.)
     * @param int $context_id The context to retrieve the node toolbar for, this
     *     defaults to the current context.
     * @return string The rendered toolbar
     * @see midcom_helper_toolbar::render()
     */
    function _render_toolbar($toolbar_identifier, $context_id = null)
    {
        if ($context_id === null) {
            $context_id = midcom_core_context::get()->id;
        }

        if (!array_key_exists($context_id, $this->_toolbars)) {
            return '';
        }

        return $this->_toolbars[$context_id][$toolbar_identifier]->render();
    }

    /**
     * Renders the node toolbar for the indicated context. If the toolbar is undefined,
     * an empty string is returned. If you want to show the toolbar directly, look for
     * the show_xxx_toolbar methods.
     *
     * @param int $context_id The context to retrieve the node toolbar for, this
     *     defaults to the current context.
     * @return string The rendered toolbar
     * @see midcom_helper_toolbar::render()
     */
    public function render_node_toolbar($context_id = null)
    {
        return $this->_render_toolbar(MIDCOM_TOOLBAR_NODE, $context_id);
    }

    /**
     * Renders the view toolbar for the indicated context. If the toolbar is undefined,
     * an empty string is returned. If you want to show the toolbar directly, look for
     * the show_xxx_toolbar methods.
     *
     * @param int $context_id The context to retrieve the node toolbar for, this
     *     defaults to the current context.
     * @return string The rendered toolbar
     * @see midcom_helper_toolbar::render()
     */
    public function render_view_toolbar($context_id = null)
    {
        return $this->_render_toolbar(MIDCOM_TOOLBAR_VIEW, $context_id);
    }

    /**
     * Renders the host toolbar for the indicated context. If the toolbar is undefined,
     * an empty string is returned. If you want to show the toolbar directly, look for
     * the show_xxx_toolbar methods.
     *
     * @param int $context_id The context to retrieve the node toolbar for, this
     *     defaults to the current context.
     * @return string The rendered toolbar
     * @see midcom_helper_toolbar::render()
     */
    public function render_host_toolbar($context_id = null)
    {
        return $this->_render_toolbar(MIDCOM_TOOLBAR_HOST, $context_id);
    }

    /**
     * Renders the help toolbar for the indicated context. If the toolbar is undefined,
     * an empty string is returned. If you want to show the toolbar directly, look for
     * the show_xxx_toolbar methods.
     *
     * @param int $context_id The context to retrieve the node toolbar for, this
     *     defaults to the current context.
     * @return string The rendered toolbar
     * @see midcom_helper_toolbar::render()
     */
    public function render_help_toolbar($context_id = null)
    {
        return $this->_render_toolbar(MIDCOM_TOOLBAR_HELP, $context_id);
    }

    /**
     * Displays the node toolbar for the indicated context. If the toolbar is undefined,
     * an empty string is returned.
     *
     * @param int $context_id The context to retrieve the node toolbar for, this
     *     defaults to the current context.
     * @see midcom_helper_toolbar::render()
     */
    function show_node_toolbar($context_id = null)
    {
        if (!$this->_centralized_mode) {
            echo $this->render_node_toolbar($context_id);
        }
    }

    /**
     * Displays the host toolbar for the indicated context. If the toolbar is undefined,
     * an empty string is returned.
     *
     * @param int $context_id The context to retrieve the node toolbar for, this
     *     defaults to the current context.
     * @see midcom_helper_toolbar::render()
     */
    function show_host_toolbar($context_id = null)
    {
        if (!$this->_centralized_mode) {
            echo $this->render_host_toolbar($context_id);
        }
    }

    /**
     * Displays the view toolbar for the indicated context. If the toolbar is undefined,
     * an empty string is returned.
     *
     * @param int $context_id The context to retrieve the node toolbar for, this
     *     defaults to the current context.
     * @see midcom_helper_toolbar::render()
     */
    public function show_view_toolbar($context_id = null)
    {
        if (!$this->_centralized_mode) {
            echo $this->render_view_toolbar($context_id);
        }
    }

    /**
     * Displays the help toolbar for the indicated context. If the toolbar is undefined,
     * an empty string is returned.
     *
     * @param int $context_id The context to retrieve the node toolbar for, this
     *     defaults to the current context.
     * @see midcom_helper_toolbar::render()
     */
    function show_help_toolbar($context_id = null)
    {
        if (!$this->_centralized_mode) {
            echo $this->render_help_toolbar($context_id);
        }
    }

    /**
     * Displays the combined MidCOM toolbar system
     *
     * @param int $context_id The context to retrieve the node toolbar for, this
     *     defaults to the current context.
     * @see midcom_helper_toolbar::render()
     */
    public function show($context_id = null)
    {
        if (!$this->_enable_centralized) {
            return;
        }

        if (null === $context_id) {
            $context_id = midcom_core_context::get()->id;
        }

        $this->_centralized_mode = true;

        $enable_drag = false;
        $toolbar_style = "";
        $toolbar_class = "midcom_services_toolbars_simple";

        if (midcom::get()->auth->can_user_do('midcom:ajax', null, midcom_services_toolbars::class)) {
            $enable_drag = true;
            $toolbar_class = "midcom_services_toolbars_fancy";
            $toolbar_style = "display: none;";
        }

        echo "<div class=\"{$toolbar_class}\" style=\"{$toolbar_style}\">\n";
        echo "    <div class=\"minimizer\">\n";
        echo "    </div>\n";
        echo "    <div class=\"items\">\n";

        foreach (array_reverse($this->_toolbars[$context_id], true) as $identifier => $toolbar) {
            if (count($toolbar->items) == 0) {
                continue;
            }
            switch ($identifier) {
                case MIDCOM_TOOLBAR_VIEW:
                    $id = $class = 'page';
                    break;
                case MIDCOM_TOOLBAR_NODE:
                    $id = $class = 'folder';
                    break;
                case MIDCOM_TOOLBAR_HOST:
                    $id = $class = 'host';
                    break;
                case MIDCOM_TOOLBAR_HELP:
                    $id = $class = 'help';
                    break;
                default:
                    $id = 'custom-' . $identifier;
                    $class = 'custom';
                    break;
            }
            echo "        <div id=\"midcom_services_toolbars_topic-{$id}\" class=\"item\">\n";
            echo "            <span class=\"midcom_services_toolbars_topic_title {$class}\">" . $toolbar->get_label() . "</span>\n";
            echo $toolbar->render();
            echo "        </div>\n";
        }
        echo "</div>\n";

        if ($enable_drag) {
            echo "     <div class=\"dragbar\"></div>\n";
        }
        echo "</div>\n";
    }
}
