<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class midcom_services_cache_module_contentTest extends openpsa_testcase
{
    public function test_on_request()
    {
        $module = new midcom_services_cache_module_content;
        $module->initialize();
        $module->uncached(false);

        $request = Request::create('/');
        $ctx = midcom_core_context::enter('/');
        $request->attributes->set('context', $ctx);
        $event = new GetResponseEvent($GLOBALS['kernel'], $request, KernelInterface::MASTER_REQUEST);

        $module->on_request($event);
        $this->assertFalse($event->hasResponse(), 'Response should not be cached yet');

        // write response to cache
        $response = Response::create('test');
        $filter_event = new FilterResponseEvent($GLOBALS['kernel'], $request, KernelInterface::MASTER_REQUEST, $response);
        $module->on_response($filter_event);

        $module->on_request($event);
        $this->assertTrue($event->hasResponse(), 'Response should be cached');
        midcom_core_context::leave();

        // same url, but with GET params this time
        $request = Request::create('/', 'GET', ['test' => 'test']);
        $ctx = midcom_core_context::enter('/');
        $request->attributes->set('context', $ctx);
        $event = new GetResponseEvent($GLOBALS['kernel'], $request, KernelInterface::MASTER_REQUEST);

        $module->on_request($event);
        $this->assertFalse($event->hasResponse(), 'Response should not be cached yet');
    }
}
