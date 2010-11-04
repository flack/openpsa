<?php
error_reporting(E_ALL);
require_once('midcom/lib/org/openpsa/mail/nonmidcom.php');
$mail = new org_openpsa_mail();

echo "mb_detect_encoding_list: " . $mail->_config->get('mb_detect_encoding_list') . "\n";

debug_add('Test debug level log', MIDCOM_LOG_DEBUG);
debug_add('Test info level log', MIDCOM_LOG_INFO);
debug_add('Test warn level log', MIDCOM_LOG_WARN);
debug_add('Test error level log', MIDCOM_LOG_ERROR);


?>