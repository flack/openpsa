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
class net_nemein_redirector_tinyurlTest extends openpsa_testcase
{
    public function testCRUD()
    {
        midcom::get()->auth->request_sudo('net.nemein.redirector');
        $tinyurl = new net_nemein_redirector_tinyurl_dba();
        $tinyurl->_use_rcs = false;
        $name = net_nemein_redirector_tinyurl_dba::generate();

        $topic = $this->get_component_node('net.nemein.redirector');
        $tinyurl->node = $topic->guid;
        $tinyurl->name = $name;

        $stat = $tinyurl->create();
        $this->assertTrue($stat, midcom_connection::get_error_string());

        $this->register_object($tinyurl);
        $tinyurl->refresh();
        $this->assertEquals($name, $tinyurl->name);

        $tinyurl2 = new net_nemein_redirector_tinyurl_dba();
        $tinyurl2->node = $topic->guid;
        $tinyurl2->name = $name;
        $this->assertFalse($tinyurl2->create());

        $name2 = net_nemein_redirector_tinyurl_dba::generate();
        $tinyurl->name = $name2;
        $stat = $tinyurl->update();
        $this->assertTrue($stat);
        $tinyurl->refresh();
        $this->assertEquals($name2, $tinyurl->name);

        $stat = $tinyurl->delete();
        $this->assertTrue($stat);

        midcom::get()->auth->drop_sudo();
    }
}
