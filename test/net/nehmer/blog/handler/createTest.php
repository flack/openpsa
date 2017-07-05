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
class net_nehmer_blog_handler_createTest extends openpsa_testcase
{
    protected static $_topic;

    public static function setUpBeforeClass()
    {
        self::$_topic = self::get_component_node('net.nehmer.blog');
    }

    public function testHandler_create()
    {
        midcom::get()->auth->request_sudo('net.nehmer.blog');

        $data = $this->run_handler(self::$_topic, ['create', 'default']);
        $this->assertEquals('create', $data['handler_id']);

        $this->show_handler($data);

        $formdata = [
            'title' => uniqid(__CLASS__),
            'content' => '<p>TEST</p>'
        ];

        $this->submit_dm_no_relocate_form('controller', $formdata, self::$_topic, ['create', 'default']);
        $url = $this->get_dialog_url();
        $this->assertEquals('', $url);

        $qb = midcom_db_article::new_query_builder();
        $qb->add_constraint('title', '=', $formdata['title']);
        $results = $qb->execute();
        $this->register_objects($results);
        $this->assertEquals(1, sizeof($results));

        midcom::get()->auth->drop_sudo();
    }
}
