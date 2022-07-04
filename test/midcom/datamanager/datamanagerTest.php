<?php
/**
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace midcom\datamanager\test;

use midcom\datamanager\schemadb;
use midcom\datamanager\schema;
use midcom\datamanager\datamanager;
use PHPUnit\Framework\TestCase;

class datamanagerTest extends TestCase
{
    public function test_get_content_html()
    {
        $schemadb = new schemadb;
        $schemadb->add('default', new schema(['fields' => [
            'name' => [
                'storage' => 'name',
                'widget' => 'text'
            ]
        ]]));
        $dm = new datamanager($schemadb);

        $topic1 = new \midcom_db_topic;
        $topic1->name = uniqid();
        $dm->set_storage($topic1);
        $this->assertEquals($topic1->name, $dm->get_content_html()['name']);

        $topic2 = new \midcom_db_topic;
        $topic2->name = uniqid();
        $dm->set_storage($topic2);
        $this->assertEquals($topic2->name, $dm->get_content_html()['name']);
    }
}
