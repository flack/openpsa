<?php
/**
 * @package org.openpsa.smslib
 */
/**
 * Interface to Clickatell API
 *
 * @todo Implement other methods than send_sms.
 * @package org.openpsa.smslib
 */
class org_openpsa_smslib_clickatell extends org_openpsa_smslib
{
    var $uri = ''; //URL for clickatell gateway (reference to location)
    var $api_id = ''; //reference to client_id
    var $features = 'FEAT_8BIT FEAT_UDH FEAT_CONCAT FEAT_ALPHA FEAT_NUMER'; //req_feat list

    public function __construct()
    {
        parent::__construct();
        $this->location = &$this->uri;
        $this->client_id = &$this->api_id;
    }

    /**
     * Non-numeric balance should be supposed infinite.
     *
     * Also Clickatell has variable pricing and we cannot know the true
     * cost of a message before it is sent so the balance checks are rather difficult
     *
     * @return boolean false on error or whatever the GW returns
     */
    function get_balance()
    {
        if (!$this->_sanity_check())
        {
            return false;
        }
        return 'unknown'; //Non numeric balance is supposed to be infinite
    }

    private function _sanity_check()
    {
        if (   !$this->uri
            || !$this->user
            || !$this->password
            || !$this->client_id)
        {
            debug_add('Not all required fields present', MIDCOM_LOG_ERROR);
            return false;
        }
        return true;
    }


    /**
     * Method sends any SMS deliverable data, handles encoding so when sending logos
     * etc, provide msg and udh as binary.
     */
    function send_sms($number, $msg, $sender=false, $dlr=false, $udh=false, $clientid=false)
    {
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
                    return false;
                }
            }
            else
            {
                if (strlen($sender)>16)
                {
                    debug_add('numeric sender too long (max 16 numbers)', MIDCOM_LOG_ERROR);
                    return false;
                }
            }
        }
        //Base URL, username, api_id and password
        $url = "{$this->uri}/sendmsg?user={$this->user}&password={$this->password}&api_id={$this->client_id}";
        //Handle required features and set concat to N
        $url .= '&concat=N&req_feat=' . $this->_encode_req_feat();

        //TODO: Add support for array of numbers
        //Strip invalid characters from number
        //PONDER: leave + or not ?? (check API, works as is not though)
        $number = preg_replace('/[^0-9]+/', '', $number);
        $url .= "&to={$number}";

        //Try to make sure the message is in correct encoding.
        $msg = $this->msg_to_latin1($msg);
        $url .= '&text=' . rawurlencode($msg);
        if ($sender)
        {
            $url .= '&from=' . rawurlencode($sender);
        }
        if ($udh)
        {
            $url .= '&udh=' . rawurlencode($udh);
        }
        if ($clientid)
        {
            $url .= "&cliMsgId={$clientid}";
        }

        //URL constructed, time to work
        $fp = @fopen($url, 'r');
        $this->_get_http_error($http_response_header);
        if (!$fp)
        {
            debug_add("Error opening {$url}, response: " . $http_response_header[0]);
            debug_add("Failed to send message, HTTP error: {$this->errstr}", MIDCOM_LOG_ERROR);
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
        if ($this->_get_content_error($content))
        {
            debug_add("Failed to send message, API error: {$this->errstr}", MIDCOM_LOG_ERROR);
            return false;
        }

        //TODO: Parse rest the returned text and get for example message IDs

        return true;
    }

    /**
     * Encodes the features list to decimal value used by the server
     */
    private function _encode_req_feat()
    {
        $feat_arr_rev = explode(' ', $this->features);
        //Make sure each feature is specified only once
        foreach ($feat_arr_rev as $feature)
        {
            $feat_arr[strtoupper($feature)] = true;
        }
        $feat_arr['FEAT_TEXT'] = true; //Always Force this feature
        $feat_arr['FEAT_CONCAT'] = true; //Always Force this feature
        $feat_dec = 0;
        foreach ($feat_arr as $feature => $bool)
        {
            switch($feature)
            {
                case 'FEAT_TEXT':
                    $feat_dec += 1;
                    break;
                case 'FEAT_8BIT':
                    $feat_dec += 2;
                    break;
                case 'FEAT_UDH':
                    $feat_dec += 4;
                    break;
                case 'FEAT_UCS2':
                    $feat_dec += 8;
                    break;
                case 'FEAT_ALPHA':
                    $feat_dec += 16;
                    break;
                case 'FEAT_NUMER':
                    $feat_dec += 32;
                    break;
                case 'FEAT_FLASH':
                    $feat_dec += 512;
                    break;
                case 'FEAT_DELIVACK':
                    $feat_dec += 8192;
                    break;
                case 'FEAT_CONCAT':
                    $feat_dec += 16384;
                    break;
                default:
                    debug_add("feature \"{$feature}\" not recognized", MIDCOM_LOG_WARN);
                    break;
            }
        }
        return $feat_dec;
    }

    /**
     * Server HTTP level error decode
     */
    private function _get_http_error($headers)
    {
        preg_match('/HTTP\/[0-9.]+\s([0-9]+)\s(.*)/', $headers[0], $matches_hdr);
        $code = $matches_hdr[1];
        switch ((int)$code)
        {
            case 200:
            case 202:
            case 204:
                $this->errstr = 'no error';
                break;
            case 403:
                $this->errstr = 'authentication failed';
                break;
            case 404:
            case 500:
                $this->errstr = 'server error (do not resubmit)';
                break;
            default:
            case 400:
                //TODO: Figure out the reason if possible
                $this->errstr = 'unknown error';
                break;
        }
    }

    /**
     * Look for error messages in content and decode them, returns false on no errors found, true on errors found.
     */
    private function _get_content_error($content)
    {
        if (!preg_match('/ERR:\s([0-9]{3}),\s(.+)/', $content, $matches))
        {
            $this->errstr = 'no error';
            return false;
        }
        $code = preg_replace('/^0/', '', trim($matches[1]));
        $msg = trim($matches[2]);
        if (empty($msg))
        {
            $msg = 'unknown error';
        }

        switch ((int)$code)
        {
            case 1:
            case 2:
            case 3:
            case 4:
            case 5:
                $this->errstr = $msg;
                break;
            case 114:
                $this->errstr = $msg;
                break;
            default:
            case 400:
                $this->errstr = $msg;
                break;
        }

        return true;
    }
}
?>