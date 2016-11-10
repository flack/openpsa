<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class midcom_connectionTest extends openpsa_testcase
{
    public function test_url()
    {
        $test_uri = '/test///test//250/test/0///';
        $target_uri = '/test/test/250/test/0/';
        $method = new ReflectionMethod("midcom_connection", "_parse_url");
        $method->setAccessible(true);
        
        $method->invoke(null, $test_uri, '/', '/');
        $uri = midcom_connection::get_url('uri');
        
        $this->assertEquals($uri, $target_uri);
    }
}
