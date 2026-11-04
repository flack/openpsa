<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\net\nehmer\comments\handler;

use openpsa_testcase;
use midcom;
use PHPUnit\Framework\Attributes\DataProvider;
use midcom_db_topic;
use net_nehmer_comments_comment;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class adminTest extends openpsa_testcase
{
    public function testHandler_welcome()
    {
        $this->create_user(true);
        midcom::get()->auth->request_sudo('net.nehmer.comments');

        $data = $this->run_handler('net.nehmer.comments');
        $this->assertEquals('admin-welcome', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    #[DataProvider('provider_moderate')]
    public function testHandler_moderate($status)
    {
        $this->create_user(true);
        midcom::get()->auth->request_sudo('net.nehmer.comments');

        $data = $this->run_handler('net.nehmer.comments', ['moderate', $status]);
        $this->assertEquals('moderate', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public static function provider_moderate()
    {
        return [
            ['abuse'],
            ['reported_abuse'],
            ['junk'],
            ['latest'],
            ['latest_new'],
            ['latest_approved'],
        ];
    }

    public function testHandler_moderate_ajax()
    {
        $this->create_user(true);
        midcom::get()->auth->request_sudo('net.nehmer.comments');

        $topic = $this->create_object(midcom_db_topic::class);
        $comment = $this->create_object(net_nehmer_comments_comment::class, ['objectguid' => $topic->guid]);

        $this->set_post_data([
            'action' => 'action_delete',
            'guid' => $comment->guid
        ]);

        $data = $this->run_handler('net.nehmer.comments', ['moderate', 'ajax', 'latest']);
        $this->assertEquals('moderate_ajax', $data['handler_id']);

        $qb = net_nehmer_comments_comment::new_query_builder();
        $qb->add_constraint('guid', '=', $comment->guid);
        $this->assertEquals(0, $qb->count());

        midcom::get()->auth->drop_sudo();
    }
}
