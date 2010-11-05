<?php
/**
 * Created on 25/08/2006
 * @author tarjei huse
 * @package midcom.services.rcs
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * 
 */
require_once '../../.././tests/config/cli_config.php';
require_once 'PEAR.php';
require_once(MIDCOM_ROOT . "/baseclasses/core/object.php");
require_once(MIDCOM_ROOT . "/services/cache.php");


$curr_path = dirname(__FILE__);

require_once $curr_path . '/../../rcs.php';
require_once $curr_path . '/../config.php';


Mock::generate('midcom_application', 'MockMidCOM');
Mock::generate('midcom_services_cache', 'mock_midcom_cache');
Mock::generate('midcom_services_rcs_backend', 'MockRcsBackend');
//

class midcom_rcs_test extends UnitTestCase {
    var $config;
    var $midcom;
    function basicSetUp() 
    {
        $_MIDCOM = new MockMidCOM();        
        $_MIDCOM = &$_MIDCOM;
        $this->midcom = &$_MIDCOM;
        
        $this->config = array(
            'midcom_services_rcs_bin_dir' => "/usr/bin",
            'midcom_services_rcs_root' => "/tmp",
            'midcom_services_rcs_use' => true
        );
    }
}

class midcom_services_rcs_test extends midcom_rcs_test {
    
    function setUp() {
        $this->basicSetUp();
    }
    
    function test_midcom_services_rcs() 
    {
        $rcs = new midcom_services_rcs($this->config, &$this->midcom);
        $this->assertEqual($rcs->_config, new midcom_services_rcs_config($this->config));
    }
    
    function test_update() {
       $rcs = new midcom_services_rcs($this->config, &$this->midcom);
       $rcs->_handler = new MockRcsBackend;
       $object = "tata";
       $message = "tete";
       $rcs->_handler->expectArguments('update', array($object, $message));
       $rcs->update($object, $message);
       $rcs->_handler->tally();
       
    }
}

class rcs_config_test extends midcom_rcs_test {

    function setUp() 
    {
        $this->basicSetUp();
        $_MIDCOM = new MockMidCOM();        
        $_MIDCOM = &$_MIDCOM;
        
    }

    function test_midcom_services_rcs() 
    {
        $this->config['midcom_services_rcs_bin_dir'] = '/opt/gabage';
        $this->config['midcom_services_rcs_use'] = false;
        $rcs = new midcom_services_rcs_config($this->config);
        $this->assertFalse($rcs->_test_rcs_config());
        $this->assertEqual($rcs->_get_handler_class() ,'midcom_services_rcs_backend_null');
    }
    
    function test_get_rcs_root() {
        $rcs = new midcom_services_rcs_config($this->config);
        $this->assertEqual($rcs->get_rcs_root(), '/tmp');
        $this->assertEqual($rcs->get_bin_prefix(), '/usr/bin');
    }
    /**
     * We should fail hard if rcs_use is set but something is wrong.
     */
    function test_midcom_services_rcs_fail() {
        
        $this->config['midcom_services_rcs_bin_dir'] = '/opt/gabage';
        $this->midcom->expectArguments('generate_error',array('Tried to use RCS as wanted but failed. Please read the errorlog for more information.', MIDCOM_ERRCRIT));
        $rcs = new midcom_services_rcs_config($this->config);
        $this->assertFalse($rcs->_test_rcs_config());
        $this->midcom->tally();
    }
    
    function test_correct_startup() {
        
        $rcs = new midcom_services_rcs_config($this->config);
        
        $this->assertTrue($rcs->_test_rcs_config());
        
        $handler = $rcs->get_handler($this->midcom);
        $this->assertTrue(is_a($handler, 'midcom_services_rcs_backend_rcs'));
        $this->assertEqual($this->midcom, $handler->midcom);
    }
}




 

if (realpath($_SERVER['PHP_SELF']) == __FILE__  || (array_key_exists('HTTP_HOST',$_SERVER) )) {
    $test = new GroupTest("RCSTests");            
    $test->AddTestCase(new midcom_services_rcs_test ());
    $test->AddTestCase(new rcs_config_test ());
    
    $reporter = ((array_key_exists('HTTP_HOST',$_SERVER) )) ? new HtmlReporter() : new TextReporter;
    $test->run($reporter);

}

?>