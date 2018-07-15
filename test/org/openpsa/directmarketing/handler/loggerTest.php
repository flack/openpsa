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
class org_openpsa_directmarketing_handler_loggerTest extends openpsa_testcase
{
    protected static $_person;

    public static function setUpBeforeClass()
    {
        self::$_person = self::create_user(true);
    }

    public function testHandler_bounce()
    {
        $helper = new openpsa_test_campaign_helper($this);
        $message = $helper->get_message();
        $token = uniqid();
        $receipt = $this->create_object(org_openpsa_directmarketing_campaign_messagereceipt_dba::class, [
            'token' => $token,
            'orgOpenpsaObtype' => org_openpsa_directmarketing_campaign_messagereceipt_dba::SENT,
            'message' => $message->id
        ]);

        $_POST = ['token' => $token];
        midcom::get()->auth->request_sudo('org.openpsa.directmarketing');

        $data = $this->run_handler('org.openpsa.directmarketing', ['logger', 'bounce']);
        $this->assertEquals('log_bounce', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_link()
    {
        $helper = new openpsa_test_campaign_helper($this);
        $message = $helper->get_message();
        $token = uniqid();
        $receipt = $this->create_object(org_openpsa_directmarketing_campaign_messagereceipt_dba::class, [
            'token' => $token,
            'orgOpenpsaObtype' => org_openpsa_directmarketing_campaign_messagereceipt_dba::SENT,
            'message' => $message->id
        ]);

        $_POST = ['token' => $token, 'link' => 'dummy'];
        midcom::get()->auth->request_sudo('org.openpsa.directmarketing');

        $data = $this->run_handler('org.openpsa.directmarketing', ['logger', 'link']);
        $this->assertEquals('log_link', $data['handler_id']);

        $qb = org_openpsa_directmarketing_link_log_dba::new_query_builder();
        $qb->add_constraint('token', '=', $token);
        $results = $qb->execute();
        $this->register_objects($results);
        $this->assertCount(1, $results);

        $qb = org_openpsa_directmarketing_campaign_messagereceipt_dba::new_query_builder();
        $qb->add_constraint('token', '=', $token);
        $results = $qb->execute();
        $this->register_objects($results);
        $this->assertCount(2, $results);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_redirect()
    {
        $helper = new openpsa_test_campaign_helper($this);
        $message = $helper->get_message();
        $token = 'a' . uniqid();
        $receipt = $this->create_object(org_openpsa_directmarketing_campaign_messagereceipt_dba::class, [
            'token' => $token,
            'orgOpenpsaObtype' => org_openpsa_directmarketing_campaign_messagereceipt_dba::SENT,
            'message' => $message->id
        ]);

        $_POST = ['token' => $token, 'link' => 'dummy'];
        midcom::get()->auth->request_sudo('org.openpsa.directmarketing');

        $url = $this->run_relocate_handler('org.openpsa.directmarketing', ['logger', 'redirect', $token, 'dummy']);
        $this->assertEquals('dummy', $url);

        $qb = org_openpsa_directmarketing_link_log_dba::new_query_builder();
        $qb->add_constraint('token', '=', $token);
        $results = $qb->execute();
        $this->register_objects($results);
        $this->assertCount(1, $results);

        $qb = org_openpsa_directmarketing_campaign_messagereceipt_dba::new_query_builder();
        $qb->add_constraint('token', '=', $token);
        $results = $qb->execute();
        $this->register_objects($results);
        $this->assertCount(2, $results);

        midcom::get()->auth->drop_sudo();
    }
}
