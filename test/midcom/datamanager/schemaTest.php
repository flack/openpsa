<?php
/**
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
* @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
* @license http://www.gnu.org/licenses/gpl.html GNU General Public License
*/

namespace midcom\datamanager\test;

use midcom\datamanager\schema;
use PHPUnit\Framework\TestCase;

class schemaTest extends TestCase
{
    public function test_process_parameter()
    {
        $schema = new schema(['fields' => [
            'test' => [
                'title' => 'test',
                'storage' => 'parameter',
                'type' => 'text',
                'widget' => 'text'
            ],
        ]]);
        $fields = $schema->get('fields');
        $this->assertArrayHasKey('test', $fields);
        $this->assertArrayHasKey('storage', $fields['test']);
        $this->assertArrayHasKey('domain', $fields['test']['storage']);
    }
}
