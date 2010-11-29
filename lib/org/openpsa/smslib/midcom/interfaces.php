<?php
/**
 * @package org.openpsa.smslib
 */

/**
 * OpenPSA SMS library, handles sending SMS/MMS
 *
 * @package org.openpsa.smslib
 */
class org_openpsa_smslib_interface extends midcom_baseclasses_components_interface
{
    public function __construct()
    {
        $this->_autoload_files = array
        (
            'factory.php',
            'tambur.php',
            'clickatell.php',
            'messto.php',
            'email2sms.php',
        );
    }
}
?>