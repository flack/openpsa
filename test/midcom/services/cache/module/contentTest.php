<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class midcom_services_cache_module_contentTest extends TestCase
{
    public function test_on_request()
    {
        $config = new midcom_config;
        $config->set('cache_module_content_headers_strategy', 'revalidate');

        $module = new midcom_services_cache_module_content($config, new ArrayAdapter, new ArrayAdapter);
        $module->uncached(false);

        $request = Request::create('/');
        $ctx = midcom_core_context::enter('/');
        $request->attributes->set('context', $ctx);
        $event = new RequestEvent(midcom::get(), $request, KernelInterface::MASTER_REQUEST);

        $module->on_request($event);
        $this->assertFalse($event->hasResponse(), 'Response should not be cached yet');

        // write response to cache
        $response = new Response('test');
        $filter_event = new ResponseEvent(midcom::get(), $request, KernelInterface::MASTER_REQUEST, $response);
        $module->on_response($filter_event);

        $module->on_request($event);
        $this->assertTrue($event->hasResponse(), 'Response should be cached');
        midcom_core_context::leave();

        // same url, but with GET params this time
        $request = Request::create('/', 'GET', ['test' => 'test']);
        $ctx = midcom_core_context::enter('/');
        $request->attributes->set('context', $ctx);
        $event = new RequestEvent(midcom::get(), $request, KernelInterface::MASTER_REQUEST);

        $module->on_request($event);
        $this->assertFalse($event->hasResponse(), 'Response should not be cached yet');
    }

    public function test_store_dl_content()
    {
        $config = new midcom_config;
        $config->set('cache_module_content_headers_strategy', 'revalidate');

        $backend = new ArrayAdapter;
        $data_cache = new ArrayAdapter;

        $request = Request::create('/');
        $ctx = midcom_core_context::enter('/');
        $request->attributes->set('context', $ctx);

        $module = new midcom_services_cache_module_content($config, $backend, $data_cache);
        $module->uncached(false);
        $module->register('1111111111111111111111111');

        $module->store_dl_content($ctx->id, 'test', $request);

        $backend_values = $backend->getValues();
        $this->assertCount(2, $backend_values);
        $this->assertArrayHasKey('1111111111111111111111111', $backend_values);

        $data_values = $data_cache->getValues();
        $this->assertCount(1, $data_values);
        $dl_content_id = unserialize(current($backend_values));
        $this->assertArrayHasKey($dl_content_id, $data_values);
        $this->assertEquals('test', unserialize($data_values[$dl_content_id]));
    }
}
