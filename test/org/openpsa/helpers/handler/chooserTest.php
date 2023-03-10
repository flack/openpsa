<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\helpers\handler;

use openpsa_testcase;
use midcom;
use org_openpsa_contacts_person_dba;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class chooserTest extends openpsa_testcase
{
    public static function setUpBeforeClass() : void
    {
        self::create_user(true);
    }

    public function testHandler_create()
    {
        midcom::get()->auth->request_sudo('org.openpsa.helpers');

        $_GET = [
            'chooser_widget_id' => 'org_openpsa_sales_customerContact_chooser',
            'defaults' => ['openpsa' => 'undefined']
        ];
        $handler_args = ['__mfa', 'org.openpsa.helpers', 'chooser', 'create', 'org_openpsa_contacts_person_dba'];

        $data = $this->run_handler('org.openpsa.sales', $handler_args);
        $this->assertEquals('chooser_create', $data['handler_id']);

        $formdata = [
            'salutation' => '0',
            'lastname' => 'test',
            'email' => 'test@openpsa2.org'
        ];
        $this->set_dm_formdata($data['controller'], $formdata);
        $data = $this->run_handler('org.openpsa.sales', $handler_args);

        ob_start();
        $data['__openpsa_testcase_response']->sendContent();
        ob_end_clean();

        $head_elements = midcom::get()->head->get_jshead_elements();
        $output = $head_elements[count($head_elements) - 1]['content'];
        $this->assertSame(1, preg_match('/add_item\(\{.+?"id":(\d+)/', $output), 'add_item() not found');

        $id = preg_replace('/^.+?"id":(\d+).+$/s', '$1', $output);
        $person = new org_openpsa_contacts_person_dba((int) $id);
        $this->register_object($person);
        $this->assertEquals('test', $person->lastname);

        midcom::get()->auth->drop_sudo();
    }
}
