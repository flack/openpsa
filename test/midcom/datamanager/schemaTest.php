<?php
/**
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
* @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
* @license http://www.gnu.org/licenses/gpl.html GNU General Public License
*/

namespace midcom\datamanager\test;

use openpsa_testcase;
use midcom_db_topic;
use midcom\datamanager\controller;
use midcom\datamanager\schemadb;
use midcom\datamanager\schema;
use midcom\datamanager\storage\container\nullcontainer;

class schemaTest extends openpsa_testcase
{
    public function test_process_parameter()
    {
        $schema = new schema(array('fields' => array
        (
            'test' => array
            (
                'title' => 'test',
                'storage' => 'parameter',
                'type' => 'text',
                'widget' => 'text'
            ),
        )));
        $fields = $schema->get_fields();
        $this->assertArrayHasKey('test', $fields);
        $this->assertArrayHasKey('storage', $fields['test']);
        $this->assertArrayHasKey('domain', $fields['test']['storage']);
    }
}