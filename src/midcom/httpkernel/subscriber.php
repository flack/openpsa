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
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use midcom\routing\resolver;

/**
 * @package midcom.httpkernel
 */
class subscriber implements EventSubscriberInterface
{
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
     * @param GetResponseEvent $event
     */
    public function on_request(GetResponseEvent $event)
    {
        $request = $event->getRequest();
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