<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\midcom\core;

use openpsa_testcase;
use midcom;
use midcom_core_context;
use midcom_core_urlmethods;
use midcom_db_attachment;
use midcom_db_topic;
use midcom_error_forbidden;
use midcom_error_notfound;
use midcom_response_login;
use midcom_response_relocate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class urlmethodsTest extends openpsa_testcase
{
    public function test_logout()
    {
        $topic = new midcom_db_topic;
        $topic->component = 'midcom.core.nullcomponent';
        $url = $this->run_relocate_handler($topic, 'midcom-logout-https://some-website.com/');
        $this->assertEquals('https://some-website.com/', $url);
    }

    public function test_logout_with_query_string()
    {
        $this->create_user(true);
        $request = Request::create('/midcom-logout-/target/?foo=bar');

        $response = (new midcom_core_urlmethods)->process_logout($request, '/target/');
        $this->assertInstanceOf(midcom_response_relocate::class, $response);
        $this->assertEquals('/target/?foo=bar', $response->getTargetUrl());
        $this->assertFalse(midcom::get()->auth->is_valid_user());
    }

    public function test_login_anonymous()
    {
        $response = (new midcom_core_urlmethods)->process_login(Request::create('/midcom-login-/target/'), '/target/');
        $this->assertInstanceOf(midcom_response_login::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function test_login_valid_user()
    {
        $this->create_user(true);

        $response = (new midcom_core_urlmethods)->process_login(Request::create('/midcom-login-/target/'), '/target/');
        $this->assertInstanceOf(midcom_response_relocate::class, $response);
        $this->assertEquals('/target/', $response->getTargetUrl());
    }

    public function test_permalink()
    {
        $topic = $this->get_component_node('net.nehmer.static');
        $child = $this->create_object(midcom_db_topic::class, [
            'up' => $topic->id,
            'name' => uniqid('permalink'),
            'component' => 'net.nehmer.static'
        ]);

        $url = $this->run_relocate_handler($topic, 'midcom-permalink-' . $child->guid);
        $this->assertStringEndsWith('/' . $child->name . '/', $url);
    }

    public function test_permalink_unknown()
    {
        $this->expectException(midcom_error_notfound::class);
        (new midcom_core_urlmethods)->process_permalink('1' . str_repeat('0', 31));
    }

    public function test_unlock()
    {
        $topic = $this->create_object(midcom_db_topic::class);
        midcom::get()->auth->request_sudo('midcom.core');
        $this->assertTrue($topic->lock());

        $response = (new midcom_core_urlmethods)->process_unlock($topic->guid);
        midcom::get()->auth->drop_sudo();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(['success' => true], json_decode($response->getContent(), true));
        $topic->refresh();
        $this->assertFalse($topic->is_locked());
    }

    public function test_invalidate_cache()
    {
        $request = Request::create('/midcom-cache-invalidate', server: [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_REFERER' => 'http://localhost/'
        ]);

        $response = (new midcom_core_urlmethods)->invalidate_cache($request);
        // live mode closes all output buffers, so we restore PHPUnit's
        ob_start();
        $this->assertInstanceOf(midcom_response_relocate::class, $response);
        $this->assertEquals('http://localhost/', $response->getTargetUrl());
    }

    public function test_invalidate_cache_denied()
    {
        $request = Request::create('/midcom-cache-invalidate', server: ['REMOTE_ADDR' => '123.45.6.7']);

        $this->expectException(midcom_error_forbidden::class);
        (new midcom_core_urlmethods)->invalidate_cache($request);
    }

    public function test_invalidate_cache_admin()
    {
        $this->create_user(true);
        midcom::get()->auth->request_sudo('midcom.core');
        $request = Request::create('/midcom-cache-invalidate', server: ['REMOTE_ADDR' => '123.45.6.7']);

        $response = (new midcom_core_urlmethods)->invalidate_cache($request);
        ob_start();
        midcom::get()->auth->drop_sudo();
        $this->assertInstanceOf(midcom_response_relocate::class, $response);
    }

    public function test_exec()
    {
        $context = midcom_core_context::enter('/');
        $request = Request::create('/midcom-exec-org.openpsa.mail/test.php');
        $request->attributes->set('context', $context);

        $response = (new midcom_core_urlmethods)->process_exec($request, 'org.openpsa.mail', 'test.php', '');
        midcom_core_context::leave();

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertEquals('org.openpsa.mail', $context->get_key(MIDCOM_CONTEXT_COMPONENT));
    }

    public function test_exec_notfound()
    {
        $this->expectException(midcom_error_notfound::class);
        (new midcom_core_urlmethods)->process_exec(Request::create('/'), 'midcom', 'nonexistent.php', '');
    }

    public function test_config()
    {
        $request = Request::create('/midcom-config-test');
        $response = (new midcom_core_urlmethods)->process_config($request);
        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertSame($request, midcom::get()->style->data['request']);
    }

    public function test_serveattachmentguid()
    {
        $topic = $this->create_object(midcom_db_topic::class);
        $attachment = $this->create_object(midcom_db_attachment::class, [
            'parentguid' => $topic->guid,
            'name' => 'attach.png',
            'mimetype' => 'image/png'
        ]);
        midcom::get()->auth->request_sudo('midcom.core');
        $this->assertTrue($attachment->copy_from_file(dirname(__DIR__) . '/db/__files/attach.png'));
        midcom::get()->auth->drop_sudo();

        $urlmethods = new midcom_core_urlmethods;
        $response = $urlmethods->process_serveattachmentguid(Request::create('/'), $attachment->guid);
        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('image/png', $response->headers->get('Content-Type'));
        $etag = $response->getEtag();
        $this->assertNotEmpty($etag);

        $request = Request::create('/', server: ['HTTP_IF_NONE_MATCH' => $etag]);
        $response = $urlmethods->process_serveattachmentguid($request, $attachment->guid);
        $this->assertEquals(Response::HTTP_NOT_MODIFIED, $response->getStatusCode());
    }

    public function test_serveattachmentguid_denied()
    {
        $topic = $this->create_object(midcom_db_topic::class);
        $attachment = $this->create_object(midcom_db_attachment::class, ['parentguid' => $topic->guid]);
        midcom::get()->auth->request_sudo('midcom.core');
        $attachment->set_privilege('midgard:autoserve_attachment', 'EVERYONE', MIDCOM_PRIVILEGE_DENY);
        midcom::get()->auth->drop_sudo();

        $this->expectException(midcom_error_notfound::class);
        (new midcom_core_urlmethods)->process_serveattachmentguid(Request::create('/'), $attachment->guid);
    }
}
