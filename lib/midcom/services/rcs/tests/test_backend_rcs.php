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
require_once(MIDCOM_ROOT . "/services/auth.php");
require_once(MIDCOM_ROOT . "/core/user.php");

$curr_path = dirname(__FILE__);

require_once $curr_path . '/../../rcs.php';
require_once $curr_path . '/../backend/rcs.php';
require_once $curr_path . '/../config.php';

require_once MIDCOM_ROOT . "/services/auth.php";

Mock::generate('midcom_application', 'MockMidCOM');
Mock::generate('midcom_services_cache', 'mock_midcom_cache');
Mock::generate('midcom_services_rcs_backend', 'MockRcsBackend');
Mock::generate('midcom_services_auth', 'MockServicesAuth');
Mock::generate('midcom_core_user' , 'MockUser');

Mock::generatePartial('midcom_services_rcs_backend_rcs', 'MockRcsBackendCreate', array('rcs_create'));
Mock::generatePartial('midcom_services_rcs_backend_rcs', 'MockRcsBackendUpdate', array('rcs_update'));

class midcom_backend_rcs_test extends UnitTestCase {
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
require_once '../backend/rcs.php';

require_once MIDCOM_ROOT . '/../../midcom.helper.xml/objectmapper.php';

class test_class {
    var $name;
    var $guid;
    var $id;
    var $content;
    var $_private;
}



class midcom_services_rcs_backend_update_test extends midcom_backend_rcs_test  {
    var $config;
    var $backend;
    function setUp() {
        $this->basicSetUp();
        $this->config = new midcom_services_rcs_config($this->config, &$this->midcom);
        $this->backend = new midcom_services_rcs_backend_rcs($this->config, &$this->midcom);
        $this->object = new test_class;
        $this->object->name = "name";
        $this->object->content = "content";
        $this->object->guid = "test_guid";
        $this->object->id   = 1;
        $this->object->_private = "...";
        
        $this->midcom->auth = new MockServicesAuth;
        $this->midcom->auth->user = new MockUser();
        $this->midcom->auth->user->id = 23;
    }
    
    function test_startup() 
    {
        $this->assertEqual($this->backend->config,$this->config);
    }
    
    function test_update() {
            
        $this->backend = new MockRcsBackendCreate($this->config, $this->midcom);
        $this->backend->config = $this->config;
        $this->backend->midcom = & $this->midcom;
        if (file_exists($this->config->get_rcs_root() . "/test_guid,v" )) {
            unlink($this->config->get_rcs_root() . "/test_guid,v");
        }
        $this->backend->expectOnce('rcs_create');
        $this->backend->setReturnValue('rcs_create', true);
        $result = $this->backend->update($this->object, "testmessage");
        $this->assertTrue($result );
    }
    
    function test_rcs_update() {
            
        $this->backend = new MockRcsBackendCreate($this->config, $this->midcom);
        $this->backend->config = $this->config;
        $this->backend->midcom = & $this->midcom;
        if (file_exists($this->config->get_rcs_root() . "/test_guid,v" )) {
            unlink($this->config->get_rcs_root() . "/test_guid,v");
        }
        $this->backend->setReturnValue('rcs_create', true);
        $this->backend->expectOnce('rcs_create');
        $result = $this->backend->rcs_update($this->object, "testmessage");
        $this->assertEqual($result , 0 );
    }
    
    function test_rcs_create_update() {
        $result = $this->backend->rcs_create($this->object, "testmessage");
        $this->assertEqual($result , 0 );
        $this->object->name = "kaja";
        $this->backend = new MockRcsBackendCreate($this->config, $this->midcom);
        $this->backend->config = $this->config;
        $this->backend->midcom = & $this->midcom;
        $this->backend->expectNever('rcs_create');
        $this->assertTrue(
            $this->backend->update($this->object, "updateMsg")
            );
        $this->backend->tally();
        
    }
    
}


if (realpath($_SERVER['PHP_SELF']) == __FILE__  || (array_key_exists('HTTP_HOST',$_SERVER) )) {
    $test = new GroupTest("RCSTests");            
    $test->AddTestCase(new midcom_services_rcs_backend_update_test());
    
    $reporter = ((array_key_exists('HTTP_HOST',$_SERVER) )) ? new HtmlReporter() : new TextReporter;
    $test->run($reporter);

}

?>