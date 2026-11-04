<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\midcom\baseclasses\components\handler;

use openpsa_testcase;
use midcom;
use midcom_db_topic;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class configurationTest extends openpsa_testcase
{
    #[DataProvider('provider_config')]
    public function testHandler_config(string $component)
    {
        $this->create_user(true);
        midcom::get()->auth->request_sudo($component);

        $topic = $this->create_object(midcom_db_topic::class, [
            'component' => $component,
            'name' => uniqid('config')
        ]);

        $data = $this->run_handler($topic, ['config']);
        $this->assertEquals('config', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public static function provider_config() : array
    {
        return [
            ['net.nehmer.comments'],
            ['net.nehmer.static'],
            ['net.nemein.redirector'],
            ['org.openpsa.calendar'],
            ['org.openpsa.contacts'],
            ['org.openpsa.directmarketing'],
            ['org.openpsa.invoices'],
        ];
    }
}
