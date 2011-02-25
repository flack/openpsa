<?php
/**
 * @package org.openpsa.directmarketing
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * org.openpsa.directmarketing campaign handler and viewer class.
 * @package org.openpsa.directmarketing
 */
class org_openpsa_directmarketing_handler_logger extends midcom_baseclasses_components_handler
{
    /**
     * Logs a bounce from bounce_detector.php for POSTed token, marks the send receipt
     * and the campaign member as bounced.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_bounce($handler_id, $args, &$data)
    {
        if (   !array_key_exists('token', $_POST)
            || empty($_POST['token']))
        {
            throw new midcom_error('Token not present in POST or empty');
        }
        $messages = array();
        $campaigns = array();
        $this->_request_data['update_status'] = array('receipts' => array(), 'members' => array());

        $_MIDCOM->auth->request_sudo('org.openpsa.directmarketing');
        debug_add("Looking for token '{$_POST['token']}' in sent receipts");
        $ret = $this->_qb_token_receipts($_POST['token']);
        debug_print_r("_qb_token_receipts({$_POST['token']}) returned", $ret);
        if (empty($ret))
        {
            $_MIDCOM->auth->drop_sudo();
            throw new midcom_error_notfound("No receipts with token '{$_POST['token']}' found");
        }
        //While in theory we should have only one token lets use foreach just to be sure
        foreach ($ret as $receipt)
        {
            //Mark receipt as bounced
            debug_add("Found receipt #{$receipt->id}, marking bounced");
            $receipt->bounced = time();
            $this->_request_data['update_status']['receipts'][$receipt->guid] = $receipt->update();

            //Mark member(s) as bounced (first get campaign trough message)
            if (!array_key_exists($receipt->message, $campaigns))
            {
                $messages[$receipt->message] = new org_openpsa_directmarketing_campaign_message_dba($receipt->message);
            }
            $message =& $messages[$receipt->message];
            if (!array_key_exists($message->campaign, $campaigns))
            {
                $campaigns[$message->campaign] = new org_openpsa_directmarketing_campaign_dba($message->campaign);
            }
            $campaign =& $campaigns[$message->campaign];
            debug_add("Receipt belongs to message '{$message->title}' (#{$message->id}) in campaign '{$campaign->title}' (#{$campaign->id})");

            $qb2 = org_openpsa_directmarketing_campaign_member_dba::new_query_builder();
            $qb2->add_constraint('orgOpenpsaObtype', '=', ORG_OPENPSA_OBTYPE_CAMPAIGN_MEMBER);
            //PONDER: or should be just mark the person bounced in ALL campaigns while we're at it ?
            //Just in case we somehow miss the campaign
            if (isset($campaign->id))
            {
                $qb2->add_constraint('campaign', '=', $campaign->id);
            }
            $qb2->add_constraint('person', '=', $receipt->person);
            $ret2 = $qb2->execute();
            if (empty($ret2))
            {
                continue;
            }
            foreach ($ret2 as $member)
            {
                debug_add("Found member #{$member->id}, marking bounced");
                $member->orgOpenpsaObtype = ORG_OPENPSA_OBTYPE_CAMPAIGN_MEMBER_BOUNCED;
                $this->_request_data['update_status']['members'][$member->guid] = $member->update();
            }
        }

        $_MIDCOM->auth->drop_sudo();
        $_MIDCOM->skip_page_style = true;
        $_MIDCOM->cache->content->content_type('text/plain');
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_bounce($handler_id, &$data)
    {
        echo "OK\n";
        //PONDER: check  $this->_request_data['update_status'] and display something else in case all is not ok ?
    }

    /**
     * QB search for message receipts with given token and type
     *
     * @param string $token token string
     * @param int $type receipt type, defaults to ORG_OPENPSA_MESSAGERECEIPT_SENT
     * @return array QB->execute results
     */
    private function _qb_token_receipts($token, $type = ORG_OPENPSA_MESSAGERECEIPT_SENT)
    {
        $qb = org_openpsa_directmarketing_campaign_message_receipt_dba::new_query_builder();
        $qb->add_constraint('token', '=', $token);
        $qb->add_constraint('orgOpenpsaObtype', '=', $type);
        $ret = $qb->execute();
        return $ret;
    }

