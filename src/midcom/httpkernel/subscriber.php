<?php
/**
 * @package midcom.routing
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace midcom\httpkernel;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use midcom_response_styled;
use midcom_baseclasses_components_handler;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\FilterControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use midcom\routing\resolver;
use Symfony\Component\HttpFoundation\Request;
use midcom;
use midcom_connection;
use midcom_core_context;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 * @package midcom.httpkernel
 */
class subscriber implements EventSubscriberInterface
{
    private $initialized = false;

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['on_request'],
            KernelEvents::CONTROLLER_ARGUMENTS => ['on_arguments'],
            KernelEvents::VIEW => ['on_view'],
            KernelEvents::RESPONSE => ['on_response'],
            KernelEvents::EXCEPTION => ['on_exception']
        ];
    }

    private function initialize(Request $request)
    {
        $midcom = midcom::get();
        $midcom->debug->log("Start of MidCOM run " . $request->server->get('REQUEST_URI', ''));
        $request->setSession($midcom->session);
        $midcom->componentloader->load_all_manifests();
        if ($response = $midcom->auth->check_for_login_session($request)) {
            return $response;
        }

        // This checks for the unittest case
        if (!$request->attributes->has('context')) {
            // Initialize Context Storage
            $context = midcom_core_context::enter(midcom_connection::get_url('uri'));
            $request->attributes->set('context', $context);
        }
        // Initialize the UI message stack from session
        $midcom->uimessages->initialize($request);

        $midcom->dispatcher->addListener(KernelEvents::REQUEST, [$midcom->cache->content, 'on_request'], 10);
        $midcom->dispatcher->addListener(KernelEvents::RESPONSE, [$midcom->cache->content, 'on_response'], -10);
    }

    /**
     * @param GetResponseEvent $event
     */
    public function on_request(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        if (!$this->initialized) {
            $this->initialized = true;
            if ($response = $this->initialize($request)) {
                $event->setResponse($response);
                return;
            }
        }

        $resolver = new resolver($request);

        if ($resolver->process_midcom()) {
            return;
        }

        $resolver->process_component();
        $request->attributes->set('data', '__request_data__');
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
        $attributes = $event->getRequest()->attributes;
        $controller = $attributes->get('_controller');
        if ($controller[0] instanceof midcom_baseclasses_components_handler) {
            $controller[0]->populate_breadcrumb_line();
        }
        $event->setResponse(new midcom_response_styled($attributes->get('context')));
    }

    public function on_response(FilterResponseEvent $event)
    {
        if ($event->isMasterRequest()) {
            $response = $event->getResponse();
            if ($response instanceof StreamedResponse) {
                // if we have a streamed response, we need to send right away
                // otherwise exceptions in the callback won't be caught by the kernel
                // which means e.g. that a midcom-exec script couldn't show a login screen
                $response->send();
            }
        }
    }

    public function on_exception(GetResponseForExceptionEvent $event)
    {
        $handler = new \midcom_exception_handler();
        $event->setResponse($handler->render($event->getException()));
    }
}