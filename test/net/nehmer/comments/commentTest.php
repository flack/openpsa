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
class net_nehmer_commments_commentTest extends openpsa_testcase
{
    public function testCRUD()
    {
        $topic = $this->create_object(midcom_db_topic::class);
        midcom::get()->auth->request_sudo('net.nehmer.comments');
        $comment = new net_nehmer_comments_comment;
        $comment->objectguid = $topic->guid;

        $stat = $comment->create();
        $this->assertTrue($stat);
        $this->register_object($comment);
        $comment->refresh();
        $this->assertEquals($topic->guid, $comment->objectguid);

        $comment->title = 'TEST';
        $stat = $comment->update();
        $this->assertTrue($stat);
        $comment->refresh();
        $this->assertEquals('TEST', $comment->title);

        $stat = $comment->delete();
        $this->assertTrue($stat);

        midcom::get()->auth->drop_sudo();
    }
}
