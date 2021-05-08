<?php
/**
 * @package midcom.baseclasses
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Symfony\Component\Routing\Router;

/**
 * Execution handler subclass, to be used with the request switch
 * in midcom_baseclasses_components_viewer.
 *
 * Use the various event handlers to customize startup.
 *
 * The basic idea is that you have separate instances of this type for the various
 * operations in your main viewer class. This avoids cluttering up the viewer class
 * and gives you better maintainability due to smaller code files.
 *
 * Under normal operation within the same component you don't need to think about any
 * specialties, the member variables are just references to the main request class
 * (also known as "viewer class").
 *
 * Noteworthy is the ability to export handlers for usage in other components in
 * both libraries and full components. To make the exported handler work correctly,
 * you need to set $this->_component to the corresponding value of the <i>exporting</i>
 * component. In this case, the startup code will take the main l10n instance, the
 * component data storage and the configuration <i>from the exporting component.</i>
 * The configuration in this case is merged from the global defaults (constructed
 * during component/library startup) and the configuration parameters set on the topic
 * <i>where it is invoked.</i>
 *
 * Note, that the export "mode" is only invoked <i>if and only if</i> the component of
 * the handler is <i>different</i> of the component of the main request class.
 *
 * @package midcom.baseclasses
 */
abstract class midcom_baseclasses_components_handler
{
    use midcom_baseclasses_components_base;

    /**
     * The topic for which we are handling a request.
     *
     * @var midcom_db_topic
     */
    var $_topic;

    /**
     * Request specific data storage area. Registered in the component context
     * as ''.
     *
     * @var array
     */
    var $_request_data = [];

    /**
     * The request class that has invoked this handler instance.
     *
     * @deprecated
     * @var midcom_baseclasses_components_viewer
     */
    var $_master;

    /**
     * The node toolbar for the current request context. Becomes available in the handle
     * phase.
     *
     * @var midcom_helper_toolbar
     * @see midcom_services_toolbars
     */
    var $_node_toolbar;

    /**
     * The view toolbar for the current request context. Becomes available in the handle
     * phase.
     *
     * @var midcom_helper_toolbar
     * @see midcom_services_toolbars
     */
    var $_view_toolbar;

    /**
     * @var Router
     */
    protected $router;

    /**
     * Holds breadcrumb entries the handler wants to add
     *
     * @var array
     */
    private $_breadcrumbs = [];

    /**
     * Initializes the request handler class, called by the component interface after
     * instantiation.
     *
     * Be aware that it is possible that a handler can come from a different component
     * (or library) than the master class. Take this into account when getting the
     * component data storage, configuration and l10n instances. Configuration is merged
     * during runtime based on the system defaults and all parameters attached to the
     * topic <i>we're currently operating on.</i>
     */
    public function initialize(midcom_baseclasses_components_viewer $master, Router $router)
    {
        $this->_master = $master;
        $this->router = $router;

        $this->_request_data =& $master->_request_data;
        $this->_topic = $master->_topic;

        // Load component specific stuff, special treatment if the handler has
        // a component different than the master handler set.
        if (   $this->_component
            && $this->_component != $master->_component) {
            $this->_config->store_from_object($this->_topic, $this->_component, true);
        } else {
            $this->_component = $master->_component;
        }
        $this->_on_initialize();
    }

    /**
     * Initialization event handler, called at the end of the initialization process.
     * Use this for all initialization work you need, as the component state is already
     * populated when this event handler is called.
     */
    public function _on_initialize()
    {
    }

    /**
     * Generates a response with a given style element
     */
    public function show(string $element, string $root = 'ROOT') : midcom_response_styled
    {
        $context = midcom_core_context::get();
        $context->set_key(MIDCOM_CONTEXT_SHOWCALLBACK, function() use ($element) {
            midcom::get()->style->show($element);
        });
        $this->populate_breadcrumb_line();
        return new midcom_response_styled($context, $root);
    }

    /**
     * Registers a new breadcrumb entry
     */
    public function add_breadcrumb(string $url, string $title)
    {
        $this->_breadcrumbs[] = [
            MIDCOM_NAV_URL => $url,
            MIDCOM_NAV_NAME => $title,
        ];
    }

    /**
     * Adds the registered breadcrumb entries to context_data
     */
    public function populate_breadcrumb_line()
    {
        if ($this->_breadcrumbs) {
            midcom_core_context::get()->set_custom_key('midcom.helper.nav.breadcrumb', $this->_breadcrumbs);
        }
    }

    /**
     * Binds the current page view to a particular object. This will automatically connect such things like
     * metadata and toolbars to the correct object.
     *
     * @param string $page_class String describing page type, will be used for substyling
     */
    protected function bind_view_to_object(midcom_core_dbaobject $object, string $page_class = 'default')
    {
        // Bind the object into the view toolbar
        $this->_view_toolbar->bind_to($object);

        $metadata = midcom::get()->metadata;
        // Bind the object to the metadata service
        $metadata->bind_to($object);

        // Push the object's CSS classes to metadata service
        $page_class = $metadata->get_object_classes($object, $page_class);
        $metadata->set_page_class($page_class);

        midcom::get()->style->append_substyle($page_class);
    }
}
