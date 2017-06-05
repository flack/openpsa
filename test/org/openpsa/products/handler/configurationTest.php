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
class org_openpsa_products_handler_configurationTest extends openpsa_testcase
{
    public function testHandler_config()
    {
        midcom::get()->auth->request_sudo('org.openpsa.products');

        $data = $this->run_handler('org.openpsa.products', ['config']);
        $this->assertEquals('config', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_recreate()
    {
        midcom::get()->auth->request_sudo('org.openpsa.products');

        $url = $this->run_relocate_handler('org.openpsa.products', ['config', 'recreate']);
        $this->assertEquals('config/', $url);

        midcom::get()->auth->drop_sudo();
    }
}
