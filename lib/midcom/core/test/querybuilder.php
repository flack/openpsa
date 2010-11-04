<?php
/**
 *
 */
require_once 'PHPUnit/Framework.php';
require_once 'PHPUnit/TextUI/TestRunner.php';
require_once 'PEAR.php';
require_once '../baseclasses/core/object.php';
require_once '../services/cache/module.php';
require_once 'querybuilder.php';

class mockdbaobject  {

    function _on_prepare_new_query_builder() {
    }
}


if (!class_exists('midgard_query_builder')) {   
class midgard_query_builder 
{

    function __call($name, $args) {


    }
}
}

class CoreQueryBuilderCacheTest extends PHPUnit_Framework_TestCase
{
    public $key = "midcom_querybuilder_cache_mockdbaobjectset_limit1_0";
    public function testRunQueryWithoutCache()
    {
        $cache = $this->getMock('midcom_services_cache_module', array('get', 'put')); // should mock the cache object but that's for later
        $cache->expects($this->once())
              ->method('put')
              ->with($this->equalTo('MISC'), 
              $this->equalTo($this->key), 
              $this->equalTo(array('2')), $this->equalTo(3600))
              ->will($this->returnValue(true));
        $cache->expects($this->once())->method('get')
              ->with($this->equalTo($this->key))
              ->will($this->returnValue(false));
        $qb = new midcom_core_querybuilder_cached( $cache);
        $qb->qb = $this->getMock('midcom_core_querybuilder',array(),array(),'',FALSE);
        $qb->qb->classname = "mockdbaobject";
        $qb->qb->expects($this->once())->method('set_limit')->with($this->equalTo(1));
        $qb->qb->expects($this->once())->method('execute')->will($this->returnValue(array('2')));
        $qb->set_limit(1);
        $ret = $qb->execute();
        $this->assertEquals($ret, array('2'));
    }
    public function testRunQueryWithCachedversion()
    {
        $cache = $this->getMock('Memcache', array('get', 'set')); // should mock the cache object but that's for later
        $cache->expects($this->never())
              ->method('set');
        $cache->expects($this->once())->method('get')
              ->with($this->equalTo($this->key))
              ->will($this->returnValue(array('2')));
        $qb = new midcom_core_querybuilder_cached($cache);
        $qb->qb = $this->getMock('midcom_core_querybuilder',array(),array(),'',FALSE);
        $qb->qb->classname = "mockdbaobject";
        $qb->qb->expects($this->once())->method('set_limit')->with($this->equalTo(1));
        $qb->qb->expects($this->never())->method('execute');
        $qb->set_limit(1);
        $ret = $qb->execute();
        $this->assertEquals($ret, array('2'));
    }


}
class querybuilder  
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }
 
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('MidCOM Core');
        $suite->addTestSuite('CoreQueryBuilderCacheTest');
        return $suite;
    }
}
querybuilder::main();
?>