<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\midcom\helper\search\handler;

use midcom_db_topic;
use openpsa_testcase;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class searchTest extends openpsa_testcase
{
    protected static midcom_db_topic $_topic;

    public static function setUpBeforeClass() : void
    {
        self::$_topic = self::get_component_node('midcom.helper.search');
    }

    public function testHandler_searchform()
    {
        $data = $this->run_handler(self::$_topic);
        $this->assertEquals('basic', $data['handler_id']);
    }

    public function testHandler_searchform_advanced()
    {
        $data = $this->run_handler(self::$_topic, ['advanced']);
        $this->assertEquals('advanced', $data['handler_id']);
    }

    /**
     * @dataProvider provider_result
     */
    public function testHandler_result($options)
    {
        $_GET = $options;
        $data = $this->run_handler(self::$_topic, ['result']);

        $this->assertEquals('result', $data['handler_id']);
    }

    public function provider_result()
    {
        return [
            1 => [
                [
                    'type' => 'basic',
                    'query' => 'test'
                ]
            ],
            2 => [
                [
                    'type' => 'advanced',
                    'query' => 'test',
                    'append_terms' => ['test1', 'test2'],
                    'lastmodified' => time() - 20000
                ]
            ]
        ];
    }

    public function testHandler_opensearchdescription()
    {
        $data = $this->run_handler(self::$_topic, ['opensearch.xml']);
        $this->assertEquals('opensearch_description', $data['handler_id']);
    }
}
