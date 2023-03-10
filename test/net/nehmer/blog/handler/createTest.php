<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\net\nehmer\blog\handler;

use midcom_db_topic;
use openpsa_testcase;
use midcom_db_article;
use midcom;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class createTest extends openpsa_testcase
{
    protected static midcom_db_topic $_topic;

    public static function setUpBeforeClass() : void
    {
        self::$_topic = self::get_component_node('net.nehmer.blog');
    }

    public function testHandler_create()
    {
        midcom::get()->auth->request_sudo('net.nehmer.blog');

        $data = $this->run_handler(self::$_topic, ['create', 'default']);
        $this->assertEquals('create', $data['handler_id']);

        $formdata = [
            'title' => uniqid(__CLASS__),
            'content' => '<p>TEST</p>'
        ];

        $data = $this->submit_dm_no_relocate_form('controller', $formdata, self::$_topic, ['create', 'default']);
        $qb = midcom_db_article::new_query_builder();
        $qb->add_constraint('title', '=', $formdata['title']);
        $results = $qb->execute();
        $this->register_objects($results);
        $this->assertCount(1, $results);

        $url = $this->get_dialog_url($data);
        $this->assertEquals('', $url);

        midcom::get()->auth->drop_sudo();
    }
}
