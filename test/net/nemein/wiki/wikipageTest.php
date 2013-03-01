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
class net_nemein_wiki_wikipageTest extends openpsa_testcase
{
    public function testCRUD()
    {
        midcom::get('auth')->request_sudo('net.nemein.wiki');

        $page = new net_nemein_wiki_wikipage();

        $topic = $this->get_component_node('net.nemein.wiki');
        $page->topic = $topic->id;
        $page->title = 'TEST TITLE';

        $stat = $page->create();
        $this->assertTrue($stat, midcom_connection::get_error_string());

        $this->register_object($page);
        $page->refresh();
        $this->assertEquals('test-title', $page->name);

        $page->title = 'Test Title 2';
        $stat = $page->update();
        $this->assertTrue($stat);
        $page->refresh();
        $this->assertEquals('Test Title 2', $page->title);

        $stat = $page->delete();
        $this->assertTrue($stat);

        midcom::get('auth')->drop_sudo();
    }
}
?>