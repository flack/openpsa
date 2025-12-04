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
use midcom;
use midcom_connection;
use midcom_core_context;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * @package midcom.httpkernel
 */
class subscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents() : array
    {
        return [
            KernelEvents::REQUEST => ['on_request'],
            KernelEvents::CONTROLLER => ['on_controller'],
            KernelEvents::CONTROLLER_ARGUMENTS => ['on_arguments'],
            KernelEvents::VIEW => ['on_view'],
            KernelEvents::RESPONSE => ['on_response']
        ];
    }

    public function on_request(RequestEvent $event)
    {
        $request = $event->getRequest();
        if ($event->isMainRequest()) {
            // Initialize Context Storage
            $context = midcom_core_context::enter(midcom_connection::get_url('uri'));
            $request->attributes->set('context', $context);

            // Initialize the UI message stack from session
            midcom::get()->uimessages->initialize($request);
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
            $request->attributes->set('__handler', $controller);
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
        $controller = $attributes->get('__handler');
        if ($controller[0] instanceof midcom_baseclasses_components_handler) {
            $controller[0]->populate_breadcrumb_line();
        }
        $event->setResponse(new midcom_response_styled($attributes->get('context')));
    }

    public function on_response(ResponseEvent $event)
    {
        if ($event->isMainRequest()) {
            $response = $event->getResponse();
            if ($response instanceof StreamedResponse) {
                // if we have a streamed response, we need to send right away
                // otherwise exceptions in the callback won't be caught by the kernel
                // which means e.g. that a midcom-exec script couldn't show a login screen
                $response->send();
            }
        }
    }
}