<?php
/**
 * @package midcom.routing
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace midcom\httpkernel;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use midcom;
use midcom_connection;
use midcom_error_forbidden;
use midcom_error_notfound;
use midcom_response_styled;
use midcom_baseclasses_components_handler;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\FilterControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @package midcom.httpkernel
 */
class kernel implements EventSubscriberInterface
{
    private static $kernel;

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['on_request'],
            KernelEvents::CONTROLLER_ARGUMENTS => ['on_arguments'],
            KernelEvents::VIEW => ['on_view'],
            KernelEvents::RESPONSE => ['on_response']
        ];
    }

    /**
     * @return HttpKernel
     */
    public static function get()
    {
        if (self::$kernel === null) {
            midcom::get()->dispatcher->addSubscriber(new static);
            $c_resolver = new ControllerResolver;
            $a_resolver = new ArgumentResolver;
            self::$kernel = new HttpKernel(midcom::get()->dispatcher, $c_resolver, new RequestStack, $a_resolver);
        }
        return self::$kernel;
    }

    public function on_request(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $context = $request->attributes->get('context');
        $topic = $this->find_topic($context);

        $request->attributes->set('argv', $context->parser->argv);

        // Get component interface class
        $component_interface = midcom::get()->componentloader->get_interface_class($topic->component);
        $viewer = $component_interface->get_viewer($topic);

        // Make can_handle check
        $result = $viewer->get_handler($request);
        if (!$result) {
            debug_add("Component {$topic->component} in {$topic->name} declared unable to handle request.", MIDCOM_LOG_INFO);

            // We couldn't fetch a node due to access restrictions
            if (midcom_connection::get_error() == MGD_ERR_ACCESS_DENIED) {
                throw new midcom_error_forbidden(midcom::get()->i18n->get_string('access denied', 'midcom'));
            }
            throw new midcom_error_notfound("This page is not available on this server.");
        }

        foreach ($result as $key => $value) {
            if ($key === 'handler') {
                $key = '_controller';
                $value[1] = '_handler_' . $value[1];
            } elseif ($key === '_route') {
                $key = 'handler_id';
            }
            $request->attributes->set($key, $value);
        }
        $request->attributes->set('data', '__request_data__');

        $context->set_key(MIDCOM_CONTEXT_SHOWCALLBACK, [$viewer, 'show']);
        $viewer->handle();
    }

    /**
     * @param \midcom_core_context $context
     * @throws \midcom_error
     * @return \midcom_db_topic
     */
    private function find_topic(\midcom_core_context $context)
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

    public function on_arguments(FilterControllerArgumentsEvent $event)
    {
        $arguments = $event->getArguments();
        foreach ($arguments as $i => $argument) {
            if ($argument === '__request_data__') {
                $context = $event->getRequest()->attributes->get('context');
                $arguments[$i] =& $context->get_custom_key('request_data');
            }
        }
        $event->setArguments($arguments);
    }

    public function on_view(GetResponseForControllerResultEvent $event)
    {
        $event->setResponse(new midcom_response_styled($event->getRequest()->attributes->get('context')));
    }

    public function on_response(FilterResponseEvent $event)
    {
        $controller = $event->getRequest()->attributes->get('_controller');
        if ($controller[0] instanceof midcom_baseclasses_components_handler) {
            $controller[0]->populate_breadcrumb_line();
        }
    }
}