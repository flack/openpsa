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
class midcom_core_accountTest extends openpsa_testcase
{
    protected static $_person;

    public static function setUpBeforeClass()
    {
        self::$_person = self::create_class_object('midcom_db_person');
    }

    public function testCRUD()
    {
        midcom::get('auth')->request_sudo('midcom.core');

        $account = midcom_core_account::get(self::$_person);
        $this->assertTrue($account instanceOf midcom_core_account);

        $password = 'password_' . time();
        $account->set_password($password);
        $this->assertEquals(midcom_connection::prepare_password($password), $account->get_password());

        $username = __CLASS__ . ' user ' . time();
        $account->set_username($username);
        $this->assertEquals($username, $account->get_username());

        $stat = $account->save();
        $this->assertTrue($stat);

        $new_username = __CLASS__ . ' user ' . time();
        $account->set_username($new_username);
        $stat = $account->save();
        $this->assertTrue($stat);
        $this->assertEquals($new_username, $account->get_username());

        $stat = $account->delete();
        $this->assertTrue($stat);

        midcom::get('auth')->drop_sudo();
    }

    public function testNameUnique()
    {
        midcom::get('auth')->request_sudo('midcom.core');

        $account1 = midcom_core_account::get(self::$_person);
        $username = __CLASS__ . ' user ' . time();
        $account1->set_username($username);
        $account1->save();
        $this->assertEquals($username, $account1->get_username());

        $person = $this->create_object('midcom_db_person');
        $account2 = midcom_core_account::get($person);

        $password = 'password_' . time();
        $account2->set_password($password);
        $account2->set_username($username);

        $stat = $account2->save();
        $this->assertFalse($stat);

        midcom::get('auth')->drop_sudo();
    }

    private function getQueryMock()
    {
        return $this->getMock('midcom_core_query', array('add_constraint', 'execute', 'count', 'count_unchecked'));
    }

    public function testAddUsernameConstraint()
    {
        $rdm_username = uniqid(__FUNCTION__);
        if (method_exists('midgard_user', 'login'))
        {
            // test invalid user
            $operator = "=";
            $query = $this->getQueryMock();
            $query->expects($this->once())
            ->method('add_constraint')
            ->with($this->equalTo('id'), $this->equalTo("="), $this->equalTo(0));

            midcom_core_account::add_username_constraint($query, "=", $rdm_username);

            // test empty usernames
            $query = $this->getQueryMock();
            $query->expects($this->once())
            ->method('add_constraint')
            ->with($this->equalTo('guid'), $this->equalTo("NOT IN"));

            midcom_core_account::add_username_constraint($query, "=", "");
        }
        else
        {
            $operator = "=";
            $value = "bob";

            $query = $this->getQueryMock();
            $query->expects($this->once())
            ->method('add_constraint')
            ->with($this->equalTo('username'), $this->equalTo($operator), $this->equalTo($value));

            midcom_core_account::add_username_constraint($query, $operator, $value);
        }
    }
}
?>