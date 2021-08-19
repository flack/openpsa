<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\contacts\handler;

use openpsa_testcase;
use midcom;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class searchTest extends openpsa_testcase
{
    protected static $_person;

    public static function setUpBeforeClass() : void
    {
        self::$_person = self::create_user(true);
    }

    public function testHandler_search()
    {
        midcom::get()->auth->request_sudo('org.openpsa.contacts');

        $data = $this->run_handler('org.openpsa.contacts', ['search']);
        $this->assertEquals('search', $data['handler_id']);

        $_GET = ['query' => 'firstname lastname'];
        $this->run_handler('org.openpsa.contacts', ['search']);
        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_search_autocomplete()
    {
        midcom::get()->auth->request_sudo('org.openpsa.contacts');

        $data = $this->run_handler('org.openpsa.contacts', ['search', 'autocomplete']);
        $this->assertEquals('search_autocomplete', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
