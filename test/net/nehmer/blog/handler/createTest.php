<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

if (!defined('OPENPSA_TEST_ROOT'))
{
    define('OPENPSA_TEST_ROOT', dirname(dirname(dirname(dirname(dirname(__FILE__))))) . DIRECTORY_SEPARATOR);
    require_once OPENPSA_TEST_ROOT . 'rootfile.php';
}

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
        midcom::get('auth')->request_sudo('net.nehmer.blog');

        $data = $this->run_handler(self::$_topic, array('create', 'default'));
        $this->assertEquals('create', $data['handler_id']);

        $this->show_handler($data);

        $formdata = array
        (
            'title' => __CLASS__ . microtime(),
            'content' => '<p>TEST</p>'
        );

        $url = $this->submit_dm2_form('controller', $formdata, self::$_topic, array('create', 'default'));
        $this->assertEquals('', $url);

        $qb = midcom_db_article::new_query_builder();
        $qb->add_constraint('topic', '=', self::$_topic->id);
        $results = $qb->execute();
        $this->register_objects($results);
        $this->assertEquals(1, sizeof($results));

        midcom::get('auth')->drop_sudo();
    }
}
?>
