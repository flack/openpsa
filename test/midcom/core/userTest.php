<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\midcom\core;

use openpsa_testcase;
use midcom;
use midcom_db_group;
use midcom_db_person;
use midcom_core_user;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class userTest extends openpsa_testcase
{
    public function test_is_in_group()
    {
        $parentgroup = $this->create_object(midcom_db_group::class);
        $childgroup = $this->create_object(midcom_db_group::class, ['owner' => $parentgroup->id]);
        $person = $this->create_object(midcom_db_person::class);
        midcom::get()->auth->request_sudo('midcom.core');
        $childgroup->add_member($person);
        midcom::get()->auth->drop_sudo();

        $user = new midcom_core_user($person);

        $this->assertTrue($user->is_in_group('group:' . $parentgroup->guid));
    }

}
