<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

if (!defined('OPENPSA_TEST_ROOT'))
{
    define('OPENPSA_TEST_ROOT', dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR);
    require_once(OPENPSA_TEST_ROOT . 'rootfile.php');
}

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class org_openpsa_reports_queryTest extends openpsa_testcase
{
    public function testCRUD()
    {
        midcom::get('auth')->request_sudo('org.openpsa.reports');
        $query = new org_openpsa_reports_query_dba();
        $stat = $query->create();
        $this->assertTrue($stat);
        $this->register_object($query);

        $query = new org_openpsa_reports_query_dba($query->guid);
        $this->assertEquals('.html', $query->extension);
        $this->assertEquals(ORG_OPENPSA_OBTYPE_REPORT_TEMPORARY, $query->orgOpenpsaObtype);
        $this->assertEquals('text/html', $query->mimetype);
        $this->assertEquals('unnamed', $query->title);

        $query->title = 'TEST';
        $stat = $query->update();
        $this->assertTrue($stat);
        $query->refresh();
        $this->assertEquals('TEST', $query->title);

        $stat = $query->delete();
        $this->assertTrue($stat);

        midcom::get('auth')->drop_sudo();
    }
}
?>