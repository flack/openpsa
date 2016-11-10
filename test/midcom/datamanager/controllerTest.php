<?php
/**
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace midcom\datamanager\test;

use openpsa_testcase;
use midcom\datamanager\controller;
use midcom\datamanager\schemadb;
use midcom\datamanager\schema;
use midcom\datamanager\datamanager;

class controllerTest extends openpsa_testcase
{
    public function test_process_cancel()
    {
        $schemadb = new schemadb;
        $schemadb->add('default', new schema(array('fields' => array())));
        $dm = new datamanager($schemadb);
        $controller = $dm->get_controller('test');
        $_SERVER['REQUEST_METHOD'] = 'post';
        $_POST = array(
            'test' => array(
                'form_toolbar' => array('cancel0' => '')
            )
        );
        $result = $controller->process();
        $this->assertSame(controller::CANCEL, $result);
    }
}
