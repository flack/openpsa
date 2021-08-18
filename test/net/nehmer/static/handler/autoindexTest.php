<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\net\nehmer\handler;

use openpsa_testcase;
use midcom;
use midcom_db_topic;
use midcom_db_article;
/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class autoindexTest extends openpsa_testcase
{
    public function testHandler_autoindex()
    {
        midcom::get()->auth->request_sudo('net.nehmer.static');
        $data = [
            'component' => 'net.nehmer.static',
            'name' => __CLASS__ . time()
        ];
        $topic = $this->create_object(midcom_db_topic::class, str_replace('\\', '_', $data));
        $topic->set_parameter('net.nehmer.static', 'autoindex', true);
        $article_properties = [
            'topic' => $topic->id,
            'name' => 'dummy'
        ];
        $this->create_object(midcom_db_article::class, $article_properties);

        $data = $this->run_handler($topic);
        $this->assertEquals('autoindex', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
