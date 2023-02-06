<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\midgard\admin\asgard\handler\object;

use openpsa_testcase;
use midcom_db_topic;
use midcom;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class metadataTest extends openpsa_testcase
{
    protected static midcom_db_topic $_object;

    public static function setUpBeforeClass() : void
    {
        self::$_object = self::create_class_object(midcom_db_topic::class);
    }

    public function testHandler_edit()
    {
        $this->create_user(true);
        midcom::get()->auth->request_sudo('midgard.admin.asgard');

        $data = $this->run_handler('net.nehmer.static', ['__mfa', 'asgard', 'object', 'metadata', self::$_object->guid]);
        $this->assertEquals('object_metadata', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
