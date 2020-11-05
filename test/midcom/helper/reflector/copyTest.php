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
class midcom_helper_reflector_copyTest extends openpsa_testcase
{
    public function test_copy_parameters()
    {
        $source = $this->create_object(midcom_db_topic::class);
        $target = $this->create_object(midcom_db_topic::class);
        midcom::get()->auth->request_sudo('midcom');
        $source->set_parameter('a', 'b', 'c');
        $copy = new midcom_helper_reflector_copy;
        $this->assertTrue($copy->copy_parameters($source, $target));
        midcom::get()->auth->drop_sudo();
        $this->assertEquals('c', $target->get_parameter('a', 'b'));
    }
}
