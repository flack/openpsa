<?php
/**
 * @package org.openpsa.directmarketing
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * org.openpsa.directmarketing campaign handler and viewer class.
 * @package org.openpsa.directmarketing
 */
class org_openpsa_directmarketing_handler_logger extends midcom_baseclasses_components_handler
{
    /**
     * Logs a bounce from bounce_detector.php for POSTed token, marks the send receipt
     * and the campaign member as bounced.
     */
    public function _handler_bounce(Request $request)
    {
        if (!$request->request->has('token')) {
            throw new midcom_error('Token not present in POST or empty');
        }
        $this->_request_data['update_status'] = ['receipts' => [], 'members' => []];

        midcom::get()->auth->request_sudo('org.openpsa.directmarketing');
        $ret = $this->_qb_token_receipts($request->request->get('token'));
        //While in theory we should have only one token lets use foreach just to be sure
        foreach ($ret as $receipt) {
            //Mark receipt as bounced
            debug_add("Found receipt #{$receipt->id}, marking bounced");
            $receipt->bounced = time();
            $this->_request_data['update_status']['receipts'][$receipt->guid] = $receipt->update();

            //Mark member(s) as bounced (first get campaign trough message)
            $message = org_openpsa_directmarketing_campaign_message_dba::get_cached($receipt->message);
            $campaign = org_openpsa_directmarketing_campaign_dba::get_cached($message->campaign);

            debug_add("Receipt belongs to message '{$message->title}' (#{$message->id}) in campaign '{$campaign->title}' (#{$campaign->id})");

            $qb2 = org_openpsa_directmarketing_campaign_member_dba::new_query_builder();
            $qb2->add_constraint('orgOpenpsaObtype', '=', org_openpsa_directmarketing_campaign_member_dba::NORMAL);
            //PONDER: or should be just mark the person bounced in ALL campaigns while we're at it ?
            $qb2->add_constraint('campaign', '=', $campaign->id);
            $qb2->add_constraint('person', '=', $receipt->person);

            foreach ($qb2->execute() as $member) {
                debug_add("Found member #{$member->id}, marking bounced");
                $member->orgOpenpsaObtype = org_openpsa_directmarketing_campaign_member_dba::BOUNCED;
                $this->_request_data['update_status']['members'][$member->guid] = $member->update();
            }
        }

        midcom::get()->auth->drop_sudo();
        //PONDER: check  $this->_request_data['update_status'] and display something else in case all is not ok ?
        return new Response("OK\n", Response::HTTP_OK, ['Content-Type', 'text/plain']);
    }

    /**
     * QB search for message receipts with given token
     *
     * @param string $token token string
     * @return org_openpsa_directmarketing_campaign_messagereceipt_dba[]
     */
    private function _qb_token_receipts(string $token) : array
    {
        debug_add("Looking for token '{$token}' in sent receipts");
        $qb = org_openpsa_directmarketing_campaign_messagereceipt_dba::new_query_builder();
        $qb->add_constraint('token', '=', $token);
        $qb->add_constraint('orgOpenpsaObtype', '=', org_openpsa_directmarketing_campaign_messagereceipt_dba::SENT);
        $ret = $qb->execute();
        debug_print_r("_qb_token_receipts({$token}) returned", $ret);
        if (empty($ret)) {
            midcom::get()->auth->drop_sudo();
            throw new midcom_error_notfound("No receipts with token '{$token}' found");
        }
        return $ret;
    }

    /**
     * Logs a link click from link_detector.php for POSTed token, binds to person
     * and creates received and read receipts as well
     */
    public function _handler_link(Request $request)
    {
        $token = $request->request->get('token');
        $link = $request->request->get('link');
        if (!$token) {
            throw new midcom_error('Token not present in POST or empty');
        }
        if (!$link) {
            throw new midcom_error('Link not present in POST or empty');
        }

        midcom::get()->auth->request_sudo('org.openpsa.directmarketing');
        $ret = $this->_qb_token_receipts($token);

        //While in theory we should have only one token lets use foreach just to be sure
        foreach ($ret as $receipt) {
            $this->_create_link_receipt($receipt, $token, $link);
        }

        midcom::get()->auth->drop_sudo();
        //PONDER: check $this->_request_data['create_status'] and display something else in case all is not ok ?
        return new Response("OK\n", Response::HTTP_OK, ['Content-Type', 'text/plain']);
    }

    private function _create_link_receipt(org_openpsa_directmarketing_campaign_messagereceipt_dba $receipt, string $token, $target)
    {
        if (!array_key_exists('create_status', $this->_request_data)) {
            $this->_request_data['create_status'] = ['receipts' => [], 'links' => []];
        }

        //Store the click in database
        $link = new org_openpsa_directmarketing_link_log_dba();
        $link->person = $receipt->person;
        $link->message = $receipt->message;
        $link->target = $target;
        $link->token = $token;
        $this->_request_data['create_status']['links'][$target] = $link->create();

        //Create received and read receipts
        $read_receipt = new org_openpsa_directmarketing_campaign_messagereceipt_dba();
        $read_receipt->person = $receipt->person;
        $read_receipt->message = $receipt->message;
        $read_receipt->token = $token;
        $read_receipt->orgOpenpsaObtype = org_openpsa_directmarketing_campaign_messagereceipt_dba::RECEIVED;
        $this->_request_data['create_status']['receipts'][$token] = $read_receipt->create();
    }

    /**
     * Duplicates link_detector.php functionality in part (to avoid extra apache configurations)
     * and handles the logging mentioned above as well.
     *
     * @param Request $request The request object
     * @param string $token The token
     * @param string $url The URL
     */
    public function _handler_redirect(Request $request, string $token, $url = null)
    {
        if (!empty($url)) {
            //Due to the way browsers handle the URLs this form only works for root pages
            $target = $url;
        } elseif ($request->query->has('link')) {
            $target = $request->query->get('link');
        } else {
            throw new midcom_error('Target not present in address or GET, or is empty');
        }

        //TODO: valid target domains check

        //If we have a dummy token don't bother with looking for it, just go on.
        if ($token === 'dummy') {
            return new midcom_response_relocate($target);
        }

        midcom::get()->auth->request_sudo($this->_component);
        $ret = $this->_qb_token_receipts($token);

        //While in theory we should have only one token lets use foreach just to be sure
        foreach ($ret as $receipt) {
            $this->_create_link_receipt($receipt, $token, $target);
        }

        midcom::get()->auth->drop_sudo();
        return new midcom_response_relocate($target);
    }
}