    /**
     * Logs a link click from link_detector.php for POSTed token, binds to person
     * and creates received and read receipts as well
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_link($handler_id, $args, &$data)
    {
        if (   !array_key_exists('token', $_POST)
            || empty($_POST['token']))
        {
            throw new midcom_error('Token not present in POST or empty');
        }
        if (   !array_key_exists('link', $_POST)
            || empty($_POST['link']))
        {
            throw new midcom_error('Link not present in POST or empty');
        }

        $_MIDCOM->auth->request_sudo('org.openpsa.directmarketing');
        debug_add("Looking for token '{$_POST['token']}' in sent receipts");
        $ret = $this->_qb_token_receipts($_POST['token']);
        debug_print_r("_qb_token_receipts({$_POST['token']}) returned", $ret);
        if (empty($ret))
        {
            $_MIDCOM->auth->drop_sudo();
            throw new midcom_error_notfound("No receipts with token '{$_POST['token']}' found");
        }
        //While in theory we should have only one token lets use foreach just to be sure
        foreach ($ret as $receipt)
        {
            $this->_create_link_receipt($receipt, $_POST['token'], $_POST['link']);
        }

        $_MIDCOM->auth->drop_sudo();
        $_MIDCOM->skip_page_style = true;
        $_MIDCOM->cache->content->content_type('text/plain');
    }

    private function _create_link_receipt(&$receipt, &$token, &$target)
    {
        if (!array_key_exists('create_status', $this->_request_data))
        {
            $this->_request_data['create_status'] = array('receipts' => array(), 'links' => array());
        }

        //Store the click in database
        $link = new org_openpsa_directmarketing_link_log_dba();
        $link->person = $receipt->person;
        $link->message = $receipt->message;
        $link->target = $target;
        $link->token = $token;
        $this->_request_data['create_status']['links'][$target] = $link->create();

        //Create received and read receipts
        $read_receipt = new org_openpsa_directmarketing_campaign_message_receipt_dba();
        $read_receipt->person = $receipt->person;
        $read_receipt->message = $receipt->message;
        $read_receipt->token = $token;
        $read_receipt->orgOpenpsaObtype = ORG_OPENPSA_MESSAGERECEIPT_RECEIVED;
        $this->_request_data['create_status']['receipts'][$token] = $read_receipt->create();
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_link($handler_id, &$data)
    {
        echo "OK\n";
        //PONDER: check $this->_request_data['create_status'] and display something else in case all is not ok ?
    }

    /**
     * Duplicates link_detector.php functionality in part (to avoid extra apache configurations)
     * and handles the logging mentioned above as well.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_redirect($handler_id, $args, &$data)
    {
        $this->_request_data['target'] = false;
        $this->_request_data['token'] = false;
        if (   count($args) == 2
            && !empty($args[1]))
        {
            //Due to the way browsers handle the URLs this form only works for root pages
            $this->_request_data['target'] = $args[1];
        }
        else if (   array_key_exists('link', $_GET)
                && !empty($_GET['link']))
        {
            $this->_request_data['target'] = $_GET['link'];
        }
        if (!empty($args[0]))
        {
            $this->_request_data['token'] = $args[0];
        }
        if (!$this->_request_data['token'])
        {
            throw new midcom_error('Token empty');
        }
        if (!$this->_request_data['target'])
        {
            throw new midcom_error('Target not present in address or GET, or is empty');
        }

        //TODO: valid target domains check

        //If we have a dummy token don't bother with looking for it, just go on.
        if ($this->_request_data['token'] === 'dummy')
        {
            $_MIDCOM->skip_page_style = true;
            $_MIDCOM->relocate($this->_request_data['target']);
            //This will exit
        }

        $_MIDCOM->auth->request_sudo('org.openpsa.directmarketing');
        debug_add("Looking for token '{$this->_request_data['token']}' in sent receipts");
        $ret = $this->_qb_token_receipts($this->_request_data['token']);
        if (empty($ret))
        {
            $_MIDCOM->auth->drop_sudo();
            throw new midcom_error_notfound("No receipts with token '{$this->_request_data['token']}' found");
        }

        //While in theory we should have only one token lets use foreach just to be sure
        foreach ($ret as $receipt)
        {
            $this->_create_link_receipt($receipt, $this->_request_data['token'], $this->_request_data['target']);
        }

        $_MIDCOM->auth->drop_sudo();
        $_MIDCOM->skip_page_style = true;
        $_MIDCOM->relocate($this->_request_data['target']);
        //This will exit
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_redirect($handler_id, &$data)
    {
        //TODO: make an element to display in case our relocate fails (with link to the intended target...)
    }
}
?>