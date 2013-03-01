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
class org_openpsa_user_handler_person_editTest extends openpsa_testcase
{
    protected static $_user;

    public static function setUpBeforeClass()
    {
        self::$_user = self::create_user(true);
    }

    public function test_handler_edit()
    {
        midcom::get('auth')->request_sudo('org.openpsa.user');

        $data = $this->run_handler('org.openpsa.user', array('edit', self::$_user->guid));
        $this->assertEquals('user_edit', $data['handler_id']);
        $this->assertEquals('person', $data['controller']->schemadb['default']->description);

        $formdata = array
        (
            'email' => 'test@test.info',
            'lastname' => 'TEST'
        );

        $url = $this->submit_dm2_form('controller', $formdata, 'org.openpsa.user', array('edit', self::$_user->guid));

        $this->assertEquals('view/' . self::$_user->guid . '/', $url);

        midcom::get('auth')->drop_sudo();
    }
}
?>