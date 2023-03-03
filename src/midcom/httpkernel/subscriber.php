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
use midcom\routing\resolver;
use Symfony\Component\HttpFoundation\Request;
use midcom;
use midcom_connection;
use midcom_core_context;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

/**
 * @package midcom.httpkernel
 */
class subscriber implements EventSubscriberInterface
{
    private bool $initialized = false;

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['on_request'],
            KernelEvents::CONTROLLER => ['on_controller'],
            KernelEvents::CONTROLLER_ARGUMENTS => ['on_arguments'],
            KernelEvents::VIEW => ['on_view'],
            KernelEvents::EXCEPTION => ['on_exception'],
            KernelEvents::TERMINATE => ['on_terminate']
        ];
    }

    private function initialize(Request $request)
    {
        $midcom = midcom::get();
        $midcom->debug->log("Start of MidCOM run " . $request->server->get('REQUEST_URI', ''));
        $request->setSession($midcom->session);
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

    public function on_request(RequestEvent $event)
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

    public function on_controller(ControllerEvent $event)
    {
        $request = $event->getRequest();
        $attributes = $request->attributes->all();
        if (!empty($attributes['__viewer'])) {
            $controller = $event->getController();
            $attributes['__viewer']->prepare_handler($controller[0], $attributes);
            unset($attributes['__viewer']);
            $request->attributes->set('handler_id', $attributes['_route']);
        }
    }

    public function on_arguments(ControllerArgumentsEvent $event)
    {
        $arguments = $event->getArguments();
        $i = array_search('__request_data__', $arguments, true);
        if ($i !== false) {
            $context = $event->getRequest()->attributes->get('context');
            $arguments[$i] =& $context->get_custom_key('request_data');
            $event->setArguments($arguments);
        }
    }

    public function on_view(ViewEvent $event)
    {
        $attributes = $event->getRequest()->attributes;
        $controller = $attributes->get('_controller');
        if ($controller[0] instanceof midcom_baseclasses_components_handler) {
            $controller[0]->populate_breadcrumb_line();
        }
        $event->setResponse(new midcom_response_styled($attributes->get('context')));
    }

    public function on_exception(ExceptionEvent $event)
    {
        $event->allowCustomResponseCode();
        $handler = new \midcom_exception_handler($event->getThrowable());
        $event->setResponse($handler->render());
    }

    public function on_terminate(TerminateEvent $event)
    {
        debug_add("End of MidCOM run: " . $event->getRequest()->server->get('REQUEST_URI'));
    }
}