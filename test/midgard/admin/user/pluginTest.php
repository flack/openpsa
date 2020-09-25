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
class midgard_admin_user_pluginTest extends openpsa_testcase
{
    public function testGenerate_password()
    {
        $password = midgard_admin_user_plugin::generate_password();
        $this->assertEquals(8, strlen($password));

        $password = midgard_admin_user_plugin::generate_password(16, true);
        $this->assertEquals(16, strlen($password));
    }
}
