<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\reports;

use openpsa_testcase;
use midcom;
use org_openpsa_reports_query_dba;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class queryTest extends openpsa_testcase
{
    public function testCRUD()
    {
        midcom::get()->auth->request_sudo('org.openpsa.reports');
        $query = new org_openpsa_reports_query_dba();
        $stat = $query->create();
        $this->assertTrue($stat);
        $this->register_object($query);

        $query = new org_openpsa_reports_query_dba($query->guid);
        $this->assertEquals('.html', $query->extension);
        $this->assertEquals(org_openpsa_reports_query_dba::OBTYPE_REPORT_TEMPORARY, $query->orgOpenpsaObtype);
        $this->assertEquals('text/html', $query->mimetype);
        $this->assertEquals('', $query->title);

        $query->title = 'TEST';
        $stat = $query->update();
        $this->assertTrue($stat);
        $query->refresh();
        $this->assertEquals('TEST', $query->title);

        $stat = $query->delete();
        $this->assertTrue($stat);

        midcom::get()->auth->drop_sudo();
    }
}
