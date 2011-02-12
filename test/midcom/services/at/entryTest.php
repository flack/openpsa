<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

require_once('rootfile.php');

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class midcom_services_at_entryTest extends openpsa_testcase
{
    public function testCRUD()
    {
        $args = array
        (
            'arg1' => 'test',
            'arg2' => 12,
        );

        $_MIDCOM->auth->request_sudo('midcom.services.at');
        $entry = new midcom_services_at_entry_dba();
        $entry->arguments = $args;
        $stat = $entry->create();
        $this->assertTrue($stat);

        $this->assertEquals($args, $entry->arguments);
        $this->assertEquals(MIDCOM_SERVICES_AT_STATUS_SCHEDULED, $entry->status);

        $args['arg2'] = 11;
        $entry->arguments = $args;
        $stat = $entry->update();
        $this->assertTrue($stat);
        $this->assertEquals($args, $entry->arguments);

        $stat = $entry->delete();
        $this->assertTrue($stat);

        $_MIDCOM->auth->drop_sudo();
    }
}
?>