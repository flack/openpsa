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
class midcom_db_personTest extends openpsa_testcase
{
    public function testCRUD()
    {
        midcom::get()->auth->request_sudo('midcom.core');

        $person = new midcom_db_person();
        $person->_use_activitystream = false;
        $person->_use_rcs = false;
        $stat = $person->create();
        $this->assertTrue($stat);
        $this->register_object($person);

        $person = new midcom_db_person($person->guid);
        $person->_use_activitystream = false;
        $person->_use_rcs = false;
        $this->assertEquals('person #' . $person->id, $person->name);
        $this->assertEquals('person #' . $person->id, $person->rname);
        $person->firstname = ' Firstname ';
        $person->lastname = ' Lastname ';
        $stat = $person->update();
        $this->assertTrue($stat);
        $this->assertEquals('Firstname Lastname', $person->name);
        $this->assertEquals('Lastname, Firstname', $person->rname);

        $group = $this->create_object(midcom_db_group::class);
        $attributes = [
            'gid' => $group->id,
            'uid' => $person->id
        ];
        $member = $this->create_object(midcom_db_member::class, $attributes);

        $stat = $person->delete();
        $this->assertTrue($stat);

        $qb = midcom_db_member::new_query_builder();
        $qb->add_constraint('id', '=', $member->id);
        $this->assertEquals(0, $qb->count());

        midcom::get()->auth->drop_sudo();
    }

    /**
     * @dataProvider providerUpdate_computed_members
     */
    public function testUpdate_computed_members($attributes, $results)
    {
        $person = self::prepare_object('midcom_db_person', $attributes);
        foreach ($results as $field => $value) {
            $this->assertEquals($value, $person->$field, 'Mismatch on field ' . $field);
        }
    }

    public function providerUpdate_computed_members()
    {
        return [
            [
                [
                    'firstname' => 'FIRSTNAME',
                    'lastname' => 'LASTNAME',
                    'email' => 'test@test.com',
                ],
                [
                    'name' => 'FIRSTNAME LASTNAME',
                    'rname' => 'LASTNAME, FIRSTNAME',
                    'emaillink' => '<a href="mailto:test@test.com" title="FIRSTNAME LASTNAME">test@test.com</a>',
                    'homepagelink' => '',
                ],
            ],
            [
                [
                    'firstname' => '',
                    'lastname' => 'LASTNAME',
                    'homepage' => 'http://openpsa2.org',
                ],
                [
                    'name' => 'LASTNAME',
                    'rname' => 'LASTNAME',
                    'emaillink' => '',
                    'homepagelink' => '<a href="http://openpsa2.org" title="LASTNAME">http://openpsa2.org</a>',
                ],
            ],
        ];
    }
}
