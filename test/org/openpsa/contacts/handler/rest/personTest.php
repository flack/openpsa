<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\contacts\handler\rest;

use midcom_db_group;
use midcom_db_member;
use midcom_db_person;
use openpsa_testcase;
use midcom;
use org_openpsa_contacts_person_dba;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class personTest extends openpsa_testcase
{
    protected static midcom_db_person $_person;

    public static function setUpBeforeClass() : void
    {
        self::$_person = self::create_user(true);
    }

    public function testHandler_get()
    {
        midcom::get()->auth->request_sudo('org.openpsa.contacts');

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = ['guid' => self::$_person->guid];

        $data = $this->run_handler('org.openpsa.contacts', ['rest', 'person']);
        $this->assertEquals('person_rest', $data['handler_id']);

        $response = $data['__openpsa_testcase_response'];
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(self::$_person->guid, json_decode($response->getContent())->object->guid);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_create()
    {
        midcom::get()->auth->request_sudo('org.openpsa.contacts');

        $group = $this->create_object(midcom_db_group::class);
        $this->set_post_data([
            'lastname' => __FUNCTION__,
            'group_id' => $group->id
        ]);

        $data = $this->run_handler('org.openpsa.contacts', ['rest', 'person']);
        $response = $data['__openpsa_testcase_response'];
        $this->assertEquals(200, $response->getStatusCode());

        $person = new org_openpsa_contacts_person_dba(json_decode($response->getContent())->guid);
        $this->register_object($person);
        $this->assertEquals(__FUNCTION__, $person->lastname);

        $qb = midcom_db_member::new_query_builder();
        $qb->add_constraint('uid', '=', $person->id);
        $qb->add_constraint('gid', '=', $group->id);
        $results = $qb->execute();
        $this->register_objects($results);
        $this->assertCount(1, $results);

        midcom::get()->auth->drop_sudo();
    }
}
