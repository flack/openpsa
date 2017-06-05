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
class org_openpsa_products_handler_group_csvimportTest extends openpsa_testcase
{
    public function testHandler_import_group_csv()
    {
        midcom::get()->auth->request_sudo('org.openpsa.products');

        $data = $this->run_handler('org.openpsa.products', ['import', 'group', 'csv']);
        $this->assertEquals('import_group_csv', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
