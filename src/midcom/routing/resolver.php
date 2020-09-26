<?php
/**
 * @package midcom.routing
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace midcom\routing;

use Symfony\Component\HttpFoundation\Request;
use midcom;
use midcom_error_forbidden;
use midcom_error_notfound;
use midcom_baseclasses_components_viewer;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * @package midcom.routing
 */
class resolver
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var parser
     */
    private $parser;

    /**
     * @var \midcom_core_context
     */
    private $context;

    /**
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->context = $request->attributes->get('context');
        $this->parser = new parser($this->context);
    }

    public static function get_router(string $component, array $request_switch = []) : Router
    {
        $loader = new loader;
        if (!empty($request_switch)) {
            return new Router($loader, $request_switch);
        }
        $identifier = str_replace('.', '_', $component);
        return new Router($loader, $component, [
            'cache_dir' => midcom::get()->getCacheDir() . '/routing/' . $identifier,
        ]);
    }

    /**
     * @throws midcom_error_notfound
     */
    public function process_midcom() : bool
    {
        if ($url = $this->parser->find_urlmethod()) {
            $router = self::get_router('midcom');
            $router->getContext()
                ->fromRequest($this->request);

            try {
                $result = $router->match($url);
            } catch (ResourceNotFoundException $e) {
                throw new midcom_error_notfound('This URL method is unknown.');
            }
            $this->request->attributes->add($result);

            return true;
        }
        return false;
    }

    /**
     * Basically this method will parse the URL and search for a component that can
     * handle the request. If one is found, it will process the request, if not, it
     * will report an error, depending on the situation.
     *
     * Details: The logic will traverse the node tree, and for the last node it will load
     * the component that is responsible for it. This component gets the chance to
     * accept the request, which is basically a call to can_handle. If the component
     * declares to be able to handle the call, its handle function is executed. Depending
     * if the handle was successful or not, it will either display an HTTP error page or
     * prepares the content handler to display the content later on.
     *
     * If the parsing process doesn't find any component that declares to be able to
     * handle the request, an HTTP 404 - Not Found error is triggered.
     *
     * @throws midcom_error_forbidden
     * @throws midcom_error_notfound
     */
    public function process_component()
    {
        $topic = $this->parser->find_topic();
        $this->request->attributes->set('argv', $this->parser->argv);

        // Get component interface class
        $component_interface = midcom::get()->componentloader->get_interface_class($topic->component);
        $viewer = $component_interface->get_viewer($topic);

        // Make can_handle check
        $parameters = $this->get_parameters($viewer);
        $viewer->prepare_handler($parameters);

        $this->context->set_key(MIDCOM_CONTEXT_SHOWCALLBACK, [$viewer, 'show']);

        foreach ($parameters as $key => $value) {
            if ($key === 'handler') {
                $key = '_controller';
                $value[1] = '_handler_' . $value[1];
            } elseif ($key === '_route') {
                $key = 'handler_id';
            }
            $this->request->attributes->set($key, $value);
        }
        $viewer->handle();
    }

    /**
     * Checks against all registered handlers if a valid one can be found.
     */
    private function get_parameters(midcom_baseclasses_components_viewer $viewer) : array
    {
        $argv = $this->request->attributes->get('argv', []);
        $prefix = $this->context->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);

        // Check if we need to start up a plugin.
        if (   count($argv) > 1
            && $config = plugin::get_config($argv[0], $argv[1])) {
            $namespace = array_shift($argv);
            $name = array_shift($argv);
            $prefix .= $namespace . '/' . $name . '/';
            debug_add("Loading the plugin {$namespace}/{$name}");
            $viewer->load_plugin($name, new $config['class'], $config);
        }

        $url = '/';
        if (!empty($argv)) {
            $url .= implode('/', $argv) . '/';
        }
        $router = $viewer->get_router();
        $router->getContext()
            ->fromRequest($this->request)
            ->setBaseUrl(substr($prefix, 0, -1));

        try {
            $result = $router->match($url);
        } catch (ResourceNotFoundException $e) {
            // No match
            debug_add("Component {$viewer->_component} in {$viewer->_topic->name} declared unable to handle request.", MIDCOM_LOG_INFO);
            throw new midcom_error_notfound("This page is not available on this server.");
        }

        $result['args'] = array_values(array_filter($result, function($name) {
            return !str_starts_with($name, '_');
        }, ARRAY_FILTER_USE_KEY));

        return $result;
    }
}