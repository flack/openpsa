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
use midcom_core_context;
use midcom_core_urlmethods;
use midcom_connection;
use midcom_error_forbidden;
use midcom_error_notfound;
use Symfony\Component\Routing\Router;

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
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @param string $component
     * @param array $request_switch
     * @return \Symfony\Component\Routing\Router
     */
    public static function get_router($component, array $request_switch = [])
    {
        $loader = new loader;
        if (!empty($request_switch)) {
            return new Router($loader, $request_switch);
        }
        $identifier = str_replace('.', '_', $component);
        return new Router($loader, $component, [
            'cache_dir' => midcom::get()->config->get('cache_base_directory') . 'routing',
            'matcher_cache_class' => "loader__$identifier",
            'generator_cache_class' => "generator__$identifier"
        ]);
    }

    public function process_midcom()
    {
        $context = $this->request->attributes->get('context');

        $urlmethods = new midcom_core_urlmethods($context);
        return $urlmethods->process();
    }

    /**
     * @throws midcom_error_forbidden
     * @throws midcom_error_notfound
     */
    public function process_component()
    {
        $context = $this->request->attributes->get('context');

        $topic = $this->find_topic($context);
        $this->request->attributes->set('argv', $context->parser->argv);

        // Get component interface class
        $component_interface = midcom::get()->componentloader->get_interface_class($topic->component);
        $viewer = $component_interface->get_viewer($topic);

        // Make can_handle check
        $result = $viewer->get_handler($this->request);
        if (!$result) {
            debug_add("Component {$topic->component} in {$topic->name} declared unable to handle request.", MIDCOM_LOG_INFO);

            // We couldn't fetch a node due to access restrictions
            if (midcom_connection::get_error() == MGD_ERR_ACCESS_DENIED) {
                throw new midcom_error_forbidden(midcom::get()->i18n->get_string('access denied', 'midcom'));
            }
            throw new midcom_error_notfound("This page is not available on this server.");
        }
        $context->set_key(MIDCOM_CONTEXT_SHOWCALLBACK, [$viewer, 'show']);

        foreach ($result as $key => $value) {
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
     * @param midcom_core_context $context
     * @throws \midcom_error
     * @return \midcom_db_topic
     */
    private function find_topic(midcom_core_context $context)
    {
        do {
            $topic = $context->parser->get_current_object();
            if (empty($topic)) {
                throw new \midcom_error('Root node missing.');
            }
        } while ($context->parser->get_object() !== false);

        // Initialize context
        $context->set_key(MIDCOM_CONTEXT_ANCHORPREFIX, $context->parser->get_url());
        $context->set_key(MIDCOM_CONTEXT_COMPONENT, $topic->component);
        $context->set_key(MIDCOM_CONTEXT_CONTENTTOPIC, $topic);
        $context->set_key(MIDCOM_CONTEXT_URLTOPICS, $context->parser->get_objects());

        return $topic;
    }
}