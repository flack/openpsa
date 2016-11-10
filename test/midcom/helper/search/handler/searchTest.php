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
class midcom_helper_search_handler_searchTest extends openpsa_testcase
{
    protected static $_topic;

    public static function setUpBeforeClass()
    {
        self::$_topic = self::get_component_node('midcom.helper.search');
    }

    public function testHandler_searchform()
    {
        $data = $this->run_handler(self::$_topic);
        $this->assertEquals('basic', $data['handler_id']);

        $this->show_handler($data);
    }

    public function testHandler_searchform_advanced()
    {
        $data = $this->run_handler(self::$_topic, array('advanced'));
        $this->assertEquals('advanced', $data['handler_id']);

        $this->show_handler($data);
    }

    /**
     * @dataProvider provider_result
     */
    public function testHandler_result($options)
    {
        $_REQUEST = $options;
        $data = $this->run_handler(self::$_topic, array('result'));

        $this->assertEquals('result', $data['handler_id']);

        $this->show_handler($data);
    }

    public function provider_result()
    {
        return array(
            1 => array(
                array(
                    'type' => 'basic',
                    'query' => 'test'
                )
            ),
            2 => array(
                array(
                    'type' => 'advanced',
                    'query' => 'test',
                    'append_terms' => array('test1', 'test2'),
                    'lastmodified' => time() - 20000
                )
            )
        );
    }

    public function testHandler_opensearchdescription()
    {
        $data = $this->run_handler(self::$_topic, array('opensearch.xml'));
        $this->assertEquals('opensearch_description', $data['handler_id']);

        $this->show_handler($data);
    }
}
