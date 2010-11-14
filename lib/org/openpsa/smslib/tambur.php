<?php
/**
 * @package org.openpsa.smslib
 */

/**
 * Interface to Tambur API for sending SMS, MMS, Wap-push etc
 *
 * @todo Implement other methods than send_sms.
 * @package org.openpsa.smslib
 */
class org_openpsa_smslib_tambur extends org_openpsa_smslib
{
    var $uri = ''; //URL for tambur gateway (reference to location)

    function __construct()
    {
        parent::__construct();
        $this->location = &$this->uri;
        return true;
    }

    function _sanity_check()
    {
        if (   !$this->uri
            || !$this->user
            || !$this->password)
        {
            debug_add('All required fields not present', MIDCOM_LOG_ERROR);
            $this->errcode = '400';
            $this->errstr = 'required fields missing';
            return false;
        }
        return true;
    }

    /**
     * Returns either (int)balance on success or (bool)false on error
     */
    function get_balance()
    {
        debug_push_class(__CLASS__, __FUNCTION__);
        if (!$this->_sanity_check())
        {
            return false;
        }
        $url = "{$this->uri}/balance?l={$this->user}&p={$this->password}";
        $fp = @fopen($url, 'r');
        $this->_get_remote_error($http_response_header);
        if (!$fp)
        {
            debug_add("Error opening {$url}, response: ".$http_response_header[0]);
            debug_add("Failed to get balance, error: {$this->errstr}", MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }
        $content = '';
        while (!feof($fp))
        {
            //Sometimes this gives warnings on "SSL: fatal protocol error"
            $content .= @fread($fp, 4096);
        }
        fclose($fp);
        debug_add("Got content\n===\n{$content}===");
        if (!preg_match('/<(credit-balance)>(.*?)<\/\\1>/', $content, $matches_credit))
        {
            debug_add('could not find balance data in response', MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }

        debug_pop();
        return (int)$matches_credit[2];
    }

    /**
     * Method sends any SMS deliverable data, handles encoding so when sending logos
     * etc, provide msg and udh as binary.
     */
    function send_sms($number, $msg, $sender=false, $dlr=false, $udh=false, $clientid=false)
    {
        debug_push_class(__CLASS__, __FUNCTION__);
        if (!$this->_sanity_check())
        {
            return false;
        }
        if ($sender)
        {
            //Check sender length
            //is_numeric doesn't catch the + of international number properly
            if (preg_match('/[^0-9+]/', $sender))
            {
                if (strlen($sender)>11)
                {
                    debug_add('alphanumeric sender too long (max 11 ASCII characters)', MIDCOM_LOG_ERROR);
                    $this->errstr = 'sender too long';
                    $this->errcode = 400;
                    debug_pop();
                    return false;
                }
            }
            else
            {
                if (strlen($sender)>25)
                {
                    debug_add('numeric sender too long (max 25 numbers)', MIDCOM_LOG_ERROR);
                    $this->errstr = 'sender too long';
                    $this->errcode = 400;
                    debug_pop();
                    return false;
                }
            }
        }
        $url = "{$this->uri}/sendsms?l={$this->user}&p={$this->password}";
        //TODO: Add support for array of numbers
        //Strip invalid characters from number
        //PONDER: leave + or not ?? (check API, works as is now though)
        $number = preg_replace('/[^0-9]+/', '', $number);
        $url .= "&msisdn={$number}";

        //Try to make sure the message is in correct encoding.
        $msg = $this->msg_to_latin1($msg);
        $url .= '&msg=' . rawurlencode($msg);
        if ($sender)
        {
            $url .= '&sender=' . rawurlencode($sender);
        }
        if ($udh)
        {
            $url .= '&udh=' . rawurlencode($udh);
        }
        if ($clientid)
        {
            $url .= "&clientid={$clientid}";
        }

        //URL constructed, time to work
        $fp = @fopen($url, 'r');
        $this->_get_remote_error($http_response_header);
        if (!$fp)
        {
            debug_add("Error opening {$url}, response: " . $http_response_header[0]);
            debug_add("Failed to send message, error: {$this->errstr}", MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }
        $content = '';
        while (!feof($fp))
        {
            //Sometimes this gives warnings on "SSL: fatal protocol error"
            $content .= @fread($fp, 4096);
        }
        fclose($fp);
        debug_add("Got content\n===\n{$content}===");
        //TODO: Parse the returned XML and get for example dlr IDs

        debug_pop();
        return true;
    }

    function _get_remote_error($headers)
    {
        preg_match('/HTTP\/[0-9.]+\s([0-9]+)\s(.*)/', $headers[0], $matches_hdr);
        $code = $matches_hdr[1];
        $string = $matches_hdr[2];
        $this->errcode = (int)$code;
        switch ((int)$code)
        {
            case 200:
            case 202:
            case 204:
                $this->errstr = 'no error';
                $this->errcode = 200;
                break;
            case 403:
                $this->errstr = 'authentication failed';
                break;
            case 404:
            case 500:
                $this->errstr = 'server error (do not resubmit)';
                $this->errcode = 500;
                break;
            default:
            case 400:
                //TODO: Figure out the reason if possible
                $this->errstr = 'unknown error';
                break;
        }
    }
}
?>