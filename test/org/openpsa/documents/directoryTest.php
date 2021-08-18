<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\documents;

use openpsa_testcase;
use midcom;
use org_openpsa_documents_directory;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class directoryTest extends openpsa_testcase
{
    public function testCRUD()
    {
        $this->create_user(true);

        midcom::get()->auth->request_sudo('org.openpsa.documents');

        $directory = new org_openpsa_documents_directory();
        $directory->name = 'TEST_' . __CLASS__ . time();
        $directory->_use_rcs = false;

        $stat = $directory->create();
        $this->assertTrue($stat);
        $this->register_object($directory);

        $stat = $directory->update();
        $this->assertTrue($stat);

        $stat = $directory->delete();
        $this->assertTrue($stat);

        midcom::get()->auth->drop_sudo();
    }
}
