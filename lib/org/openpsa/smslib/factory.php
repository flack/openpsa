<?php
/**
 * @package org.openpsa.smslib
 */

/**
 * Loader for the supported SMS interfaces
 * @package org.openpsa.smslib
 */
class org_openpsa_smslib extends midcom_baseclasses_components_purecode
{
    /* Common properties, all backends must handle these (if they have
    API specific names for them then they must create references to use them) */
    var $location = '';
    var $client_id = '';
    var $user = '';
    var $password = '';
    var $errstr = '';
    var $errcode = 200; //Use HTTP style error codes as default

    public function __construct()
    {
        parent::__construct();
        $this->_component='org.openpsa.smslib';

        return true;
    }

    function factory($type='tambur')
    {
        $classname='org_openpsa_smslib_'.$type;
        if (!class_exists($classname))
        {
            return false;
        }
        return new $classname();
    }

    function send_sms()
    {
        debug_add('SMSLib factory method does not do anything, must be overridden in real backend', MIDCOM_LOG_ERROR);
        return false;
    }

    function send_mms()
    {
        debug_add('SMSLib factory method does not do anything, must be overridden in real backend', MIDCOM_LOG_ERROR);
        return false;
    }

    function msg_to_latin1($msg)
    {
        if (   function_exists('mb_detect_encoding')
            && function_exists('iconv'))
        {
            //TODO: Should we specify a larger list ??
            $encoding = strtoupper(mb_detect_encoding($msg, 'auto'));
            debug_add("msg is {$encoding} encoded");
            if (   $encoding
                && (   $encoding != 'ISO-8859-1'
                    && $encoding != 'ASCII'))
            {
                debug_add("converting msg from {$encoding} to ISO-8859-1 (with //TRANSLIT)", MIDCOM_LOG_WARN);
                $stat = iconv($encoding, 'ISO-8859-1//TRANSLIT', $msg);
                if ($stat)
                {
                    $msg = $stat;
                }
            }
        }
        return $msg;
    }
}
?>