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
use Symfony\Component\HttpFoundation\Request;
use midcom_db_person;

class controllerTest extends openpsa_testcase
{
    public function test_process_cancel()
    {
        $schemadb = new schemadb;
        $schemadb->add('default', new schema(['fields' => []]));
        $dm = new datamanager($schemadb);
        $controller = $dm->get_controller('test');

        $request = Request::create('/', 'POST', [
            'test' => [
                'form_toolbar' => ['cancel0' => '']
            ]
        ]);

        $result = $controller->handle($request);
        $this->assertSame(controller::CANCEL, $result);
    }

    public function test_process_save()
    {
        $user = $this->create_user(true);
        $user->set_privilege('midgard:create', 'SELF');
        $schemadb = new schemadb;
        $schemadb->add('default', new schema(['fields' => []]));
        $dm = new datamanager($schemadb);

        $object = new midcom_db_person;

        $controller = $dm
            ->set_storage($object)
            ->get_controller('test');

        $request = Request::create('/', 'POST', [
            'test' => [
                'form_toolbar' => ['save0' => '']
            ]
        ]);

        $result = $controller->handle($request);

        $this->register_object($object);

        $this->assertSame(controller::SAVE, $result);
        $this->assertNotEmpty($object->id);
    }

}
