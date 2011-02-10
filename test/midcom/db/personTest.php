<?php
require_once('rootfile.php');

class personTest extends openpsa_testcase
{
    public function testCRUD()
    {
        $_MIDCOM->auth->request_sudo('midcom.core');

        $person = new midcom_db_person();
        $stat = $person->create();
        $this->assertTrue($stat);

        $person = new midcom_db_person($person->guid);
        $this->assertEquals('person #' . $person->id, $person->name);
        $this->assertEquals('person #' . $person->id, $person->rname);
        $person->firstname = ' Firstname ';
        $person->lastname = ' Lastname ';
        $stat = $person->update();
        $this->assertTrue($stat);
        $this->assertEquals('Firstname Lastname', $person->name);
        $this->assertEquals('Lastname, Firstname', $person->rname);

        $stat = $person->delete();
        $this->assertTrue($stat);

        $_MIDCOM->auth->drop_sudo();
     }
}
?>