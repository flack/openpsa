<?php
/**
 *
 */
$base = dirname(__FILE__);
require_once 'PHPUnit/Framework.php';
require_once 'PHPUnit/TextUI/TestRunner.php';
require_once 'PEAR.php';
require_once $base . '/../request.php';

class UrlFactoryTest extends PHPUnit_Framework_TestCase
{

    function test_midcom_url_paramcollector () {
        $collector = new midcom_url_paramcollector;
        $collector->set_style('/tmp');
        $this->assertEquals($collector->get_style(), '/tmp');
        $collector->style_can_override = false;
        $collector->set_style('tem');
        $this->assertEquals($collector->get_style(), '/tmp');

    }

    function test_paramcollector_add_config() {

        $collector = new midcom_url_paramcollector;
        $collector->add_config('dontshow', true);
        $this->assertEquals($collector->get_config('dontshow'), true);
    }

    function test_midcom_urlstack() {
        $stack = new midcom_url_urlstack(array());

        $this->assertTrue($stack->done());
        $this->assertTrue($stack->get() == false);

        $stack = new midcom_url_urlstack(array('news', 'test'));
        $this->assertFalse($stack->done());
        $this->assertEquals($stack->get(), 'news');

        $stack->pop();
        $this->assertEquals('test', $stack->get());
        $stack->pop();
        $this->assertTrue($stack->done());
        $this->assertTrue($stack->get() == false);
    }



}
// tests the different parsers that are used to parse the url.
// format: function test_(class name (without namespacing))_(function to test)_(test_description)
//    Example:
//    function test_midcom_parse_variable_parse_substyle:
//    test the substyling variant of the parse_variable function
//    in the midcom_url_midcom class.
//
class UrlParserTests extends PHPUnit_Framework_TestCase
{

    function test_different_urls () {
        $config = array('midcom_url_topic', 'midcom_url_midcom');
        $this->urlFactory = new midcom_urlparserfactory($config);
        $argv = array ('midcom-serveattachmentguid', '234234324234');
        $argv = array( 'news');
        $result = $this->urlFactory->execute($argv);
        $this->assertTrue($result instanceof midcom_command_factory);
    }

    function test_midcom_url_midcom_parse_variable() {
        $this->make_url_parser(array('midcom-cache-invalidate'));
        $this->assertEquals($this->parser->param_collector->get_command(), 'midcom_services_cache_invalidate');

    }
    function test_midcom_parse_variable_parse_substyle()
    {
        $this->make_url_parser(array('midcom-substyle-test'));
        $this->assertEquals($this->parser->param_collector->get_style(), 'test');
    }

    function test_get_midcom_topic_from_db() {
       $this->fail() ;
    }


    function make_url_parser($argv) {
        $this->stack = new midcom_url_urlstack($argv);
        $this->parser = new midcom_url_midcom($this->stack,new midcom_url_nullparser );
    }

}


class RequestTests extends PHPUnit_Framework_TestCase
{
    function setUp()
    {
        $this->request = new midcom_request(array('test' => '', 'empty', 'next' => 'string', 'trim' => ' string '));
    }
    public function testRequestGetSeters() {
        $this->assertEquals($this->request->get('test'), '') ;
        $this->assertEquals($this->request->get('test4'), false) ;
        $this->assertEquals($this->request->get('test4', ''), '') ;
        $this->assertEquals($this->request->get('next', ''), 'string') ;
        $this->assertEquals($this->request->getTrim('trim', ''), 'string') ;
    }

    public function test_set_get_var() {
        $this->request->set('rau', 'hau');
        $this->assertEquals($this->request->get('rau'), 'hau');
    }
}

class urlfactorytests
{
    public static function main()
    {
    }

    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('MidCOM Core');
        $suite->addTestSuite('UrlFactoryTest');
        $suite->addTestSuite('UrlParserTests');
        $suite->addTestSuite('RequestTests');
        return $suite;
    }
}
//urlfactorytests::main();

PHPUnit_TextUI_TestRunner::run(urlfactorytests::suite());
?>