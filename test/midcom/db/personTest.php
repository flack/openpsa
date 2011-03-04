<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

if (!defined('OPENPSA_TEST_ROOT'))
{
    define('OPENPSA_TEST_ROOT', dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR);
    require_once(OPENPSA_TEST_ROOT . 'rootfile.php');
}

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class midcom_db_personTest extends openpsa_testcase
{
    public function testCRUD()
    {
        $_MIDCOM->auth->request_sudo('midcom.core');

        $person = new midcom_db_person();
        $stat = $person->create();
        $this->assertTrue($stat);

        $person = new midcom_db_person($person->guid);
        $this->assertEquals('person #' . $person->id, $person->name);
        $this->assertEquals('person #' . $person->id, $person->rname);
        $person->firstname = ' Firstname ';
        $person->lastname = ' Lastname ';
        $stat = $person->update();
        $this->assertTrue($stat);
        $this->assertEquals('Firstname Lastname', $person->name);
        $this->assertEquals('Lastname, Firstname', $person->rname);

        $stat = $person->delete();
        $this->assertTrue($stat);

        $_MIDCOM->auth->drop_sudo();
     }

    /**
     * @dataProvider providerUpdate_computed_members
     */
    public function testUpdate_computed_members($attributes, $results)
    {
        $person = self::prepare_object('midcom_db_person', $attributes);
        foreach ($results as $field => $value)
        {
            $this->assertEquals($value, $person->$field, 'Mismatch on field ' . $field);
        }
    }

    public function providerUpdate_computed_members()
    {
        return array
        (
            array
            (
                array
                (
                    'firstname' => 'FIRSTNAME',
                    'lastname' => 'LASTNAME',
                    'email' => 'test@test.com',
                ),
                array
                (
                    'name' => 'FIRSTNAME LASTNAME',
                    'rname' => 'LASTNAME, FIRSTNAME',
                    'emaillink' => '<a href="mailto:test@test.com" title="FIRSTNAME LASTNAME">test@test.com</a>',
                    'homepagelink' => '',
                ),
            ),
            array
            (
                array
                (
                    'firstname' => '',
                    'lastname' => 'LASTNAME',
                    'homepage' => 'http://openpsa2.org',
                ),
                array
                (
                    'name' => 'LASTNAME',
                    'rname' => 'LASTNAME',
                    'emaillink' => '',
                    'homepagelink' => '<a href="http://openpsa2.org" title="LASTNAME">http://openpsa2.org</a>',
                ),
            ),
        );
    }
}
?>